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
    $_SESSION['mensaje_error'] = "❌ No se especificó la recepción a eliminar";
    header("Location: index.php");
    exit();
}

$id_recepcion = intval($_GET['id']);

// Verificar si la recepción existe
$query_verificar = "SELECT ra.*, p.nombre_apellido, s.nombre as sector_nombre 
                   FROM recepcion_alimentos ra
                   INNER JOIN ppl p ON ra.dni_ppl = p.dni
                   INNER JOIN sector s ON ra.id_sector = s.id
                   WHERE ra.id = ?";
$stmt_verificar = $conexion->prepare($query_verificar);
$stmt_verificar->bind_param("i", $id_recepcion);
$stmt_verificar->execute();
$result_verificar = $stmt_verificar->get_result();

if ($result_verificar->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Recepción no encontrada";
    header("Location: index.php");
    exit();
}

$recepcion = $result_verificar->fetch_assoc();

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        // Eliminar el registro
        $query_eliminar = "DELETE FROM recepcion_alimentos WHERE id = ?";
        $stmt_eliminar = $conexion->prepare($query_eliminar);
        $stmt_eliminar->bind_param("i", $id_recepcion);
        
        if ($stmt_eliminar->execute()) {
            $_SESSION['mensaje_exito'] = "✅ Recepción eliminada correctamente";
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al eliminar la recepción: " . $conexion->error;
        }
        
        header("Location: index.php");
        exit();
    } else {
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
    <title>Eliminar Recepción de Alimentos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 800px;
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
            padding: 20px;
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
        
        /* ESTILOS PARA DATOS BÁSICOS - AL LADO DE LOS DOS PUNTOS */
        .datos-basicos-linea {
            margin-bottom: 8px;
            display: block;
        }
        
        .dato-label {
            font-weight: bold;
            display: inline;
        }
        
        .dato-valor {
            display: inline;
            margin-left: 5px;
        }
        
        .comida-info {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .comida-info h5 {
            margin: 0 0 10px 0;
            color: #333;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        
        .comida-linea {
            margin-bottom: 5px;
        }
        
        .comida-label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .comida-valor {
            margin-left: 5px;
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
            <p>¿Está seguro que desea eliminar esta recepción de alimentos? Esta acción no se puede deshacer.</p>
        </div>

        <div class="info-box">
            <h4>Información de la Recepción:</h4>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">NOMBRE:</span>
                <span class="dato-valor"><?php echo htmlspecialchars($recepcion['nombre_apellido']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">DNI:</span>
                <span class="dato-valor"><?php echo htmlspecialchars($recepcion['dni_ppl']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">SECTOR:</span>
                <span class="dato-valor"><?php echo htmlspecialchars($recepcion['sector_nombre']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">FECHA:</span>
                <span class="dato-valor"><?php echo date('d/m/Y', strtotime($recepcion['fecha'])); ?></span>
            </div>
            
            <div class="comida-info">
                <h5>DESAYUNO</h5>
                <div class="comida-linea">
                    <span class="comida-label">HORA:</span>
                    <span class="comida-valor"><?php echo $recepcion['desayuno_hora'] ? date('H:i', strtotime($recepcion['desayuno_hora'])) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">FIRMA:</span>
                    <span class="comida-valor"><?php echo $recepcion['desayuno_firma'] ? htmlspecialchars($recepcion['desayuno_firma']) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">ACLARACIÓN:</span>
                    <span class="comida-valor"><?php echo $recepcion['desayuno_aclaracion'] ? htmlspecialchars($recepcion['desayuno_aclaracion']) : 'Ninguna'; ?></span>
                </div>
            </div>
            
            <div class="comida-info">
                <h5>ALMUERZO</h5>
                <div class="comida-linea">
                    <span class="comida-label">HORA:</span>
                    <span class="comida-valor"><?php echo $recepcion['almuerzo_hora'] ? date('H:i', strtotime($recepcion['almuerzo_hora'])) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">FIRMA:</span>
                    <span class="comida-valor"><?php echo $recepcion['almuerzo_firma'] ? htmlspecialchars($recepcion['almuerzo_firma']) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">ACLARACIÓN:</span>
                    <span class="comida-valor"><?php echo $recepcion['almuerzo_aclaracion'] ? htmlspecialchars($recepcion['almuerzo_aclaracion']) : 'Ninguna'; ?></span>
                </div>
            </div>
            
            <div class="comida-info">
                <h5>CENA</h5>
                <div class="comida-linea">
                    <span class="comida-label">HORA:</span>
                    <span class="comida-valor"><?php echo $recepcion['cena_hora'] ? date('H:i', strtotime($recepcion['cena_hora'])) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">FIRMA:</span>
                    <span class="comida-valor"><?php echo $recepcion['cena_firma'] ? htmlspecialchars($recepcion['cena_firma']) : 'No registrada'; ?></span>
                </div>
                <div class="comida-linea">
                    <span class="comida-label">ACLARACIÓN:</span>
                    <span class="comida-valor"><?php echo $recepcion['cena_aclaracion'] ? htmlspecialchars($recepcion['cena_aclaracion']) : 'Ninguna'; ?></span>
                </div>
            </div>
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