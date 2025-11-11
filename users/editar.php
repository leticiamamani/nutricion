<?php
session_start();
require_once '../includes/auth-check.php';
verificarGestionUsuarios();
require_once '../includes/conexion.php';

$id = intval($_GET['id']);
$usuario = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM usuarios WHERE id_usuario = $id"));

if (!$usuario) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_usuario']);
    $cargo = mysqli_real_escape_string($conexion, $_POST['cargo']);
    $area = mysqli_real_escape_string($conexion, $_POST['area']);
    $permisos = $_POST['permisos'];
    $estado = $_POST['estado'];

    $sql = "UPDATE usuarios SET 
                nombre_usuario='$nombre', 
                cargo='$cargo', 
                area='$area', 
                permisos='$permisos', 
                estado='$estado' 
            WHERE id_usuario=$id";

    if (mysqli_query($conexion, $sql)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Error al actualizar el usuario.";
    }
}

include '../includes/header.php';
?>

<style>
    .form-container {
        max-width: 600px;
        margin: 30px auto;
        background: #ffffff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .form-container h2 {
        margin-bottom: 20px;
        font-size: 1.4rem;
        color: #000;
        font-weight: 700;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 6px;
        font-weight: 600;
        color: #333;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        background-color: #f9f9f9;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        border-color: #000;
        outline: none;
        background-color: #fff;
    }

    .btn-save {
        background-color: #000;
        color: #fff;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    .btn-save:hover {
        background-color: #333;
    }

    .btn-cancel {
        background-color: #e0e0e0;
        color: #000;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
        text-decoration: none;
        display: inline-block;
    }

    .btn-cancel:hover {
        background-color: #ccc;
    }

    .form-actions {
        text-align: right;
        margin-top: 30px;
    }
</style>

<div class="dashboard-header admin-header">
    <h1>✏️ Editar Usuario</h1>
    <p>Modificá los datos del usuario seleccionado</p>
</div>

<div class="form-container">
    <?php if (!empty($error)): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nombre completo:</label>
            <input type="text" name="nombre_usuario" value="<?= htmlspecialchars($usuario['nombre_usuario']) ?>" required>
        </div>

        <div class="form-group">
            <label>Cargo:</label>
            <input type="text" name="cargo" value="<?= htmlspecialchars($usuario['cargo']) ?>" required>
        </div>

        <div class="form-group">
            <label>Área:</label>
            <input type="text" name="area" value="<?= htmlspecialchars($usuario['area']) ?>" required>
        </div>

        <div class="form-group">
            <label>Rol:</label>
            <select name="permisos" required>
                <option value="ADMIN" <?= $usuario['permisos'] === 'ADMIN' ? 'selected' : '' ?>>Administrador</option>
                <option value="NUTRICION" <?= $usuario['permisos'] === 'NUTRICION' ? 'selected' : '' ?>>Nutricionista</option>
                <option value="ADMINISTRATIVO" <?= $usuario['permisos'] === 'ADMINISTRATIVO' ? 'selected' : '' ?>>Administrativo</option>
                <option value="GUARDIA" <?= $usuario['permisos'] === 'GUARDIA' ? 'selected' : '' ?>>Guardia</option>
            </select>
        </div>

        <div class="form-group">
            <label>Estado:</label>
            <select name="estado" required>
                <option value="ACTIVO" <?= $usuario['estado'] === 'ACTIVO' ? 'selected' : '' ?>>Activo</option>
                <option value="INACTIVO" <?= $usuario['estado'] === 'INACTIVO' ? 'selected' : '' ?>>Inactivo</option>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save">💾 Guardar Cambios</button>
            <a href="index.php" class="btn-cancel">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>