<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/db.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;

$app = AppFactory::create();
$app->addBodyParsingMiddleware();

// Endpoint de prueba de conexión
$app->get('/api/db-test', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    if ($mysqli->ping()) {
        $data = ['success' => true, 'message' => 'Conexión exitosa a la base de datos'];
    } else {
        $data = ['success' => false, 'message' => 'No se pudo conectar a la base de datos'];
    }
    $mysqli->close();
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/login', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $correo = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $mysqli = getMySQLi();

    // Prepara la consulta para evitar SQL Injection
    $stmt = $mysqli->prepare("SELECT id_usuario, contraseña, rol FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    error_log("Correo recibido: $correo");
    error_log("Password recibido: $password");
    if ($user) {
        error_log("Hash PHP: " . hash('sha256', $password));
        error_log("Hash DB: " . $user['contraseña']);
    } else {
        error_log("Usuario no encontrado");
    }

    // Compara el hash de la contraseña ingresada con la almacenada
    if ($user && hash('sha256', $password) === $user['contraseña']) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login exitoso',
            'rol' => $user['rol'],
            'id_usuario' => $user['id_usuario'],
            // Solo para depuración:
            'hash_php' => hash('sha256', $password),
            'hash_db' => $user['contraseña'],
            'correo' => $correo,
            'password' => $password
        ]));
    } else {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos',
            // Solo para depuración:
            'hash_php' => hash('sha256', $password),
            'hash_db' => $user['contraseña'] ?? null,
            'correo' => $correo,
            'password' => $password
        ]));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});


$app->run();