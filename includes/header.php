<?php
/**
 * includes/header.php - Encabezado común con detección dinámica de rutas
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
$usuarioLogueado = isset($_SESSION['id_usuario']);
$rol = $_SESSION['permisos'] ?? '';
$nombreUsuario = $_SESSION['nombre_usuario'] ?? '';

// Determinar la ruta base según la ubicación actual
$current_dir = dirname($_SERVER['PHP_SELF']);
$is_in_formularios = strpos($current_dir, '/formularios/') !== false;

if ($is_in_formularios) {
    $base_path = '../../';
} else {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Nutrición</title>

    <!-- CSS y Favicon con rutas dinámicas -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/style.css">
    <link rel="icon" href="<?php echo $base_path; ?>assets/img/favicon.ico" type="image/x-icon">

    <style>
    /* Correcciones para el layout */
    body {
        margin: 0;
        padding: 0;
        background-color: #f8f9fa;
    }
    
    .main-container {
        display: flex;
        min-height: calc(100vh - 70px);
    }
    
    .compact-menu {
        width: 200px;
        background: white;
        border-right: 2px solid #e0e0e0;
        height: calc(100vh - 70px);
        position: sticky;
        top: 70px;
        overflow-y: auto;
        flex-shrink: 0;
    }
    
    .main-content {
        flex: 1;
        background-color: #f8f9fa;
        overflow-y: auto;
        height: calc(100vh - 70px);
        padding: 0;
    }
    
    html, body {
        height: 100%;
    }
    </style>
</head>
<body>

    <!-- Barra superior completa -->
    <header class="top-header-full">
        <div class="header-content">
            <div class="system-info">
                <h1>Sistema de Nutrición</h1>
                <p class="system-subtitle">Servicio Penitenciario Provincial</p>
            </div>
            <div class="user-info-full">
                <span class="user-avatar-small">👤</span>
                <div class="user-details-compact">
                    <span class="user-name"><?php echo htmlspecialchars($nombreUsuario); ?></span>
                    <span class="user-role">
                        <?php 
                        $roleIcons = [
                            'ADMIN' => '👩‍⚕️ Administradora',
                            'NUTRICION' => '👩‍⚕️ Nutricionista',
                            'ADMINISTRATIVO' => '👩‍💼 Administrativo',
                            'GUARDIA' => '👮‍♀️ Guardia'
                        ];
                        echo $roleIcons[$rol] ?? $rol;
                        ?>
                    </span>
                </div>
                <a href="<?php echo $base_path; ?>auth/logout.php" class="logout-btn-small">🚪 Salir</a>
            </div>
        </div>
    </header>

    <div class="main-container">
        <!-- Menú vertical lateral compacto -->
        <nav class="compact-menu">
            <ul class="compact-menu-list">
                <li>
                    <a href="<?php echo $base_path; ?>auth/dashboard.php" class="compact-menu-link">
                        <span class="menu-icon">🏠</span>
                        <span class="menu-text">Inicio</span>
                    </a>
                </li>

                <!-- Atención Nutricional - Solo ADMIN y NUTRICION -->
                <?php if ($rol === 'ADMIN' || $rol === 'NUTRICION'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/atencion-nutricional/index.php" class="compact-menu-link">
                        <span class="menu-icon">🏥</span>
                        <span class="menu-text">Atenciones</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Huelga de Hambre - Solo ADMIN y NUTRICION -->
                <?php if ($rol === 'ADMIN' || $rol === 'NUTRICION'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/huelga-hambre/index.php" class="compact-menu-link">
                        <span class="menu-icon">🚫</span>
                        <span class="menu-text">Huelgas Hambre</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Ingreso Nutricional - Solo ADMIN y NUTRICION -->
                <?php if ($rol === 'ADMIN' || $rol === 'NUTRICION'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/ingreso-nutricional/index.php" class="compact-menu-link">
                        <span class="menu-icon">👤</span>
                        <span class="menu-text">Ingresos</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Distribución Alimentos - ADMIN y GUARDIA -->
                <?php if ($rol === 'ADMIN' || $rol === 'GUARDIA'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/distribucion-alimentos/index.php" class="compact-menu-link">
                        <span class="menu-icon">📦</span>
                        <span class="menu-text">Distribución</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Recepción Alimentos - ADMIN y GUARDIA -->
                <?php if ($rol === 'ADMIN' || $rol === 'GUARDIA'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/recepcion-alimentos/index.php" class="compact-menu-link">
                        <span class="menu-icon">🏭</span>
                        <span class="menu-text">Recepción</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Entrega Productos - ADMIN y GUARDIA -->
                <?php if ($rol === 'ADMIN' || $rol === 'GUARDIA'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>formularios/entrega-productos/index.php" class="compact-menu-link">
                        <span class="menu-icon">📦</span>
                        <span class="menu-text">Entregas</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Novedades - Todos los roles -->
                <li>
                    <a href="<?php echo $base_path; ?>formularios/novedades/index.php" class="compact-menu-link">
                        <span class="menu-icon">⚠️</span>
                        <span class="menu-text">Novedades</span>
                    </a>
                </li>

                

                <!-- Gestión de Usuarios - Solo ADMIN -->
                <?php if ($rol === 'ADMIN'): ?>
                <li>
                    <a href="<?php echo $base_path; ?>users/index.php" class="compact-menu-link">
                        <span class="menu-icon">👥</span>
                        <span class="menu-text">Usuarios</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Contenido principal -->
        <main class="main-content">