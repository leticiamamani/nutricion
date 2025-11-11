<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibe el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "❌ No se especificó la distribución a visualizar";
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
                 s.nombre as sector_nombre
          FROM distribucion_alimentos da 
          LEFT JOIN sectores_distribucion s ON da.id_sector_distribucion = s.id 
          WHERE da.id_tipo_comida = ? AND da.fecha = ? AND da.hora = ?";
$stmt = $conexion->prepare($query);
$stmt->bind_param("iss", $tipo_comida_id, $fecha, $hora);
$stmt->execute();
$result = $stmt->get_result();

// Organizar los datos por sector para fácil acceso
$distribuciones_por_sector = [];
while ($row = $result->fetch_assoc()) {
    $distribuciones_por_sector[$row['sector_nombre']] = $row;
}

// Obtener todos los sectores activos
$sectores = [];
$query_sectores = "SELECT nombre FROM sectores_distribucion WHERE estado = 'ACTIVO' ORDER BY id";
$result_sectores = $conexion->query($query_sectores);
if ($result_sectores) {
    while ($row = $result_sectores->fetch_assoc()) {
        $sectores[] = $row['nombre'];
    }
}

// Obtener descripciones desde la base de datos
$descripciones_desayuno = [];
$descripciones_almuerzo = [];
$descripciones_cena = [];

if ($tipo_comida_id == 1) {
    $query_desc = "SELECT sd.nombre, dd.descripcion 
                   FROM descripciones_desayuno dd 
                   LEFT JOIN sectores_distribucion sd ON dd.id_sector_distribucion = sd.id 
                   WHERE dd.estado = 'ACTIVO'";
    $result_desc = $conexion->query($query_desc);
    while ($row = $result_desc->fetch_assoc()) {
        $descripciones_desayuno[$row['nombre']] = $row['descripcion'];
    }
} elseif ($tipo_comida_id == 2) {
    $query_desc = "SELECT sd.nombre, da.descripcion 
                   FROM descripciones_almuerzo da 
                   LEFT JOIN sectores_distribucion sd ON da.id_sector_distribucion = sd.id 
                   WHERE da.estado = 'ACTIVO'";
    $result_desc = $conexion->query($query_desc);
    while ($row = $result_desc->fetch_assoc()) {
        $descripciones_almuerzo[$row['nombre']] = $row['descripcion'];
    }
} elseif ($tipo_comida_id == 3) {
    $query_desc = "SELECT sd.nombre, dc.descripcion 
                   FROM descripciones_cena dc 
                   LEFT JOIN sectores_distribucion sd ON dc.id_sector_distribucion = sd.id 
                   WHERE dc.estado = 'ACTIVO'";
    $result_desc = $conexion->query($query_desc);
    while ($row = $result_desc->fetch_assoc()) {
        $descripciones_cena[$row['nombre']] = $row['descripcion'];
    }
}

