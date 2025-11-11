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
    $_SESSION['mensaje_error'] = "❌ No se especificó el registro a visualizar";
    header("Location: index.php");
    exit();
}

$id_entrega = intval($_GET['id']);

// Obtener datos del registro
$query = "SELECT ep.*, p.nombre_apellido, s.nombre as sector_nombre, pb.nombre as pabellon_nombre,
                 u.nombre_usuario as usuario_nombre
          FROM entrega_productos ep
          LEFT JOIN ppl p ON ep.dni_ppl = p.dni
          LEFT JOIN sector s ON ep.id_sector = s.id
          LEFT JOIN pabellon pb ON ep.id_pabellon = pb.id
          LEFT JOIN usuarios u ON ep.id_usuario = u.id_usuario
          WHERE ep.id = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_entrega);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Registro no encontrado";
    header("Location: index.php");
    exit();
}

$registro = $result->fetch_assoc();

// Formatear fecha para mostrar
$fecha_registro = $registro['fecha'];
$dia_registro = date('d', strtotime($fecha_registro));
$mes_registro = date('m', strtotime($fecha_registro));
$anio_registro = date('Y', strtotime($fecha_registro));

// Array de meses en español
$meses = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];

// Determinar texto del producto - USANDO LAS NUEVAS COLUMNAS
$texto_entrega = "";

if ($registro['tipo_producto'] === 'LECHE_DESLACTOSADA') {
    $texto_entrega = "<span class='info-field field-cantidad'>" . htmlspecialchars($registro['cantidad']) . "</span> unidades de leche deslactosada";
    if (!empty($registro['fecha_vto'])) {
        $texto_entrega .= " VTO <span class='info-field field-fecha-vto'>" . date('d/m/Y', strtotime($registro['fecha_vto'])) . "</span>";
    }
} elseif ($registro['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
    // USAR LAS NUEVAS COLUMNAS para mostrar ambos productos
    $texto_entrega = "<span class='info-field field-cantidad'>" . htmlspecialchars($registro['cantidad_galletas']) . "</span> paquetes de galletas de arroz VTO <span class='info-field field-fecha-vto'>" . date('d/m/Y', strtotime($registro['fecha_vto_galletas'])) . "</span> y <span class='info-field field-cantidad'>" . htmlspecialchars($registro['cantidad_pan']) . "</span> unidades de pan tipo facial sin TACC VTO <span class='info-field field-fecha-vto'>" . date('d/m/Y', strtotime($registro['fecha_vto_pan'])) . "</span>";
} else {
    $texto_entrega = "<span class='info-field field-cantidad'>" . htmlspecialchars($registro['cantidad']) . "</span> unidades de producto";
    if (!empty($registro['fecha_vto'])) {
        $texto_entrega .= " con vencimiento <span class='info-field field-fecha-vto'>" . date('d/m/Y', strtotime($registro['fecha_vto'])) . "</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Acta de Entrega</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-size: 12pt;
            line-height: 1.2;
        }
        
        .page-a4 {
            width: 21cm;
            min-height: 29.7cm;
            margin: 1cm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 2cm;
            box-sizing: border-box;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header h3 {
            font-size: 14pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .acta-content {
            margin-bottom: 30px;
        }
        
        .acta-paragraph {
            margin-bottom: 15px;
            text-align: justify;
            line-height: 1.4;
        }
        
        .info-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            padding: 2px 5px;
            margin: 0 5px;
            min-width: 50px;
            text-align: center;
        }
        
        .field-small {
            width: 50px;
        }
        
        .field-medium {
            width: 120px;
        }
        
        .field-interno {
            width: 350px;
        }
        
        .field-cantidad {
            width: 80px;
        }
        
        .field-fecha-vto {
            width: 120px;
        }
        
        .firma-section {
            margin-top: 60px;
        }
        
        .firma-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        
        .firma-group {
            text-align: center;
            flex: 1;
            margin: 0 15px;
        }
        
        .firma-label {
            font-size: 10pt;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .firma-input {
            width: 100%;
            text-align: center;
            border-bottom: 1px solid #000;
            padding: 5px 0;
            margin-top: 5px;
        }
        
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn {
            padding: 8px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 0 10px;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            color: #666;
            font-size: 10pt;
            font-family: Arial, sans-serif;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="page-a4">
        <div class="header">
            <h3>EFECTIVO ACTUANTE</h3>
            <h3>ACTA DE ENTREGA</h3>
        </div>

        <div class="acta-content">
            <div class="acta-paragraph">
                En San Juan, Servicio Penitenciario Provincial, a los 
                <span class="info-field field-small"><?php echo $dia_registro; ?></span> días del mes de 
                <span class="info-field field-medium"><?php echo $meses[$mes_registro]; ?></span> 
                del año 
                <span class="info-field field-small"><?php echo $anio_registro; ?></span>
                se labra la siguiente acta de Entrega: Por ello se hace comparecer a la P.P.L.
                <span class="info-field field-interno"><?php echo htmlspecialchars($registro['nombre_apellido']); ?></span>
                alojado en el Sector Nº 
                <span class="info-field field-medium"><?php echo htmlspecialchars($registro['sector_nombre']); ?></span>
                Pabellón Nº 
                <span class="info-field field-medium"><?php echo htmlspecialchars($registro['pabellon_nombre']); ?></span>
            </div>

            <div class="acta-paragraph">
                Se le hace entrega de <?php echo $texto_entrega; ?>.
            </div>

            <div class="acta-paragraph">
                Sin más se da por finalizado el acta, a un solo efecto, firma y aclaración de puño y letra que certifican la presente, cuyo ejemplar es único.
            </div>
        </div>

        <!-- Sección de firmas - ACLARACIÓN SOLO PARA FIRMAS -->
        <div class="firma-section">
            <div class="firma-container">
                <!-- Firma PPL -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA</div>
                    <div class="firma-input"><?php echo htmlspecialchars($registro['firma_ppl']); ?></div>
                </div>
                
                <!-- Aclaración - SOLO PARA ACLARACIÓN DE FIRMAS -->
                <div class="firma-group">
                    <div class="firma-label">ACLARACIÓN</div>
                    <div class="firma-input"><?php echo htmlspecialchars($registro['aclaracion']); ?></div>
                </div>
                
                <!-- Firma Efectivo -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA EFECTIVO</div>
                    <div class="firma-input"><?php echo htmlspecialchars($registro['firma_efectivo']); ?></div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            <a href="editar.php?id=<?php echo $id_entrega; ?>" class="btn">Editar</a>
            <a href="pdf.php?id=<?php echo $id_entrega; ?>" target="_blank" class="btn">Generar PDF</a>
        </div>
    </div>
</body>
</html>