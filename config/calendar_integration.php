<?php

if (!function_exists('ensure_calendar_integration_schema')) {
    function ensure_calendar_integration_schema(PDO $conn): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $conn->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS calendar_connections (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                empresa_id INT NULL,
                provider ENUM('google','microsoft') NOT NULL,
                account_email VARCHAR(190) NULL,
                token_payload LONGTEXT NOT NULL,
                expires_at DATETIME NULL,
                connected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_calendar_user (usuario_id),
                KEY idx_calendar_company (empresa_id),
                KEY idx_calendar_provider (provider)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL);

        try {
            $columns = $conn->query("SHOW COLUMNS FROM actividades_capacitacion")->fetchAll(PDO::FETCH_COLUMN);
            $required = [
                'calendar_provider' => "ALTER TABLE actividades_capacitacion ADD COLUMN calendar_provider VARCHAR(20) NULL AFTER enlace_reunion",
                'calendar_event_id' => "ALTER TABLE actividades_capacitacion ADD COLUMN calendar_event_id VARCHAR(255) NULL AFTER calendar_provider",
                'calendar_event_url' => "ALTER TABLE actividades_capacitacion ADD COLUMN calendar_event_url TEXT NULL AFTER calendar_event_id",
            ];
            foreach ($required as $column => $sql) {
                if (!in_array($column, $columns, true)) {
                    $conn->exec($sql);
                }
            }
        } catch (Throwable $e) {
            // La conexión del calendario sigue disponible aunque la tabla de actividades
            // todavía no exista durante una instalación inicial.
        }
        $ensured = true;
    }

    function calendar_app_url(): string
    {
        $configured = rtrim((string)(getenv('APP_URL') ?: ''), '/');
        if ($configured !== '') {
            return $configured;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        return $scheme . '://' . $host . rtrim($directory, '/');
    }

    function calendar_redirect_uri(): string
    {
        return calendar_app_url() . '/calendar_auth.php';
    }

    function calendar_provider_label(string $provider): string
    {
        return $provider === 'microsoft' ? 'Microsoft Outlook' : 'Google Calendar';
    }

    function calendar_provider_configured(string $provider): bool
    {
        if ($provider === 'google') {
            return is_file(dirname(__DIR__) . '/credentials.json') && is_file(dirname(__DIR__) . '/vendor/autoload.php');
        }

        return trim((string)getenv('MICROSOFT_CLIENT_ID')) !== ''
            && trim((string)getenv('MICROSOFT_CLIENT_SECRET')) !== '';
    }

    function calendar_token_key(): string
    {
        $secret = (string)(getenv('CALENDAR_TOKEN_KEY') ?: getenv('APP_KEY') ?: '');
        if ($secret === '') {
            $secret = (string)getenv('DB_NAME') . '|' . (string)getenv('DB_USER') . '|preventwork-calendar';
        }
        return hash('sha256', $secret, true);
    }

    function calendar_encrypt_token(array $token): string
    {
        $json = json_encode($token, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('No fue posible proteger el token del calendario.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($json, 'aes-256-gcm', calendar_token_key(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('No fue posible proteger el token del calendario.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    function calendar_decrypt_token(string $payload): array
    {
        $raw = base64_decode($payload, true);
        if ($raw === false || strlen($raw) < 29) {
            return [];
        }
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $json = openssl_decrypt($cipher, 'aes-256-gcm', calendar_token_key(), OPENSSL_RAW_DATA, $iv, $tag);
        $decoded = $json === false ? null : json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    function calendar_user_company_id(PDO $conn, int $userId): int
    {
        $stmt = $conn->prepare('SELECT empresa_id FROM usuarios WHERE id = ?');
        $stmt->execute([$userId]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    function calendar_connection(PDO $conn, int $userId): ?array
    {
        ensure_calendar_integration_schema($conn);
        $stmt = $conn->prepare('SELECT * FROM calendar_connections WHERE usuario_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $row['token'] = calendar_decrypt_token((string)$row['token_payload']);
        $row['connected'] = !empty($row['token']);
        return $row;
    }

    function calendar_save_connection(PDO $conn, int $userId, int $companyId, string $provider, array $token, ?string $email = null): void
    {
        ensure_calendar_integration_schema($conn);
        if (!in_array($provider, ['google', 'microsoft'], true)) {
            throw new InvalidArgumentException('Proveedor de calendario no válido.');
        }
        $expiresAt = null;
        if (!empty($token['expires_in'])) {
            $created = (int)($token['created'] ?? time());
            $expiresAt = date('Y-m-d H:i:s', $created + (int)$token['expires_in']);
        }
        $stmt = $conn->prepare(<<<'SQL'
            INSERT INTO calendar_connections
                (usuario_id, empresa_id, provider, account_email, token_payload, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                empresa_id=VALUES(empresa_id), provider=VALUES(provider), account_email=VALUES(account_email),
                token_payload=VALUES(token_payload), expires_at=VALUES(expires_at), connected_at=NOW()
        SQL);
        $stmt->execute([$userId, $companyId ?: null, $provider, $email, calendar_encrypt_token($token), $expiresAt]);
    }

    function calendar_disconnect(PDO $conn, int $userId): void
    {
        ensure_calendar_integration_schema($conn);
        $stmt = $conn->prepare('DELETE FROM calendar_connections WHERE usuario_id = ?');
        $stmt->execute([$userId]);
        unset($_SESSION['google_access_token']);
    }

    function calendar_http_request(string $url, array $options = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
        }
        $curl = curl_init($url);
        $headers = $options['headers'] ?? [];
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $options['method'] ?? 'GET',
        ]);
        if (array_key_exists('body', $options)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['body']);
        }
        $body = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $error !== '') {
            throw new RuntimeException('No fue posible comunicarse con el proveedor de calendario.');
        }
        $decoded = json_decode((string)$body, true);
        if ($status < 200 || $status >= 300) {
            $message = $decoded['error']['message'] ?? $decoded['error_description'] ?? 'El proveedor rechazó la solicitud.';
            throw new RuntimeException((string)$message);
        }
        return is_array($decoded) ? $decoded : [];
    }

    function calendar_refresh_microsoft_token(PDO $conn, array $connection): array
    {
        $token = $connection['token'] ?? [];
        $expiresAt = !empty($connection['expires_at']) ? strtotime($connection['expires_at']) : 0;
        if ($expiresAt > time() + 90) {
            return $token;
        }
        if (empty($token['refresh_token'])) {
            throw new RuntimeException('La conexión con Microsoft venció. Vuelve a conectarla desde Configuración.');
        }
        $tenant = trim((string)(getenv('MICROSOFT_TENANT_ID') ?: 'common'));
        $response = calendar_http_request("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
            'method' => 'POST',
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'body' => http_build_query([
                'client_id' => getenv('MICROSOFT_CLIENT_ID'),
                'client_secret' => getenv('MICROSOFT_CLIENT_SECRET'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $token['refresh_token'],
                'redirect_uri' => calendar_redirect_uri(),
                'scope' => 'openid profile email offline_access Calendars.ReadWrite',
            ]),
        ]);
        if (empty($response['refresh_token'])) {
            $response['refresh_token'] = $token['refresh_token'];
        }
        $response['created'] = time();
        calendar_save_connection($conn, (int)$connection['usuario_id'], (int)$connection['empresa_id'], 'microsoft', $response, $connection['account_email'] ?: null);
        return $response;
    }

    function calendar_sync_event(PDO $conn, array $connection, array $eventData): array
    {
        $provider = (string)($connection['provider'] ?? '');
        if ($provider === 'google') {
            require_once dirname(__DIR__) . '/vendor/autoload.php';
            $client = new Google\Client();
            $client->setAuthConfig(dirname(__DIR__) . '/credentials.json');
            $client->setRedirectUri(calendar_redirect_uri());
            $client->addScope(Google\Service\Calendar::CALENDAR);
            $client->setAccessToken($connection['token']);
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken() ?: ($connection['token']['refresh_token'] ?? null);
                if (!$refreshToken) {
                    throw new RuntimeException('La conexión con Google venció. Vuelve a conectarla desde Configuración.');
                }
                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                if (!empty($newToken['error'])) {
                    throw new RuntimeException('Google solicitó volver a conectar el calendario.');
                }
                $newToken['refresh_token'] = $refreshToken;
                calendar_save_connection($conn, (int)$connection['usuario_id'], (int)$connection['empresa_id'], 'google', $newToken, $connection['account_email'] ?: null);
                $client->setAccessToken($newToken);
            }
            $service = new Google\Service\Calendar($client);
            $event = new Google\Service\Calendar\Event([
                'summary' => $eventData['title'],
                'description' => $eventData['description'],
                'location' => $eventData['location'] ?? '',
                'start' => ['dateTime' => $eventData['start'], 'timeZone' => 'America/Bogota'],
                'end' => ['dateTime' => $eventData['end'], 'timeZone' => 'America/Bogota'],
                'conferenceData' => ['createRequest' => [
                    'requestId' => 'preventwork-' . ($eventData['activity_id'] ?? time()) . '-' . bin2hex(random_bytes(4)),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ]],
            ]);
            $existingId = ($eventData['existing_provider'] ?? '') === 'google' ? trim((string)($eventData['event_id'] ?? '')) : '';
            $created = $existingId !== ''
                ? $service->events->update('primary', $existingId, $event, ['conferenceDataVersion' => 1, 'sendUpdates' => 'all'])
                : $service->events->insert('primary', $event, ['conferenceDataVersion' => 1, 'sendUpdates' => 'all']);
            return ['provider' => 'google', 'event_id' => $created->getId(), 'meeting_url' => $created->getHangoutLink(), 'web_url' => $created->getHtmlLink()];
        }

        if ($provider === 'microsoft') {
            $token = calendar_refresh_microsoft_token($conn, $connection);
            $body = [
                'subject' => $eventData['title'],
                'body' => ['contentType' => 'HTML', 'content' => nl2br(htmlspecialchars($eventData['description'], ENT_QUOTES, 'UTF-8'))],
                'start' => ['dateTime' => date('Y-m-d\\TH:i:s', strtotime($eventData['start'])), 'timeZone' => 'SA Pacific Standard Time'],
                'end' => ['dateTime' => date('Y-m-d\\TH:i:s', strtotime($eventData['end'])), 'timeZone' => 'SA Pacific Standard Time'],
                'location' => ['displayName' => $eventData['location'] ?? ''],
                'isOnlineMeeting' => true,
                'onlineMeetingProvider' => 'teamsForBusiness',
            ];
            $existingId = ($eventData['existing_provider'] ?? '') === 'microsoft' ? trim((string)($eventData['event_id'] ?? '')) : '';
            $endpoint = 'https://graph.microsoft.com/v1.0/me/events' . ($existingId !== '' ? '/' . rawurlencode($existingId) : '');
            $created = calendar_http_request($endpoint, [
                'method' => $existingId !== '' ? 'PATCH' : 'POST',
                'headers' => ['Authorization: Bearer ' . $token['access_token'], 'Content-Type: application/json', 'Prefer: outlook.timezone="SA Pacific Standard Time"'],
                'body' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return [
                'provider' => 'microsoft',
                'event_id' => $created['id'] ?? null,
                'meeting_url' => $created['onlineMeeting']['joinUrl'] ?? null,
                'web_url' => $created['webLink'] ?? null,
            ];
        }

        throw new RuntimeException('No hay un proveedor de calendario conectado.');
    }
}
