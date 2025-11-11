<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibe el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "❌ No se especificó la distribución a eliminar";
    header("Location: index.php");
    exit();
}

$id_distribucion = intval($_GET['id']);

// Primero obtener los datos básicos del registro para saber de qué distribución se trata
$query_base = "SELECT da.id_tipo_comida, da.fecha, da.hora, tc.nombre as tipo_comida_nombre 
               FROM distribucion_alimentos da 
               LEFT JOIN tipo_comida tc ON da.id_tipo_comida = tc.id 
               WHERE da.id = ?";
$stmt_base = $conexion->prepare($query_base);
$stmt_base->bind_param("i", $id_distribucion);
$stmt_base->execute();
$result_base = $stmt_base->get_result();

if ($result_base->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Distribución no encontrada";
    header("Location: index.php");
    exit();
}

$distribucion_base = $result_base->fetch_assoc();
$tipo_comida_id = $distribucion_base['id_tipo_comida'];
$tipo_comida_nombre = $distribucion_base['tipo_comida_nombre'];
$fecha = $distribucion_base['fecha'];
$hora = $distribucion_base['hora'];

// Ahora obtener TODOS los registros de la misma distribución (mismo tipo_comida, fecha y hora)
$query = "SELECT da.*, 
                 s.nombre as sector_nombre,
                 u.nombre as usuario_nombre
          FROM distribucion_alimentos da 
          LEFT JOIN sectores_distribucion s ON da.id_sector_distribucion = s.id 
          LEFT JOIN usuarios u ON da.id_usuario = u.id
          WHERE da.id_tipo_comida = ? AND da.fecha = ? AND da.hora = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("iss", $tipo_comida_id, $fecha, $hora);
$stmt->execute();
$result = $stmt->get_result();

$distribuciones = [];
$total_registros = 0;
while ($row = $result->fetch_assoc()) {
    $distribuciones[] = $row;
    $total_registros++;
}

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirmar']) && $_POST['confirmar'] === 'si') {
        // Eliminar TODOS los registros de la misma distribución
        $query_eliminar = "DELETE FROM distribucion_alimentos WHERE id_tipo_comida = ? AND fecha = ? AND hora = ?";
        $stmt_eliminar = $conexion->prepare($query_eliminar);
        $stmt_eliminar->bind_param("iss", $tipo_comida_id, $fecha, $hora);
        
        if ($stmt_eliminar->execute()) {
            $_SESSION['mensaje_exito'] = "✅ Distribución de " . $tipo_comida_nombre . " del " . date('d/m/Y', strtotime($fecha)) . " eliminada correctamente (" . $total_registros . " registros)";
        } else {
            $_SESSION['mensaje_error'] = "❌ Error al eliminar la distribución: " . $conexion->error;
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
    <title>Eliminar Distribución de Alimentos - Área Nutrición</title>
    <style>
        /* Mantener los mismos estilos del archivo original */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 20px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #dc3545;
        }
        
        .alert h3 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        
        .info-box {
            background-color: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #007bff;
        }
        
        .info-box h4 {
            margin-bottom: 15px;
            color: #004085;
            font-size: 16px;
        }
        
        .info-item {
            margin-bottom: 12px;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border-left: 4px solid #007bff;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
            font-size: 13px;
        }
        
        .info-value {
            color: #666;
            font-weight: 500;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 10px;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #ddd;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 20px;
            color: #666;
            font-size: 14px;
            width: 100%;
            max-width: 600px;
            padding: 10px 0;
        }
        
        .metadata {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
            border-left: 4px solid #28a745;
        }
        
        .warning-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        
        .warning-section h5 {
            color: #856404;
            margin-bottom: 8px;
        }
        
        .sectores-list {
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px;
                padding: 20px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 12px;
                margin-bottom: 10px;
                width: 100%;
            }
            
            .actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
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
            <p>¿Está seguro que desea eliminar permanentemente <strong>toda la distribución</strong> de <?php echo $tipo_comida_nombre; ?> del <?php echo date('d/m/Y', strtotime($fecha)); ?>?</p>
            <p>Esta acción eliminará <strong><?php echo $total_registros; ?> registros de sectores</strong> y no se puede deshacer.</p>
        </div>

        <div class="warning-section">
            <h5>📢 Advertencia Importante</h5>
            <p>Esta operación eliminará permanentemente todos los registros de esta distribución. Una vez eliminados, no podrá recuperar la información.</p>
        </div>

        <div class="info-box">
            <h4>Información de la Distribución a Eliminar</h4>
            
            <div class="info-item">
                <span class="info-label">Tipo de Comida:</span>
                <span class="info-value"><?php echo htmlspecialchars($tipo_comida_nombre); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Fecha:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($fecha)); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Hora:</span>
                <span class="info-value"><?php echo date('H:i', strtotime($hora)); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Total de Registros:</span>
                <span class="info-value"><?php echo $total_registros; ?> sectores</span>
            </div>

            <div class="metadata">
                <strong>📋 Sectores afectados:</strong>
                <div class="sectores-list">
                    <?php 
                    $sectores = [];
                    foreach ($distribuciones as $dist) {
                        $sectores[] = $dist['sector_nombre'];
                    }
                    echo implode(', ', array_slice($sectores, 0, 5));
                    if (count($sectores) > 5) {
                        echo ' y ' . (count($sectores) - 5) . ' más...';
                    }
                    ?>
                </div>
            </div>
        </div>

        <form method="POST" action="">
            <div class="actions">
                <a href="ver.php?id=<?php echo $id_distribucion; ?>" class="btn btn-secondary">👁️ Ver Detalles</a>
                <a href="index.php" class="btn btn-secondary">📋 Volver al Listado</a>
                <button type="submit" name="confirmar" value="si" class="btn btn-danger">🗑️ Eliminar <?php echo $total_registros; ?> Registros</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿ESTÁ ABSOLUTAMENTE SEGURO?\n\nEsta acción eliminará permanentemente <?php echo $total_registros; ?> registros de distribución y no podrá recuperarlos.\n\n¿Continuar con la eliminación?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>