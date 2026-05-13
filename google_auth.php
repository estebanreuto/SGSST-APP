<?php
// Asegúrate de que tu sesión esté iniciada y la base de datos conectada
require_once 'config/db.php'; 
session_start();

// Cargar la librería de Google (Asumiendo que ya corriste 'composer require google/apiclient')
require_once 'vendor/autoload.php';

$client = new Google\Client();
// Lee el archivo que acabas de descargar de Google Cloud
$client->setAuthConfig(__DIR__ . '/credentials.json'); 
// Pedimos permiso para gestionar el Calendario
$client->addScope(Google\Service\Calendar::CALENDAR);

// IMPORTANTE: Esta URL debe ser EXACTAMENTE la misma que pusiste en "URIs de redireccionamiento"
$client->setRedirectUri('https://preventwork.vertixtecnosas.com.co/google_auth.php');

$client->setAccessType('offline');
$client->setPrompt('select_account consent');

// Si Google nos devuelve un código (el usuario aceptó)
if (isset($_GET['code'])) {
    // Intercambiamos el código por un Token real
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Guardamos el token en la sesión de PHP para usarlo al guardar la actividad
    $_SESSION['google_access_token'] = $token;

    // Lo devolvemos al formulario de nueva actividad
    header('Location: nueva_actividad.php');
    exit;
} else {
    // Si no hay código, generamos la URL de Google y enviamos al usuario a iniciar sesión
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
}