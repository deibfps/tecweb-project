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

// Endpoint para obtener el perfil de un usuario (si existe se muestra, si no existe se muestra el form)
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


//Endpoint para editar el perfil de un usuario
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


//Endpoint para agregar estado de residencia
$app->post('/api/estado', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $id_usuario = $data['id_usuario'];
    $nombre_estado = $data['nombre_estado'];

    $mysqli = getMySQLi();

    // Si ya existe, actualiza; si no, inserta
    $stmt = $mysqli->prepare("SELECT id_usuario FROM estados WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $mysqli->prepare("UPDATE estados SET nombre_estado=? WHERE id_usuario=?");
        $stmt->bind_param("si", $nombre_estado, $id_usuario);
        $success = $stmt->execute();
    } else {
        $stmt->close();
        $stmt = $mysqli->prepare("INSERT INTO estados (id_usuario, nombre_estado) VALUES (?, ?)");
        $stmt->bind_param("is", $id_usuario, $nombre_estado);
        $success = $stmt->execute();
    }

    if ($success) {
        $response->getBody()->write(json_encode(['success' => true]));
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Error al guardar estado']));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});

// Endpoint para obtener los 5 estados más frecuentes
$app->get('/api/estados/top', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $sql = "SELECT nombre_estado, COUNT(*) as total FROM estados GROUP BY nombre_estado ORDER BY total DESC LIMIT 5";
    $result = $mysqli->query($sql);

    $estados = [];
    while ($row = $result->fetch_assoc()) {
        $estados[] = $row;
    }
    $mysqli->close();
    $response->getBody()->write(json_encode($estados));
    return $response->withHeader('Content-Type', 'application/json');
});

// Guardar o actualizar respuestas del quiz
$app->post('/api/quiz', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $id_usuario = $data['id_usuario'];
    $respuestas = $data['respuestas']; // array de booleanos

    $mysqli = getMySQLi();

    // Verifica si ya existen respuestas
    $stmt = $mysqli->prepare("SELECT id_usuario FROM respuestas_quiz WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Actualiza
        $stmt->close();
        $stmt = $mysqli->prepare("UPDATE respuestas_quiz SET pregunta_1=?, pregunta_2=?, pregunta_3=?, pregunta_4=?, pregunta_5=?, pregunta_6=?, pregunta_7=?, pregunta_8=? WHERE id_usuario=?");
        $stmt->bind_param(
            "iiiiiiiii",
            $respuestas[0], $respuestas[1], $respuestas[2], $respuestas[3],
            $respuestas[4], $respuestas[5], $respuestas[6], $respuestas[7],
            $id_usuario
        );
        $success = $stmt->execute();
    } else {
        // Inserta
        $stmt->close();
        $stmt = $mysqli->prepare("INSERT INTO respuestas_quiz (id_usuario, pregunta_1, pregunta_2, pregunta_3, pregunta_4, pregunta_5, pregunta_6, pregunta_7, pregunta_8) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "iiiiiiiii",
            $id_usuario,
            $respuestas[0], $respuestas[1], $respuestas[2], $respuestas[3],
            $respuestas[4], $respuestas[5], $respuestas[6], $respuestas[7]
        );
        $success = $stmt->execute();
    }

    if ($success) {
        $response->getBody()->write(json_encode(['success' => true]));
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Error al guardar respuestas']));
    }
    $stmt->close();
    $mysqli->close();
    return $response->withHeader('Content-Type', 'application/json');
});

// Obtener respuestas del quiz
$app->get('/api/quiz/{id_usuario}', function ($request, $response, $args) {
    $id_usuario = $args['id_usuario'];
    $mysqli = getMySQLi();
    $stmt = $mysqli->prepare("SELECT * FROM respuestas_quiz WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $respuestas = $result->fetch_assoc();
    $stmt->close();
    $mysqli->close();

    if ($respuestas) {
        $response->getBody()->write(json_encode(['exists' => true, 'respuestas' => $respuestas]));
    } else {
        $response->getBody()->write(json_encode(['exists' => false]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

//Bloquea el acceso a contestar el estado de origen mas de una vez
$app->get('/api/estado/{id_usuario}', function ($request, $response, $args) {
    $id_usuario = $args['id_usuario'];
    $mysqli = getMySQLi();
    $stmt = $mysqli->prepare("SELECT nombre_estado FROM estados WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    $estado = $result->fetch_assoc();
    $stmt->close();
    $mysqli->close();

    if ($estado) {
        $response->getBody()->write(json_encode(['exists' => true, 'nombre_estado' => $estado['nombre_estado']]));
    } else {
        $response->getBody()->write(json_encode(['exists' => false]));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

// Obtener todos los comentarios del blog (más recientes primero)
$app->get('/api/blog', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $sql = "SELECT b.comentario, b.fecha_publicacion, 
                   IFNULL(p.nombre, u.correo) AS nombre, 
                   p.apellido, 
                   u.correo
            FROM blog b
            JOIN usuario u ON b.id_usuario = u.id_usuario
            LEFT JOIN perfil_usuario p ON u.id_usuario = p.id_usuario
            ORDER BY b.fecha_publicacion DESC";
    $result = $mysqli->query($sql);

    if ($result === false) {
        $error = $mysqli->error;
        $mysqli->close();
        $response->getBody()->write(json_encode(['success' => false, 'error' => $error]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $comentarios = [];
    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }
    $mysqli->close();
    $response->getBody()->write(json_encode($comentarios));
    return $response->withHeader('Content-Type', 'application/json');
});

// Agregar un comentario al blog
$app->post('/api/blog', function ($request, $response, $args) {
    $data = $request->getParsedBody();
    $id_usuario = $data['id_usuario'];
    $comentario = $data['comentario'];

    if (!$comentario || !$id_usuario) {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Faltan datos']));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $mysqli = getMySQLi();
    $stmt = $mysqli->prepare("INSERT INTO blog (id_usuario, comentario) VALUES (?, ?)");
    $stmt->bind_param("is", $id_usuario, $comentario);
    $success = $stmt->execute();
    $stmt->close();
    $mysqli->close();

    if ($success) {
        $response->getBody()->write(json_encode(['success' => true]));
    } else {
        $response->getBody()->write(json_encode(['success' => false, 'message' => 'Error al guardar comentario']));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();