<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificar permisos para novedades
verificarNovedades();

// Obtener el ID del registro a visualizar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de registro no válido";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Obtener los datos del registro (CONSULTA CORREGIDA)
$sql = "SELECT an.*, p.nombre_apellido, p.estado_legal, s.nombre as sector_nombre, 
               pb.nombre as pabellon_nombre, u.nombre_usuario as usuario_nombre
        FROM acta_novedad an 
        JOIN ppl p ON an.dni_ppl = p.dni 
        JOIN sector s ON an.id_sector = s.id
        JOIN pabellon pb ON an.id_pabellon = pb.id
        LEFT JOIN usuarios u ON an.id_usuario = u.id_usuario
        WHERE an.id = ?";
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

// Formatear fecha
$fecha = $registro['fecha'];
list($anio, $mes, $dia) = explode('-', $fecha);

// Array de meses en español
$meses = [
    '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
    '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
    '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Acta de Novedad</title>
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
        
        .header img {
            max-height: 80px;
            margin-bottom: 10px;
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
        
        .inline-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            padding: 2px 5px;
            margin: 0 5px;
            min-width: 50px;
            text-align: center;
        }
        
        .field-medium {
            min-width: 120px;
        }
        
        .field-interno {
            min-width: 350px;
        }
        
        .field-estado-legal {
            min-width: 150px;
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
        
        .logo-placeholder {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #ccc;
            margin-bottom: 10px;
            color: #666;
        }
        
        .text-area-content {
            border: 1px solid #000;
            padding: 8px;
            margin: 5px 0;
            min-height: 80px;
            background: transparent;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="page-a4">
        <!-- Encabezado del acta con logo corregido -->
        <div class="header">
            <img src="../../assets/img/logo.png" alt="Logo Gobierno" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div class="logo-placeholder" style="display:none;">LOGO NO ENCONTRADO</div>
            <h3>Jefatura de Sanidad - Área Nutrición</h3>
            <h3>ACTA DE NOVEDAD</h3>
        </div>

        <div class="info-section">
            <strong>Registrado por:</strong> <?php echo htmlspecialchars($registro['usuario_nombre'] ?? 'N/A'); ?> | 
            <strong>Fecha de registro:</strong> <?php echo htmlspecialchars($registro['fecha']); ?>
        </div>

        <div class="acta-content">
            <div class="acta-paragraph">
                <!-- En la provincia de San Juan, sede del Servicio Penitenciario Provincial departamento Chimbas, siendo las -->
                En la provincia de San Juan, sede del Servicio Penitenciario Provincial departamento Chimbas, siendo las
                <span class="inline-field"><?php echo htmlspecialchars($registro['hora']); ?></span> hs, a los
                <span class="inline-field"><?php echo $dia; ?></span> días del mes de 
                <span class="inline-field field-medium"><?php echo $meses[$mes]; ?></span> 
                del año 
                <span class="inline-field"><?php echo $anio; ?></span>
                se labra la presente acta a fin de dejar constancia que la P.P.L. 
                
                <span class="inline-field field-interno"><?php echo htmlspecialchars($registro['nombre_apellido']); ?></span>
                alojado/a en el Sector Nº 
                <span class="inline-field field-medium"><?php echo htmlspecialchars($registro['sector_nombre']); ?></span>
                pabellón Nº 
                <span class="inline-field field-medium"><?php echo htmlspecialchars($registro['pabellon_nombre']); ?></span>
                que ante la novedad que se detalla:
            </div>

            <div class="text-area-content">
                <?php echo nl2br(htmlspecialchars($registro['detalle_novedad'])); ?>
            </div>

            <div class="acta-paragraph">
                Se solicita al interno que explique las razones a fines de realizar los descargos respectivos en la presente acta manifestando lo siguiente:
            </div>

            <div class="text-area-content">
                <?php echo nl2br(htmlspecialchars($registro['descargos_ppl'] ?: 'No se registraron descargos')); ?>
            </div>

            <div class="acta-paragraph">
                Evaluarán los actuados relacionados a la novedad producida y posteriormente se le notificará lo determinado por la administración penitenciaria.
            </div>

            <div class="acta-paragraph">
                Sin más se da por finalizado el acto, firmado al pie al interno de referencia, dos copias de un mismo tenor y a un solo efecto por ante mi funcionario efectuante, lo que CERTIFICO-
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Volver</a>
            <a href="pdf.php?id=<?php echo $id; ?>" class="btn" target="_blank">Descargar PDF</a>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn">Editar</a>
            <a href="eliminar.php?id=<?php echo $id; ?>" class="btn" style="background-color: #dc3545;">Eliminar</a>
        </div>
    </div>
</body>
</html>