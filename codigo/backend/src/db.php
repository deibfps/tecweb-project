<?php
// filepath: c:\xampp\htdocs\tecweb-project\codigo\backend\src\db.php

function getMySQLi() {
    $host = 'localhost';
    $user = 'root'; // Cambia si usas otro usuario
    $pass = 'Cande02022004';     // Cambia si tienes contraseña
    $db   = 'tecweb'; // Cambia por el nombre real

    $mysqli = new mysqli($host, $user, $pass, $db);

    if ($mysqli->connect_error) {
        die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
    }

    return $mysqli;
}