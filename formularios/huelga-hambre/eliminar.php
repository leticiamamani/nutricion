<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificar permisos para huelga de hambre
verificarHuelgaHambre();

// Obtener el ID del registro a eliminar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de registro no válido";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Obtener los datos del registro para confirmación
$sql = "SELECT hh.*, p.nombre_apellido 
        FROM huelga_hambre hh 
        JOIN ppl p ON hh.dni_ppl = p.dni 
        WHERE hh.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Registro no encontrado";
    header("Location: index.php");
    exit();
}

$registro = $result->fetch_assoc();

// Procesar la eliminación cuando se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        // Eliminar el registro
        $sql_delete = "DELETE FROM huelga_hambre WHERE id = ?";
        $stmt_delete = $conexion->prepare($sql_delete);
        $stmt_delete->bind_param("i", $id);
        
        if ($stmt_delete->execute()) {
            $_SESSION['mensaje_exito'] = "✅ Registro eliminado correctamente";
        } else {
            $_SESSION['mensaje_error'] = "Error al eliminar el registro: " . $conexion->error;
        }
        
        header("Location: index.php");
        exit();
    } else {
        // Si no confirma, redirigir a index.php
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Huelga de Hambre</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .alert {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Eliminar Acta de Huelga de Hambre</h2>
        
        <div class="alert">
            <strong>¡Advertencia!</strong> Esta acción no se puede deshacer.
        </div>

        <p>¿Está seguro de que desea eliminar el siguiente registro?</p>
        
        <ul>
            <li><strong>Interno:</strong> <?php echo htmlspecialchars($registro['nombre_apellido']); ?></li>
            <li><strong>Fecha:</strong> <?php echo htmlspecialchars($registro['fecha']); ?></li>
            <li><strong>Peso:</strong> <?php echo htmlspecialchars($registro['peso_kg']); ?> kg</li>
            <li><strong>Talla:</strong> <?php echo htmlspecialchars($registro['talla_m']); ?> m</li>
        </ul>

        <form method="POST">
            <input type="hidden" name="confirmar" value="si">
            <button type="submit" class="btn btn-danger">Eliminar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>