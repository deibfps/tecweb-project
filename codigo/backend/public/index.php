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

$app = AppFactory::create();

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

    // Compara el hash de la contraseña ingresada con la almacenada
    if ($user && hash('sha256', $password) === $user['contraseña']) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Login exitoso',
            'rol' => $user['rol'],
            'id_usuario' => $user['id_usuario']
        ]));
    } else {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos'
        ]));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});


$app->run();