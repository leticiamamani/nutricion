<?php
/**
 * Archivo de conexión a la base de datos
 * Configura los parámetros de conexión MySQL
 */

// Datos de conexión a la base de datos
$servidor = "localhost";
$usuario_bd = "root"; // Cambiar por tu usuario de MySQL
$password_bd = ""; // Cambiar por tu contraseña de MySQL
$base_datos = "nutricion";

// Crear conexión
$conexion = mysqli_connect($servidor, $usuario_bd, $password_bd, $base_datos);

// Verificar conexión
if (!$conexion) {
    die("Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Establecer el conjunto de caracteres a utf8
mysqli_set_charset($conexion, "utf8");

// Configurar zona horaria si es necesario
date_default_timezone_set('America/Argentina/Buenos_Aires');
?>