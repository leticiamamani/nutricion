<?php
/**
 * Archivo de verificación de autenticación
 * Protege las páginas que requieren login
 */

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario NO está logueado
if (!isset($_SESSION['id_usuario'])) {
    // Redirigir al login si no hay sesión activa
    header('Location: ../auth/login.php');
    exit();
}

// Obtener el rol del usuario
$rol = $_SESSION['permisos'];

/**
 * Funciones de verificación de permisos por módulo
 */

// Atención Nutricional - Solo ADMIN y NUTRICION
function puedeAccederAtencionNutricional() {
    global $rol;
    return in_array($rol, ['ADMIN', 'NUTRICION']);
}

// Novedades - Todos los roles
function puedeAccederNovedades() {
    return true; // Todos los roles tienen acceso
}

// Huelga de Hambre - Solo ADMIN y NUTRICION
function puedeAccederHuelgaHambre() {
    global $rol;
    return in_array($rol, ['ADMIN', 'NUTRICION']);
}

// Ingreso Nutricional - Solo ADMIN y NUTRICION
function puedeAccederIngresoNutricional() {
    global $rol;
    return in_array($rol, ['ADMIN', 'NUTRICION']);
}

// Distribución Alimentos - ADMIN y GUARDIA
function puedeAccederDistribucionAlimentos() {
    global $rol;
    return in_array($rol, ['ADMIN', 'GUARDIA']);
}

// Recepción Alimentos - ADMIN y GUARDIA
function puedeAccederRecepcionAlimentos() {
    global $rol;
    return in_array($rol, ['ADMIN', 'GUARDIA']);
}

// Entrega Productos - ADMIN y GUARDIA
function puedeAccederEntregaProductos() {
    global $rol;
    return in_array($rol, ['ADMIN', 'GUARDIA']);
}

// Reportes - ADMIN, NUTRICION y ADMINISTRATIVO
function puedeAccederReportes() {
    global $rol;
    return in_array($rol, ['ADMIN', 'NUTRICION', 'ADMINISTRATIVO']);
}

// Gestión de Usuarios - Solo ADMIN
function puedeAccederGestionUsuarios() {
    global $rol;
    return $rol === 'ADMIN';
}

/**
 * Funciones de redirección para cada módulo
 */
function verificarAtencionNutricional() {
    if (!puedeAccederAtencionNutricional()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarNovedades() {
    // Todos tienen acceso, no necesita verificación
}

function verificarHuelgaHambre() {
    if (!puedeAccederHuelgaHambre()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarIngresoNutricional() {
    if (!puedeAccederIngresoNutricional()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarDistribucionAlimentos() {
    if (!puedeAccederDistribucionAlimentos()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarRecepcionAlimentos() {
    if (!puedeAccederRecepcionAlimentos()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarEntregaProductos() {
    if (!puedeAccederEntregaProductos()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarReportes() {
    if (!puedeAccederReportes()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}

function verificarGestionUsuarios() {
    if (!puedeAccederGestionUsuarios()) {
        header('Location: ../auth/dashboard.php');
        exit();
    }
}
?>