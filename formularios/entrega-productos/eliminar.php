<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificación básica de permisos
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibe el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "❌ No se especificó el registro a eliminar";
    header("Location: index.php");
    exit();
}

$id_entrega = intval($_GET['id']);

// Verificar si el registro existe
$query_verificar = "SELECT * FROM entrega_productos WHERE id = ?";
$stmt_verificar = $conexion->prepare($query_verificar);
$stmt_verificar->bind_param("i", $id_entrega);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

if ($result_verificar->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Registro no encontrado";
    header("Location: index.php");
    exit();
}

$registro = $result_verificar->fetch_assoc();

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        // Eliminar el registro
        $query_eliminar = "DELETE FROM entrega_productos WHERE id = ?";
        $stmt_eliminar = $conexion->prepare($query_eliminar);
        $stmt_eliminar->bind_param("i", $id_entrega);
        
        if ($stmt_eliminar->execute()) {
            $_SESSION['mensaje'] = "✅ Entrega de producto eliminada correctamente";
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al eliminar el registro: " . $conexion->error;
        }
        
        header("Location: index.php");
        exit();
    } else {
        header("Location: index.php");
        exit();
    }
}

// Obtener nombre del interno para mostrar en la confirmación
$query_interno = "SELECT nombre_apellido FROM ppl WHERE dni = ?";
$stmt_interno = $conexion->prepare($query_interno);
$stmt_interno->bind_param("s", $registro['dni_ppl']);
$stmt_interno->execute();
$result_interno = $stmt_interno->get_result();
$interno = $result_interno->fetch_assoc();

// Determinar texto del producto para mostrar - USANDO LAS NUEVAS COLUMNAS
$texto_producto = "";
if ($registro['tipo_producto'] === 'LECHE_DESLACTOSADA') {
    $texto_producto = $registro['cantidad'] . " unidades de leche deslactosada";
    if (!empty($registro['fecha_vto'])) {
        $texto_producto .= " VTO " . date('d/m/Y', strtotime($registro['fecha_vto']));
    }
} elseif ($registro['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
    // USAR LAS NUEVAS COLUMNAS para mostrar ambos productos
    $texto_producto = $registro['cantidad_galletas'] . " paquetes de galletas de arroz VTO " . date('d/m/Y', strtotime($registro['fecha_vto_galletas'])) . " y " . $registro['cantidad_pan'] . " unidades de pan sin TACC VTO " . date('d/m/Y', strtotime($registro['fecha_vto_pan']));
} else {
    $texto_producto = $registro['cantidad'] . " unidades de producto";
    if (!empty($registro['fecha_vto'])) {
        $texto_producto .= " VTO " . date('d/m/Y', strtotime($registro['fecha_vto']));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Entrega de Producto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="container">
        <div class="alert">
            <h3>⚠️ Confirmar Eliminación</h3>
            <p>¿Está seguro que desea eliminar este registro de entrega de producto? Esta acción no se puede deshacer.</p>
        </div>

        <div class="info-box">
            <h4>Información del Registro:</h4>
            <p><strong>Interno:</strong> <?php echo htmlspecialchars($interno['nombre_apellido'] ?? 'N/A'); ?></p>
            <p><strong>Fecha:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($registro['fecha']))); ?></p>
            <p><strong>Tipo de Producto:</strong> 
                <?php 
                if ($registro['tipo_producto'] === 'LECHE_DESLACTOSADA') {
                    echo 'Leche Deslactosada';
                } elseif ($registro['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
                    echo 'Galletas de Arroz + Pan Sin TACC';
                } else {
                    echo htmlspecialchars($registro['tipo_producto']);
                }
                ?>
            </p>
            <p><strong>Productos:</strong> <?php echo htmlspecialchars($texto_producto); ?></p>
        </div>

        <form method="POST" action="">
            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" name="confirmar" value="si" class="btn btn-danger">Eliminar Permanentemente</button>
            </div>
        </form>
    </div>
</body>
</html>