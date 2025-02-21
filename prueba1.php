<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';

// Función para validar el token
function validarToken()
{
    // Verificar si existe la cookie con el token
    if (!isset($_COOKIE['jwt_token'])) {
        return false;
    }

    try {
        $token = $_COOKIE['jwt_token'];
        $key = 'PASSWORD_DE_MI_APLICACION'; // Debe ser la misma clave que usaste para crear el token

        // Decodificar y validar el token
        $decoded = JWT::decode($token, new Key($key, 'HS256'));

        // Verificar si el token ha expirado
        if ($decoded->exp < time()) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Verificar el token antes de mostrar el contenido
if (!validarToken()) {
    header('Content-Type: application/json');
    echo json_encode([
        "error" => "Acceso no autorizado",
        "message" => "Debes iniciar sesión primero"
    ]);
    exit;
}

// Si el token es válido, mostrar el contenido protegido
header('Content-Type: application/json');
echo json_encode([
    "message" => "¡Bienvenido! Tienes acceso a esta página",
    "data" => "Este es el contenido protegido"
]);
