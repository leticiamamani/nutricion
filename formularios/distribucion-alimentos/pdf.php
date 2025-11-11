<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibe el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "❌ No se especificó la distribución para generar PDF";
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

// Ahora obtener TODOS los registros de la misma distribución
$query = "SELECT da.*, 
                 s.nombre as sector_nombre,
                 u.nombre_usuario as usuario_nombre
          FROM distribucion_alimentos da 
          LEFT JOIN sectores_distribucion s ON da.id_sector_distribucion = s.id 
          LEFT JOIN usuarios u ON da.id_usuario = u.id_usuario
          WHERE da.id_tipo_comida = ? AND da.fecha = ? AND da.hora = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("iss", $tipo_comida_id, $fecha, $hora);
$stmt->execute();
$result = $stmt->get_result();

$distribuciones = [];
while ($row = $result->fetch_assoc()) {
    $distribuciones[$row['sector_nombre']] = $row;
}

// Obtener descripciones desde la base de datos
$descripciones_desayuno = [];
$descripciones_almuerzo = [];
$descripciones_cena = [];

// Descripciones para DESAYUNO
$query_desayuno = "SELECT sd.nombre, dd.descripcion 
                   FROM descripciones_desayuno dd 
                   LEFT JOIN sectores_distribucion sd ON dd.id_sector_distribucion = sd.id 
                   WHERE dd.estado = 'ACTIVO'";
$result_desayuno = $conexion->query($query_desayuno);
if ($result_desayuno) {
    while ($row = $result_desayuno->fetch_assoc()) {
        $descripciones_desayuno[$row['nombre']] = $row['descripcion'];
    }
}

// Descripciones para ALMUERZO
$query_almuerzo = "SELECT sd.nombre, da.descripcion 
                   FROM descripciones_almuerzo da 
                   LEFT JOIN sectores_distribucion sd ON da.id_sector_distribucion = sd.id 
                   WHERE da.estado = 'ACTIVO'";
$result_almuerzo = $conexion->query($query_almuerzo);
if ($result_almuerzo) {
    while ($row = $result_almuerzo->fetch_assoc()) {
        $descripciones_almuerzo[$row['nombre']] = $row['descripcion'];
    }
}

// Descripciones para CENA
$query_cena = "SELECT sd.nombre, dc.descripcion 
               FROM descripciones_cena dc 
               LEFT JOIN sectores_distribucion sd ON dc.id_sector_distribucion = sd.id 
               WHERE dc.estado = 'ACTIVO'";
$result_cena = $conexion->query($query_cena);
if ($result_cena) {
    while ($row = $result_cena->fetch_assoc()) {
        $descripciones_cena[$row['nombre']] = $row['descripcion'];
    }
}

// Obtener todos los sectores para mostrar en orden
$sectores = [];
$query_sectores = "SELECT nombre FROM sectores_distribucion WHERE estado = 'ACTIVO' ORDER BY id";
$result_sectores = $conexion->query($query_sectores);
if ($result_sectores) {
    while ($row = $result_sectores->fetch_assoc()) {
        $sectores[] = $row['nombre'];
    }
}

