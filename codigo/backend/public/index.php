<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/db.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\BodyParsingMiddleware;
use Slim\Psr7\Factory\StreamFactory;

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
    $sql = "SELECT b.id_blog, b.comentario, b.fecha_publicacion, 
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

// Obtener todos los usuarios
$app->get('/api/usuarios', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $result = $mysqli->query("SELECT id_usuario, correo, rol FROM usuario");
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    $mysqli->close();
    $response->getBody()->write(json_encode($usuarios));
    return $response->withHeader('Content-Type', 'application/json');
});

// Eliminar usuario por ID
$app->delete('/api/usuarios/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $mysqli = getMySQLi();
    $stmt = $mysqli->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    $stmt->close();
    $mysqli->close();
    $response->getBody()->write(json_encode(['success' => $success]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Eliminar comentario del blog por ID
$app->delete('/api/blog/{id_blog}', function ($request, $response, $args) {
    $id_blog = $args['id_blog'];
    $mysqli = getMySQLi();
    $stmt = $mysqli->prepare("DELETE FROM blog WHERE id_blog = ?");
    $stmt->bind_param("i", $id_blog);
    $success = $stmt->execute();
    $stmt->close();
    $mysqli->close();
    $response->getBody()->write(json_encode(['success' => $success]));
    return $response->withHeader('Content-Type', 'application/json');
});

///////ENDPOINTS DE DASHBOARD///////
// Total de usuarios
$app->get('/api/dashboard/usuarios', function ($request, $response, $args) {
    $mysqli = getMySQLi();

    $query = "SELECT COUNT(*) as total FROM USUARIO";
    $result = $mysqli->query($query);

    if ($row = $result->fetch_assoc()) {
        $total = $row['total'];
    } else {
        $total = 0;
    }

    $mysqli->close();

    // Usa StreamFactory para crear el cuerpo de la respuesta
    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode(['total' => $total]));

    return $response->withHeader('Content-Type', 'application/json')->withBody($body);
});

// Total de comentarios
$app->get('/api/dashboard/comentarios', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $result = $mysqli->query("SELECT COUNT(*) as total FROM blog");
    $total = $result->fetch_assoc()['total'];
    $mysqli->close();
    $response->getBody()->write(json_encode(['total' => $total]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Usuarios por pronombres
$app->get('/api/dashboard/pronombres', function ($request, $response, $args) {
    $mysqli = getMySQLi();

    $query = "SELECT pronombres, COUNT(*) as total FROM PERFIL_USUARIO GROUP BY pronombres ORDER BY total DESC";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['pronombres'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();

    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode(['labels' => $labels, 'values' => $values]));

    return $response->withHeader('Content-Type', 'application/json')->withBody($body);
});

// Usuarios vs Administradores
$app->get('/api/dashboard/roles', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $result = $mysqli->query("SELECT rol, COUNT(*) as total FROM usuario GROUP BY rol");
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = ucfirst($row['rol']);
        $values[] = $row['total'];
    }
    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $labels, 'values' => $values]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Actividad del foro últimos 5 días
$app->get('/api/dashboard/foro-actividad', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $result = $mysqli->query("
        SELECT DATE(fecha_publicacion) as fecha, COUNT(*) as total
        FROM blog
        WHERE fecha_publicacion >= DATE_SUB(CURDATE(), INTERVAL 4 DAY)
        GROUP BY fecha
        ORDER BY fecha ASC
    ");
    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['fecha'];
        $values[] = $row['total'];
    }
    // Asegura que estén los últimos 5 días aunque no haya actividad
    $dias = [];
    for ($i = 4; $i >= 0; $i--) {
        $dias[] = date('Y-m-d', strtotime("-$i days"));
    }
    $finalLabels = [];
    $finalValues = [];
    foreach ($dias as $dia) {
        $key = array_search($dia, $labels);
        $finalLabels[] = $dia;
        $finalValues[] = $key !== false ? $values[$key] : 0;
    }
    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $finalLabels, 'values' => $finalValues]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Estados más frecuentes
$app->get('/api/dashboard/estados', function ($request, $response, $args) {
    $mysqli = getMySQLi();

    $query = "SELECT nombre_estado, COUNT(*) as total FROM ESTADOS GROUP BY nombre_estado ORDER BY total DESC";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['nombre_estado'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();

    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode(['labels' => $labels, 'values' => $values]));

    return $response->withHeader('Content-Type', 'application/json')->withBody($body);
});

$app->get('/api/dashboard/activos', function ($request, $response, $args) {
    $mysqli = getMySQLi();

    $query = "SELECT USUARIO.correo, COUNT(BLOG.id_blog) as total FROM BLOG INNER JOIN USUARIO ON BLOG.id_usuario = USUARIO.id_usuario GROUP BY BLOG.id_usuario ORDER BY total DESC LIMIT 5";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];

    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['correo'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();

    $streamFactory = new StreamFactory();
    $body = $streamFactory->createStream(json_encode(['labels' => $labels, 'values' => $values]));

    return $response->withHeader('Content-Type', 'application/json')->withBody($body);
});

$app->get('/api/dashboard/crecimiento', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $query = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, COUNT(*) as total FROM USUARIO GROUP BY mes ORDER BY mes ASC";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['mes'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $labels, 'values' => $values]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/dashboard/edades', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $query = "
        SELECT
            CASE
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 18 AND 25 THEN '18-25'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 26 AND 35 THEN '26-35'
                WHEN TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE()) BETWEEN 36 AND 45 THEN '36-45'
                ELSE '46+'
            END as rango_edad,
            COUNT(*) as total
        FROM PERFIL_USUARIO
        GROUP BY rango_edad";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['rango_edad'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $labels, 'values' => $values]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/', function ($request, $response, $args) {
    $response->getBody()->write(json_encode([
        'success' => true,
        'message' => 'Bienvenido a la API de Plantasia'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/dashboard/quiz-preguntas', function ($request, $response, $args) {
    $mysqli = getMySQLi();
    $query = "
        SELECT
            'Pregunta 1' as pregunta, SUM(pregunta_1) as si, COUNT(*) - SUM(pregunta_1) as no FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 2', SUM(pregunta_2), COUNT(*) - SUM(pregunta_2) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 3', SUM(pregunta_3), COUNT(*) - SUM(pregunta_3) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 4', SUM(pregunta_4), COUNT(*) - SUM(pregunta_4) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 5', SUM(pregunta_5), COUNT(*) - SUM(pregunta_5) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 6', SUM(pregunta_6), COUNT(*) - SUM(pregunta_6) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 7', SUM(pregunta_7), COUNT(*) - SUM(pregunta_7) FROM RESPUESTAS_QUIZ
        UNION ALL
        SELECT
            'Pregunta 8', SUM(pregunta_8), COUNT(*) - SUM(pregunta_8) FROM RESPUESTAS_QUIZ";
    $result = $mysqli->query($query);

    $labels = [];
    $si = [];
    $no = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['pregunta'];
        $si[] = (int) $row['si'];
        $no[] = (int) $row['no'];
    }

    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $labels, 'si' => $si, 'no' => $no]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/dashboard/configuracion', function ($request, $response, $args) {
    session_start();

    // Verifica si el usuario es administrador
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        $response->getBody()->write(json_encode(['error' => 'Acceso denegado']));
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    // Configuración de las gráficas (puedes guardar esto en la base de datos)
    $configuracion = [
        'estadoChart' => true,
        'pronombresChart' => true,
        'activosChart' => true,
        'foroActividadChart' => true,
        'rolesChart' => true,
        'crecimientoChart' => true,
        'edadesChart' => true,
        'quizPreguntasChart' => true
    ];

    $response->getBody()->write(json_encode($configuracion));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/dashboard/configuracion', function ($request, $response, $args) {
    session_start();

    // Verifica si el usuario es administrador
    if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['error' => 'Acceso denegado']));
    }

    // Obtén los datos enviados desde el frontend
    $data = json_decode($request->getBody(), true);

    // Aquí puedes guardar la configuración en la base de datos o en un archivo
    // Por simplicidad, lo guardaremos en una variable de sesión
    $_SESSION['configuracion'] = $data;

    return $response->withHeader('Content-Type', 'application/json')
                    ->write(json_encode(['success' => true]));
});

$app->get('/api/dashboard/estado', function ($request, $response, $args) {
    session_start();

    // Verifica si la gráfica está habilitada
    if (isset($_SESSION['configuracion']['estadoChart']) && !$_SESSION['configuracion']['estadoChart']) {
        return $response->withStatus(403)->withHeader('Content-Type', 'application/json')
                        ->write(json_encode(['error' => 'Gráfica deshabilitada']));
    }

    // Código para devolver los datos de la gráfica
    $mysqli = getMySQLi();
    $query = "SELECT nombre_estado, COUNT(*) as total FROM ESTADOS GROUP BY nombre_estado ORDER BY total DESC";
    $result = $mysqli->query($query);

    $labels = [];
    $values = [];
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['nombre_estado'];
        $values[] = (int) $row['total'];
    }

    $mysqli->close();
    $response->getBody()->write(json_encode(['labels' => $labels, 'values' => $values]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();