// Verificar si hay datos en algún sector
$hay_datos = false;
foreach ($distribuciones_por_sector as $dist) {
    if (!empty($dist['nro_colaciones']) || !empty($dist['nro_dietas']) || 
        !empty($dist['nro_viandas_comunes']) || !empty($dist['pan_kg']) ||
        !empty($dist['te_mate_cocido']) || !empty($dist['hora_llegada']) ||
        !empty($dist['hora_recibido']) || !empty($dist['firma']) || 
        !empty($dist['aclaracion'])) {
        $hay_datos = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Distribución de Alimentos - Área Nutrición</title>
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
            padding: 0;
            font-size: 14px;
            background: #f5f5f5;
            width: 100%;
            min-height: 100vh;
        }
        
        .main-content {
            width: 100%;
            min-height: calc(100vh - 120px);
            padding: 0;
            margin: 0;
        }
        
        .form-container {
            width: 100%;
            margin: 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
            width: 100%;
        }
        
        .header h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            font-weight: bold;
        }
        
        .header h3 {
            margin: 10px 0;
            font-size: 18px;
            color: #666;
        }
        
        .distribution-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .meal-section {
            width: 100%;
            overflow-x: auto;
            margin: 25px 0;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .meal-title {
            background-color: #e0e0e0;
            padding: 15px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 0;
            font-size: 18px;
            width: 100%;
            border-radius: 8px 8px 0 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            page-break-inside: auto;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            font-size: 13px;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .description-cell {
            font-size: 12px;
            text-align: left;
            padding: 8px;
            line-height: 1.4;
            min-width: 200px;
        }
        
        .data-cell {
            padding: 10px;
            text-align: center;
            background: white;
            font-weight: 500;
        }
        
        .empty-value {
            color: #999;
            font-style: italic;
        }
        
        .notes {
            font-size: 12px;
            margin-top: 0;
            padding: 15px;
            font-style: italic;
            color: #666;
            line-height: 1.5;
            width: 100%;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
            border-top: 1px solid #e0e0e0;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #ddd;
            width: 100%;
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
            margin: 0 10px;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 8px;
            color: #666;
            font-size: 9pt;
            font-family: Arial, sans-serif;
            padding: 10px 20px;
            background: white;
            border-bottom: 1px solid #ddd;
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
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .distribution-info {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 12px;
                margin: 5px;
                width: 100%;
                margin-bottom: 10px;
            }
            
            .actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            th, td {
                padding: 8px;
                font-size: 11px;
            }
            
            .meal-title {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="main-content">
        <div class="form-container">
            <div class="header">
                <h2>SERVICIO PENITENCIARIO PROVINCIAL</h2>
                <h3>ÁREA DE NUTRICIÓN - DETALLE DE DISTRIBUCIÓN</h3>
            </div>

            <!-- Información básica -->
            <div class="distribution-info">
                <div class="info-card">
                    <div class="info-label">TIPO DE COMIDA</div>
                    <div class="info-value">
                        <?php 
                        echo htmlspecialchars($tipo_comida_nombre);
                        $completo = false;
                        foreach ($distribuciones_por_sector as $dist) {
                            if (!empty($dist['hora_recibido'])) {
                                $completo = true;
                                break;
                            }
                        }
                        if ($completo) {
                            echo '<span class="status-badge status-completed">COMPLETADO</span>';
                        } else {
                            echo '<span class="status-badge status-pending">PENDIENTE</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">FECHA</div>
                    <div class="info-value"><?php echo date('d/m/Y', strtotime($fecha)); ?></div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">HORA DISTRIBUCIÓN</div>
                    <div class="info-value"><?php echo date('H:i', strtotime($hora)); ?></div>
                </div>
                
                <div class="info-card">
                    <div class="info-label">TOTAL DE SECTORES</div>
                    <div class="info-value"><?php echo count($distribuciones_por_sector); ?> sectores</div>
                </div>
            </div>

            <?php if (!$hay_datos): ?>
                <div class="no-data">
                    <h3>⚠️ No se encontraron datos para esta distribución</h3>
                    <p>La distribución existe pero no contiene datos en ningún sector.</p>
                </div>
            <?php else: ?>
                <!-- Mostrar tabla según el tipo de comida -->
                <div class="meal-section">
                    <div class="meal-title">
                        <?php echo $tipo_comida_nombre; ?> - <?php echo date('d/m/Y', strtotime($fecha)); ?>
                    </div>
                    
                    <?php if ($tipo_comida_id == 1): // DESAYUNO ?>
                    <table>
                        <thead>
                            <tr>
                                <th>SECTOR</th>
                                <th>N° COLACIONES</th>
                                <th>DESCRIPCIÓN</th>
                                <th>PAN (KG)</th>
                                <th>TÉ O MATE COCIDO</th>
                                <th>HORA LLEGADA</th>
                                <th>HORA RECIBIDO</th>
                                <th>FIRMA</th>
                                <th>ACLARACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectores as $sector_nombre): 
                                $dist = $distribuciones_por_sector[$sector_nombre] ?? null;
                                $descripcion = $descripciones_desayuno[$sector_nombre] ?? '';
                            ?>
                            <tr>
                                <td class="data-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="description-cell"><?php echo htmlspecialchars($descripcion); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['pan_kg']) ? htmlspecialchars($dist['pan_kg']) . ' kg' : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['te_mate_cocido']) ? htmlspecialchars($dist['te_mate_cocido']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
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
                    <table>
                        <thead>
                            <tr>
                                <th>SECTOR</th>
                                <th>N° COLACIONES</th>
                                <th>DESCRIPCIÓN</th>
                                <th>N° DIETAS</th>
                                <th>N° VIANDAS COMUNES</th>
                                <th>HORA LLEGADA</th>
                                <th>HORA RECIBIDO</th>
                                <th>FIRMA</th>
                                <th>ACLARACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectores as $sector_nombre): 
                                $dist = $distribuciones_por_sector[$sector_nombre] ?? null;
                                $descripcion = $descripciones_almuerzo[$sector_nombre] ?? '';
                            ?>
                            <tr>
                                <td class="data-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="description-cell"><?php echo htmlspecialchars($descripcion); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_dietas']) ? htmlspecialchars($dist['nro_dietas']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_viandas_comunes']) ? htmlspecialchars($dist['nro_viandas_comunes']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="notes">
                        *Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria.
                        **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego).
                    </div>

                    <?php elseif ($tipo_comida_id == 3): // CENA ?>
                    <table>
                        <thead>
                            <tr>
                                <th>SECTOR</th>
                                <th>N° COLACIONES</th>
                                <th>DESCRIPCIÓN</th>
                                <th>N° DIETAS</th>
                                <th>N° VIANDAS COMUNES</th>
                                <th>HORA LLEGADA</th>
                                <th>HORA RECIBIDO</th>
                                <th>FIRMA</th>
                                <th>ACLARACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectores as $sector_nombre): 
                                $dist = $distribuciones_por_sector[$sector_nombre] ?? null;
                                $descripcion = $descripciones_cena[$sector_nombre] ?? '';
                            ?>
                            <tr>
                                <td class="data-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_colaciones']) ? htmlspecialchars($dist['nro_colaciones']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="description-cell"><?php echo htmlspecialchars($descripcion); ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_dietas']) ? htmlspecialchars($dist['nro_dietas']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['nro_viandas_comunes']) ? htmlspecialchars($dist['nro_viandas_comunes']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_llegada']) ? date('H:i', strtotime($dist['hora_llegada'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['hora_recibido']) ? date('H:i', strtotime($dist['hora_recibido'])) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['firma']) ? htmlspecialchars($dist['firma']) : '<span class="empty-value">-</span>'; ?></td>
                                <td class="data-cell"><?php echo $dist && !empty($dist['aclaracion']) ? htmlspecialchars($dist['aclaracion']) : '<span class="empty-value">-</span>'; ?></td>
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
                </div>
            <?php endif; ?>

            <!-- Metadatos -->
            <div class="metadata">
                <strong>📋 Información del Registro:</strong><br>
                <div style="margin-top: 8px;">
                    📅 Fecha de distribución: <strong><?php echo date('d/m/Y', strtotime($fecha)); ?></strong> | 
                    🕒 Hora: <strong><?php echo date('H:i', strtotime($hora)); ?></strong> |
                    📊 Total de sectores: <strong><?php echo count($distribuciones_por_sector); ?></strong>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">📋 Volver al Listado</a>
                <a href="editar.php?id=<?php echo $id_distribucion; ?>" class="btn btn-primary">✏️ Editar</a>
                <a href="pdf.php?id=<?php echo $id_distribucion; ?>" target="_blank" class="btn btn-success">📄 Generar PDF</a>
                <a href="eliminar.php?id=<?php echo $id_distribucion; ?>" class="btn btn-danger">🗑️ Eliminar</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteBtn = document.querySelector('.btn-danger');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function(e) {
                    if (!confirm('¿Está seguro de que desea eliminar permanentemente esta distribución?\n\nEsta acción eliminará <?php echo count($distribuciones_por_sector); ?> registros y no se puede deshacer.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>