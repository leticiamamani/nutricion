<?php
session_start();
require_once '../includes/auth-check.php';
verificarGestionUsuarios();
require_once '../includes/conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_usuario']);
    $usuario = mysqli_real_escape_string($conexion, $_POST['usuario_login']);
    $cargo = mysqli_real_escape_string($conexion, $_POST['cargo']);
    $area = mysqli_real_escape_string($conexion, $_POST['area']);
    $permisos = $_POST['permisos'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre_usuario, usuario_login, cargo, area, permisos, password_hash, fecha_alta)
            VALUES ('$nombre', '$usuario', '$cargo', '$area', '$permisos', '$password', CURDATE())";
    if (mysqli_query($conexion, $sql)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Error al crear usuario.";
    }
}

include '../includes/header.php';
?>

<div class="dashboard-header admin-header">
    <h1>➕ Agregar Usuario</h1>
</div>

<?php if ($error): ?>
    <div class="alert error"><?= $error ?></div>
<?php endif; ?>

<form method="POST">
    <label>Nombre completo:</label>
    <input type="text" name="nombre_usuario" required>

    <label>Usuario de acceso:</label>
    <input type="text" name="usuario_login" required>

    <label>Cargo:</label>
    <input type="text" name="cargo" required>

    <label>Área:</label>
    <input type="text" name="area" required>

    <label>Rol:</label>
    <select name="permisos" required>
        <option value="ADMIN">Administrador</option>
      <?php
session_start();
require_once '../includes/auth-check.php';
verificarGestionUsuarios();
require_once '../includes/conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = mysqli_real_escape_string($conexion, $_POST['nombre_usuario']);
    $usuario = mysqli_real_escape_string($conexion, $_POST['usuario_login']);
    $cargo = mysqli_real_escape_string($conexion, $_POST['cargo']);
    $area = mysqli_real_escape_string($conexion, $_POST['area']);
    $permisos = $_POST['permisos'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre_usuario, usuario_login, cargo, area, permisos, password_hash, fecha_alta)
            VALUES ('$nombre', '$usuario', '$cargo', '$area', '$permisos', '$password', CURDATE())";

    if (mysqli_query($conexion, $sql)) {
        header("Location: index.php");
        exit();
    } else {
        $error = "Error al crear el usuario.";
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
        font-weight: 600;
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
        font-weight: 600;
        transition: background-color 0.3s;
    }

    .btn-cancel:hover {
        background-color: #ccc;
    }

    .form-actions {
        text-align: right;
        margin-top: 30px;
    }

    .alert-error {
        background-color: #f8d7da;
        color: #842029;
        border: 1px solid #f5c6cb;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 20px;
    }
</style>

<div class="dashboard-header admin-header">
    <h1>➕ Agregar Usuario</h1>
    <p>Completa el formulario para crear un nuevo usuario del sistema</p>
</div>

<div class="form-container">
    <?php if (!empty($error)): ?>
        <div class="alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nombre completo:</label>
            <input type="text" name="nombre_usuario" required>
        </div>

        <div class="form-group">
            <label>Usuario de acceso:</label>
            <input type="text" name="usuario_login" required>
        </div>

        <div class="form-group">
            <label>Cargo:</label>
            <input type="text" name="cargo" required>
        </div>

        <div class="form-group">
            <label>Área:</label>
            <input type="text" name="area" required>
        </div>

        <div class="form-group">
            <label>Rol:</label>
            <select name="permisos" required>
                <option value="">Seleccionar rol</option>
                <option value="ADMIN">Administrador</option>
                <option value="NUTRICION">Nutricionista</option>
                <option value="ADMINISTRATIVO">Administrativo</option>
                <option value="GUARDIA">Guardia</option>
            </select>
        </div>

        <div class="form-group">
            <label>Contraseña:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-save">💾 Crear Usuario</button>
            <a href="index.php" class="btn-cancel">❌ Cancelar</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>  <option value="NUTRICION">Nutricionista</option>
        <option value="ADMINISTRATIVO">Administrativo</option>
        <option value="GUARDIA">Guardia</option>
    </select>

    <label>Contraseña:</label>
    <input type="password" name="password" required>

    <button type="submit" class="btn-login">Guardar Usuario</button>
</form>

<?php include '../includes/footer.php'; ?>