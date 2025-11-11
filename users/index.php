<?php
session_start();
require_once '../includes/auth-check.php';
verificarGestionUsuarios();
require_once '../includes/conexion.php';
include '../includes/header.php';

$usuarios = mysqli_query($conexion, "SELECT * FROM usuarios ORDER BY fecha_creacion DESC");
?>

<div class="dashboard-header admin-header">
    <h1>👥 Gestión de Usuarios</h1>
    <p>Listado completo de usuarios del sistema</p>
</div>

<a href="agregar.php" class="action-btn">➕ Agregar Usuario</a>

<table class="data-table">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Usuario</th>
            <th>Cargo</th>
            <th>Permisos</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($u = mysqli_fetch_assoc($usuarios)): ?>
        <tr>
            <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
            <td><?= htmlspecialchars($u['usuario_login']) ?></td>
            <td><?= htmlspecialchars($u['cargo']) ?></td>
            <td><?= $u['permisos'] ?></td>
            <td><?= $u['estado'] ?></td>
            <td>
                <a href="editar.php?id=<?= $u['id_usuario'] ?>">✏️</a>
                <a href="eliminar.php?id=<?= $u['id_usuario'] ?>" onclick="return confirm('¿Eliminar este usuario?')">🗑️</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../includes/footer.php'; ?>