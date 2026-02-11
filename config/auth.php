<?php
// config/auth.php

// Cookie settings (Secure solo si hay HTTPS)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

// Solo configura cookies ANTES de que la sesión esté activa
if (session_status() !== PHP_SESSION_ACTIVE) {

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function client_ip(): string
{
    // Si usas proxy/CDN, aquí se puede mejorar con headers confiables
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function user_agent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500);
}

function log_activity(PDO $conn, ?int $usuarioId, string $accion, ?string $descripcion = null): void
{
    try {
        $stmt = $conn->prepare("
            INSERT INTO logs_actividad (usuario_id, accion, descripcion, ip_address)
            VALUES (:uid, :accion, :desc, :ip)
        ");
        $stmt->execute([
            'uid' => $usuarioId,
            'accion' => $accion,
            'desc' => $descripcion,
            'ip' => client_ip(),
        ]);
    } catch (Throwable $e) {
        // No bloqueamos la app por logging
    }
}

function create_db_session(PDO $conn, array $usuario, int $hours = 8): void
{
    // Token plano (solo vive en cookie)
    $tokenPlain = bin2hex(random_bytes(32)); // 64 chars
    // Token hash (vive en BD)
    $tokenHash = hash('sha256', $tokenPlain);

    $exp = (new DateTimeImmutable('now'))->modify("+{$hours} hours")->format('Y-m-d H:i:s');

    // Inserta sesión en BD
    $stmt = $conn->prepare("
        INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, fecha_expiracion, activa)
        VALUES (:uid, :token, :ip, :ua, :exp, 1)
    ");
    $stmt->execute([
        'uid' => (int) $usuario['id'],
        'token' => $tokenHash,
        'ip' => client_ip(),
        'ua' => user_agent(),
        'exp' => $exp,
    ]);

    // Cookie segura
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    setcookie('auth_token', $tokenPlain, [
        'expires' => time() + ($hours * 3600),
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Hardening de sesión PHP
    session_regenerate_id(true);

    // Set básico en PHP session (para UI)
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? ''));
    $_SESSION['usuario_rol'] = $usuario['rol'] ?? '';
    $_SESSION['usuario_email'] = $usuario['email'] ?? '';
}

function auth_user(PDO $conn): ?array
{
    // Si ya está en sesión PHP y quieres confiar, puedes devolverlo.
    // Pero lo más seguro es validar por BD:
    $tokenPlain = $_COOKIE['auth_token'] ?? '';
    if ($tokenPlain === '')
        return null;

    $tokenHash = hash('sha256', $tokenPlain);

    $stmt = $conn->prepare("
        SELECT s.usuario_id, u.nombre, u.apellido, u.email, u.rol
        FROM sesiones s
        INNER JOIN usuarios u ON u.id = s.usuario_id
        WHERE s.token = :token
          AND s.activa = 1
          AND (s.fecha_expiracion IS NULL OR s.fecha_expiracion > NOW())
        LIMIT 1
    ");
    $stmt->execute(['token' => $tokenHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row)
        return null;

    // Refresca variables de sesión PHP para la UI
    $_SESSION['usuario_id'] = (int) $row['usuario_id'];
    $_SESSION['usuario_nombre'] = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''));
    $_SESSION['usuario_rol'] = $row['rol'] ?? '';
    $_SESSION['usuario_email'] = $row['email'] ?? '';

    return $row;
}

function require_auth(PDO $conn): array
{
    $u = auth_user($conn);
    if (!$u) {
        header('Location: login.php');
        exit;
    }
    return $u;
}

function revoke_session(PDO $conn): void
{
    $tokenPlain = $_COOKIE['auth_token'] ?? '';
    if ($tokenPlain !== '') {
        $tokenHash = hash('sha256', $tokenPlain);
        try {
            $stmt = $conn->prepare("UPDATE sesiones SET activa = 0 WHERE token = :t LIMIT 1");
            $stmt->execute(['t' => $tokenHash]);
        } catch (Throwable $e) {
        }
    }

    // Borra cookie
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    setcookie('auth_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // Limpia PHP session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
}