// En lugar de TCPDF, generamos HTML que se puede imprimir como PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribución de Alimentos - <?php echo $tipo_comida_nombre . ' - ' . $fecha; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 14px;
            margin: 3px 0;
            color: #666;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
            font-size: 11px;
        }
        .info-row {
            border: 1px solid #ddd;
        }
        .info-cell {
            padding: 8px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }
        .info-label {
            font-weight: bold;
            width: 25%;
            background: #e9e9e9;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        .data-table th, .data-table td {
            padding: 6px;
            border: 1px solid #000;
            text-align: center;
            vertical-align: middle;
        }
        .data-table th {
            background: #f2f2f2;
            font-weight: bold;
        }
        .sector-cell {
            font-weight: bold;
            text-align: left;
        }
        .descripcion-cell {
            text-align: left;
            font-size: 9px;
        }
        .empty-value {
            color: #999;
            font-style: italic;
        }
        .metadata {
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            margin-top: 20px;
        }
        .notes {
            font-size: 9px;
            color: #666;
            margin-top: 10px;
            font-style: italic;
            text-align: justify;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        .print-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir PDF</button>

    <div class="header">
        <h1>SERVICIO PENITENCIARIO PROVINCIAL</h1>
        <h2>ÁREA DE NUTRICIÓN - DISTRIBUCIÓN DE ALIMENTOS</h2>
    </div>

    <table class="info-grid">
        <tr class="info-row">
            <td class="info-cell info-label">TIPO DE COMIDA</td>
            <td class="info-cell"><?php echo htmlspecialchars($tipo_comida_nombre); ?></td>
            <td class="info-cell info-label">FECHA</td>
            <td class="info-cell"><?php echo date('d/m/Y', strtotime($fecha)); ?></td>
        </tr>
        <tr class="info-row">
            <td class="info-cell info-label">HORA DISTRIBUCIÓN</td>
            <td class="info-cell"><?php echo date('H:i', strtotime($hora)); ?></td>
            <td class="info-cell info-label">TOTAL SECTORES</td>
            <td class="info-cell"><?php echo count($distribuciones); ?></td>
        </tr>
    </table>

    <?php if ($tipo_comida_id == 1): // DESAYUNO ?>
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">SECTOR</th>
                <th width="8%">N° COLACIONES</th>
                <th width="20%">DESCRIPCIÓN</th>
                <th width="8%">PAN (KG)</th>
                <th width="12%">TÉ O MATE COCIDO</th>
                <th width="10%">HORA LLEGADA</th>
                <th width="10%">HORA RECIBIDO</th>
                <th width="10%">FIRMA</th>
                <th width="10%">ACLARACIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sectores as $sector_nombre): 
                $dist = $distribuciones[$sector_nombre] ?? null;
                $descripcion = $descripciones_desayuno[$sector_nombre] ?? '';
            ?>
            <tr>
                <td class="sector-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                <td><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                <td class="descripcion-cell"><?php echo $descripcion; ?></td>
                <td><?php echo $dist && !empty($dist['pan_kg']) ? htmlspecialchars($dist['pan_kg']) . ' kg' : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['te_mate_cocido']) ? htmlspecialchars($dist['te_mate_cocido']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="notes">
        *Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria.
        **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego del SPP).
        ***El pan entregado en el desayuno corresponde al almuerzo para los extraordinarios. Cada sector recibe su pan con las viandas.
    </div>

    <?php elseif ($tipo_comida_id == 2): // ALMUERZO ?>
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">SECTOR</th>
                <th width="8%">N° COLACIONES</th>
                <th width="20%">DESCRIPCIÓN</th>
                <th width="8%">N° DIETAS</th>
                <th width="10%">N° VIANDAS COMUNES</th>
                <th width="10%">HORA LLEGADA</th>
                <th width="10%">HORA RECIBIDO</th>
                <th width="10%">FIRMA</th>
                <th width="12%">ACLARACIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sectores as $sector_nombre): 
                $dist = $distribuciones[$sector_nombre] ?? null;
                $descripcion = $descripciones_almuerzo[$sector_nombre] ?? '';
            ?>
            <tr>
                <td class="sector-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                <td><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                <td class="descripcion-cell"><?php echo $descripcion; ?></td>
                <td><?php echo $dist && !empty($dist['nro_dietas']) ? htmlspecialchars($dist['nro_dietas']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['nro_viandas_comunes']) ? htmlspecialchars($dist['nro_viandas_comunes']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="notes">
        *Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria.
        **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego).
    </div>

    <?php elseif ($tipo_comida_id == 3): // CENA ?>
    <table class="data-table">
        <thead>
            <tr>
                <th width="12%">SECTOR</th>
                <th width="8%">N° COLACIONES</th>
                <th width="20%">DESCRIPCIÓN</th>
                <th width="8%">N° DIETAS</th>
                <th width="10%">N° VIANDAS COMUNES</th>
                <th width="10%">HORA LLEGADA</th>
                <th width="10%">HORA RECIBIDO</th>
                <th width="10%">FIRMA</th>
                <th width="12%">ACLARACIÓN</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sectores as $sector_nombre): 
                $dist = $distribuciones[$sector_nombre] ?? null;
                $descripcion = $descripciones_cena[$sector_nombre] ?? '';
            ?>
            <tr>
                <td class="sector-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                <td><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                <td class="descripcion-cell"><?php echo $descripcion; ?></td>
                <td><?php echo $dist && !empty($dist['nro_dietas']) ? htmlspecialchars($dist['nro_dietas']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['nro_viandas_comunes']) ? htmlspecialchars($dist['nro_viandas_comunes']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                <td><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="notes">
        *Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria.
        **Los reclamos de los alimentos acorde a su estado y/o características organolépticas deben realizarse dentro de los 30min luego de ser entregados al sector correspondiente para su devolución y/o reposición por parte de la empresa A.T.A (según pliego del SPP), fuera de ese horario queda sin efecto.
        ***Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego).
        ****Las colaciones donde van dos unidades son contadas como dos colaciones, aún cuando se entreguen una vez al día (ya que no hay indicación médica para el momento del día a entregar y/o fraccionamiento). Las leches especiales se incluyen en el conteo como colación, ya que llevan leche como suplemento supera lo requerido por desayuno.
    </div>
    <?php endif; ?>

    <div class="metadata">
        <strong>Documento generado:</strong> <?php echo date('d/m/Y H:i:s') . ' por ' . htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Sistema'); ?><br>
        <strong>Código de distribución:</strong> <?php echo $tipo_comida_nombre . ' - ' . $fecha . ' ' . $hora; ?><br>
        <strong>Total de sectores:</strong> <?php echo count($distribuciones); ?>
    </div>

    <script>
        // Auto-print en algunos navegadores
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>