<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

require 'vendor/autoload.php';
Flight::register('db', 'PDO', array('mysql:host=localhost;dbname=spending_tracker', 'root', ''), function ($db) {
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
});;
Flight::route('GET /users', function () {
    error_log("Iniciando consulta de usuarios");

    try {
        $db = Flight::db();
        error_log("Conexión a BD establecida");

        $query = $db->prepare("SELECT * FROM usuarios");
        $query->execute();
        error_log("Query ejecutado");

        $data = $query->fetchAll();
        error_log("Datos obtenidos: " . json_encode($data));

        $array = [];
        foreach ($data as $row) {
            $array[] = [
                "id" => $row['id'],
                "name" => $row['nombre'],
                "email" => $row['correo'],
                "phone" => $row['telefono'],
                "status" => $row['status'],
                "rol" => $row['rol_id'],
            ];
        }

        error_log("Array formateado: " . json_encode($array));

        $response = [
            "total_rows" => $query->rowCount(),
            "rows" => $array
        ];

        error_log("Enviando respuesta: " . json_encode($response));
        Flight::json($response);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        Flight::json([
            "error" => "Error al obtener usuarios",
            "message" => $e->getMessage()
        ]);
    }
});


Flight::route('GET /users/@id', function ($id) {
    $db = Flight::db();
    $query = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
    $query->execute([":id" => $id]);
    $data = $query->fetch();

    $array = [
        "id" => $data['id'],
        "name" => $data['nombre'],
        "email" => $data['correo'],
        "phone" => $data['telefono'],
        "status" => $data['status'],
        "rol" => $data['rol_id'],
    ];

    Flight::json($array);
});

Flight::route('POST /auth', function () {
    error_log("Iniciando autenticación");

    $db = Flight::db();
    $password = Flight::request()->data->password;
    $email = Flight::request()->data->email;

    error_log("Credenciales recibidas: " . $email);

    $query = $db->prepare("SELECT * FROM usuarios WHERE correo = :email AND password = :password");

    try {
        $query->execute([":email" => $email, ":password" => $password]);
        $user = $query->fetch();

        error_log("Usuario encontrado: " . json_encode($user));

        if ($user) {
            $now = strtotime("now");
            $key = 'PASSWORD_DE_MI_APLICACION';
            $payload = [
                'exp' => $now + 3600,
                'data' => $user['id']
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            error_log("Token generado: " . $jwt);

            // Establecer la cookie
            setcookie('jwt_token', $jwt, [
                'expires' => time() + 3600,    // Expira en 1 hora
                'path' => '/',                 // Disponible en todo el sitio
                'httponly' => true,            // No accesible por JavaScript
                'secure' => false,              // Solo enviar por HTTPS
                'samesite' => 'Strict'         // Protección contra CSRF
            ]);

            Flight::json([
                "message" => "Autenticación exitosa",
                "token" => $jwt
            ]);
        } else {
            error_log("Usuario no encontrado");
            Flight::json([
                "error" => "Usuario o contraseña incorrectos",
                "status" => "error"
            ]);
        }
    } catch (PDOException $e) {
        error_log("Error de BD: " . $e->getMessage());
        Flight::json([
            "error" => "Error en la base de datos: " . $e->getMessage(),
            "status" => "error"
        ]);
    }
});

Flight::start();
