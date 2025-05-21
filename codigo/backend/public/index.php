<?php
// filepath: c:\xampp\htdocs\tecweb-project\codigo\backend\public\index.php

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

$app->run();