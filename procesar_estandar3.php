<?php
require_once 'config/db.php';
require_once 'config/auth.php';

// Exige sesión válida
$u = require_auth($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_actividad') {
    
    $empresa_id = $_SESSION['empresa_id'] ?? 1; // Ajusta según tu variable de sesión
    $nombre = trim($_POST['nombre_actividad']);
    $tipo = $_POST['tipo_capacitacion'];
    $categoria = $_POST['categoria'];
    $dirigido_a = $_POST['dirigido_a'];
    
    // Unificar fecha y hora en formato SQL (YYYY-MM-DD HH:MM:00)
    $inicio = $_POST['fecha_inicio'] . ' ' . $_POST['hora_inicio'] . ':00';
    $fin = $_POST['fecha_fin'] . ' ' . $_POST['hora_fin'] . ':00';
    
    try {
        $conn->beginTransaction();

        // 1. Guardar la actividad en la base de datos local
        $sql = "INSERT INTO actividades_capacitacion (empresa_id, nombre_actividad, tipo_capacitacion, categoria, dirigido_a, fecha_inicio, fecha_fin, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'programada')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$empresa_id, $nombre, $tipo, $categoria, $dirigido_a, $inicio, $fin]);
        $actividad_id = $conn->lastInsertId();

        // 2. Si es trabajador específico, guardar la relación en la tabla pivote
        if ($dirigido_a === 'Trabajador Específico' && isset($_POST['trabajadores_seleccionados'])) {
            $stmt_rel = $conn->prepare("INSERT INTO actividades_trabajadores (actividad_id, usuario_id) VALUES (?, ?)");
            foreach ($_POST['trabajadores_seleccionados'] as $id_trab) {
                $stmt_rel->execute([$actividad_id, $id_trab]);
            }
        }

        // ==========================================================
        // LA MAGIA: INTEGRACIÓN CON GOOGLE CALENDAR API Y MEET
        // ==========================================================
        
        // MODO DESARROLLADOR: Cambia esto a TRUE cuando hayas instalado Composer y descargado credentials.json
        $usar_api_google = false; 

        if ($usar_api_google && file_exists('vendor/autoload.php')) {
            require_once 'vendor/autoload.php';
            
            $client = new Google\Client();
            $client->setAuthConfig('credentials.json'); // El archivo que descargas de Google Cloud
            $client->addScope(Google\Service\Calendar::CALENDAR);
            
            // Asumimos que el usuario ya inició sesión con Google y guardaste su token
            if (isset($_SESSION['google_access_token'])) {
                $client->setAccessToken($_SESSION['google_access_token']);
                $service = new Google\Service\Calendar($client);
                
                $event = new Google\Service\Calendar\Event([
                  'summary' => 'SG-SST: ' . $nombre,
                  'description' => "Capacitación: $tipo\nCategoría: $categoria",
                  'start' => [
                    'dateTime' => date('c', strtotime($inicio)),
                    'timeZone' => 'America/Bogota', // Zona horaria de Colombia
                  ],
                  'end' => [
                    'dateTime' => date('c', strtotime($fin)),
                    'timeZone' => 'America/Bogota',
                  ],
                  // AQUÍ LE DECIMOS A GOOGLE QUE NOS CREE EL LINK DE MEET AUTOMÁTICO
                  'conferenceData' => [
                    'createRequest' => [
                      'requestId' => 'sg-sst-meet-' . $actividad_id,
                      'conferenceSolutionKey' => ['type' => 'hangoutsMeet']
                    ]
                  ]
                ]);
                
                // Insertar evento y pedir datos de conferencia (Meet)
                $createdEvent = $service->events->insert('primary', $event, ['conferenceDataVersion' => 1]);
                $enlace_meet = $createdEvent->getHangoutLink(); // ¡Obtenemos el link de Meet!
                
                // Si nos dio el link, lo actualizamos en nuestra base de datos
                if ($enlace_meet) {
                    $stmt_upd = $conn->prepare("UPDATE actividades_capacitacion SET enlace_reunion = ? WHERE id = ?");
                    $stmt_upd->execute([$enlace_meet, $actividad_id]);
                }
                
                $conn->commit();
                header('Location: estandar3.php?save=success');
                exit;
            }
        }
        
        // ==========================================================
        // MÉTODO TRADICIONAL (Mientras configuras la API)
        // ==========================================================
        $conn->commit(); // Confirmamos los datos locales

        // Convertimos fechas para la URL
        $google_start = date('Ymd\THis', strtotime($inicio));
        $google_end = date('Ymd\THis', strtotime($fin));
        $details = "Capacitación SG-SST: " . $tipo . " - Categoría: " . $categoria;
        
        $google_url = "https://www.google.com/calendar/render?action=TEMPLATE" .
                      "&text=" . urlencode("SG-SST: " . $nombre) .
                      "&dates=" . $google_start . "/" . $google_end .
                      "&details=" . urlencode($details) .
                      "&sf=true&output=xml";

        // Abrimos la pestaña para que el usuario añada la reunión manualmente por ahora
        echo "<script>
                window.open('$google_url', '_blank');
                window.location.href = 'estandar3.php?save=success';
              </script>";
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        // Redirigir con error
        header('Location: nueva_actividad.php?error=db_error');
        exit;
    }
}