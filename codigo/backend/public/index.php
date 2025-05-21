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

//Endpoint para iniciar sesión
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


// Endpoint para registrar un nuevo usuario
$app->post('/api/signup', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $correo = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $rol = $data['rol'] ?? 'usuario';

    if (!$correo || !$password) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Correo y contraseña son obligatorios'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $mysqli = getMySQLi();

    // Verifica si el correo ya existe
    $stmt = $mysqli->prepare("SELECT id_usuario FROM usuario WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'El correo ya está registrado'
        ]));
        $stmt->close();
        $mysqli->close();
        return $response->withHeader('Content-Type', 'application/json');
    }
    $stmt->close();

    // Inserta el usuario (hasheando la contraseña)
    $hash = hash('sha256', $password);
    $stmt = $mysqli->prepare("INSERT INTO usuario (correo, contraseña, rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $correo, $hash, $rol);

    if ($stmt->execute()) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Usuario registrado correctamente'
        ]));
    } else {
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Error al registrar usuario'
        ]));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/perfil/{id_usuario}', function ($request, $response, $args) {
    $id_usuario = $args['id_usuario'];
    $mysqli = getMySQLi();

    $stmt = $mysqli->prepare("SELECT * FROM perfil_usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $perfil = $result->fetch_assoc();

    if ($perfil) {
        $response->getBody()->write(json_encode(['exists' => true, 'perfil' => $perfil]));
    } else {
        $response->getBody()->write(json_encode(['exists' => false]));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/perfil', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $id_usuario = $data['id_usuario'];
    $nombre = $data['nombre'];
    $apellido = $data['apellido'];
    $pronombres = $data['pronombres'];
    $fecha_nacimiento = $data['fecha_nacimiento'];
    $biografia = $data['biografia'];

    $mysqli = getMySQLi();

    // Verifica si ya existe perfil
    $stmt = $mysqli->prepare("SELECT id_usuario FROM perfil_usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Si existe, actualiza
        $stmt->close();
        $stmt = $mysqli->prepare("UPDATE perfil_usuario SET nombre=?, apellido=?, pronombres=?, fecha_nacimiento=?, biografia=? WHERE id_usuario=?");
        $stmt->bind_param("sssssi", $nombre, $apellido, $pronombres, $fecha_nacimiento, $biografia, $id_usuario);
        $success = $stmt->execute();
    } else {
        // Si no existe, inserta
        $stmt->close();
        $stmt = $mysqli->prepare("INSERT INTO perfil_usuario (id_usuario, nombre, apellido, pronombres, fecha_nacimiento, biografia) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $id_usuario, $nombre, $apellido, $pronombres, $fecha_nacimiento, $biografia);
        $success = $stmt->execute();
    }

    if ($success) {
        $response->getBody()->write(json_encode(['success' => true]));
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Error al guardar perfil']));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();