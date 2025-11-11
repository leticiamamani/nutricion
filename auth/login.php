<?php
// Iniciar sesión
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['id_usuario'])) {
    header('Location: dashboard.php');
    exit();
}

// Incluir conexión a base de datos
require_once '../includes/conexion.php';

$error = '';

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario_login = mysqli_real_escape_string($conexion, $_POST['usuario_login']);
    $password = $_POST['password'];

    // Consulta para buscar usuario activo
    $sql = "SELECT id_usuario, nombre_usuario, usuario_login, password_hash, permisos 
            FROM usuarios 
            WHERE usuario_login = '$usuario_login' AND estado = 'ACTIVO'";
    $resultado = mysqli_query($conexion, $sql);
    
    if ($resultado && mysqli_num_rows($resultado) == 1) {
        $fila = mysqli_fetch_assoc($resultado);
        
        // Verificar contraseña
        if (password_verify($password, $fila['password_hash'])) {
            // Crear variables de sesión
            $_SESSION['id_usuario'] = $fila['id_usuario'];
            $_SESSION['nombre_usuario'] = $fila['nombre_usuario'];
            $_SESSION['permisos'] = $fila['permisos'];
            $_SESSION['usuario_login'] = $fila['usuario_login'];
            
            // Actualizar último acceso
            $update_sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = " . $fila['id_usuario'];
            mysqli_query($conexion, $update_sql);
            
            // Redirigir al DASHBOARD
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Nutrición</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .login-body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #666;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #555;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <h2>Iniciar Sesión</h2>
        
        <!-- Mostrar mensajes de error -->
        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario_login">Usuario:</label>
                <input type="text" id="usuario_login" name="usuario_login" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Ingresar al Sistema</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px; color: #666;">
            <small>Sistema de Gestión Nutricional Penitenciaria</small>
        </div>
    </div>
</body>
</html>