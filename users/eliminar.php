<?php
session_start();
require_once '../includes/auth-check.php';
verificarGestionUsuarios();
require_once '../includes/conexion.php';

$id = intval($_GET['id']);
mysqli_query($conexion, "DELETE FROM usuarios WHERE id_usuario = $id");
header("Location: index.php");
exit();