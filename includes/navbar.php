<?php
/**
 * Barra de navegación superior
 * Opcional - puede usarse para información contextual
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- Barra de navegación superior (opcional) -->
<!-- 
<nav class="top-navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <span>Sistema de Nutrición Penitenciaria</span>
        </div>
        
        <div class="nav-user">
            <span class="user-name"><?php echo $_SESSION['nombre_usuario']; ?></span>
            <span class="user-role">(<?php echo $_SESSION['permisos']; ?>)</span>
            <a href="../auth/logout.php" class="logout-btn">Salir</a>
        </div>
    </div>
</nav>
-->