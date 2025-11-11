<?php
/**
 * DISTRIBUCIÓN DE ALIMENTOS - index.php
 * Listado de distribuciones diarias de alimentos agrupadas por fecha y tipo de comida
 * Acceso: Solo ADMIN y GUARDIA
 */

// Verificar autenticación y permisos (solo ADMIN y GUARDIA)
require_once '../../includes/auth-check.php';
verificarDistribucionAlimentos();

// Conexión a base de datos
require_once '../../includes/conexion.php';

// Capturar búsqueda si existe (por fecha)
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

// Consulta principal CORREGIDA - Obtener el ID de distribución para cada grupo
$sql = "SELECT 
            d.fecha,
            tc.nombre AS tipo_comida,
            d.id_tipo_comida,
            MIN(d.hora) AS hora,
            COUNT(DISTINCT d.id_sector_distribucion) AS total_sectores,
            SUM(d.nro_colaciones) AS total_colaciones,
            SUM(d.nro_dietas) AS total_dietas,
            SUM(d.nro_viandas_comunes) AS total_viandas_comunes,
            SUM(d.pan_kg) AS total_pan_kg,
            GROUP_CONCAT(DISTINCT d.te_mate_cocido) AS tipos_te_mate,
            u.nombre_usuario AS usuario_nombre,
            MIN(d.id) AS id_distribucion  -- Obtener el ID mínimo del grupo
        FROM distribucion_alimentos d
        INNER JOIN tipo_comida tc ON d.id_tipo_comida = tc.id
        INNER JOIN usuarios u ON d.id_usuario = u.id_usuario
        WHERE 1=1";

// Filtrar por fecha si se buscó algo
if (!empty($busqueda)) {
    $sql .= " AND d.fecha LIKE '%$busqueda%'";
}

// Agrupar por fecha y tipo de comida
$sql .= " GROUP BY d.fecha, d.id_tipo_comida";
$sql .= " ORDER BY d.fecha DESC, d.id_tipo_comida ASC";

// Ejecutar consulta
$resultado = mysqli_query($conexion, $sql);

// Verificar si hubo error en la consulta
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribución de Alimentos - Sistema de Nutrición</title>

    <!-- Estilos generales del sistema -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <!-- Estilos específicos de esta página -->
    <style>
        /* Contenedor principal para layout flexible */
        .page-container {
            display: flex;
            min-height: 100vh;
            background-color: #f8f9fa;
        }

        .main-content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-x: auto;
        }

        /* Encabezado de página */
        .content-header {
            background: white;
            padding: 25px 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .content-header h1 {
            color: #000000;
            font-size: 1.6rem;
            margin: 0;
            font-weight: 700;
        }

        /* Barra de búsqueda y botón agregar */
        .search-section {
            background: white;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex: 1;
            max-width: 400px;
        }

        .search-form input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 0.95rem;
            background: white;
        }

        .search-form input:focus {
            outline: none;
            border-color: #000000;
        }

        /* Botones */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #000000;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Tabla de distribuciones - MEJORADO */
        .table-container {
            flex: 1;
            background: white;
            margin: 0;
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 900px;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 700;
            color: #000000;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #000000;
            vertical-align: middle;
            white-space: nowrap;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Columnas específicas con anchos optimizados */
        .col-fecha { width: 100px; min-width: 100px; }
        .col-tipo { width: 120px; min-width: 120px; }
        .col-hora { width: 80px; min-width: 80px; }
        .col-sectores { width: 80px; min-width: 80px; text-align: center; }
        .col-numeros { width: 70px; min-width: 70px; text-align: center; }
        .col-pan { width: 80px; min-width: 80px; text-align: center; }
        .col-te { width: 90px; min-width: 90px; text-align: center; }
        .col-acciones { width: 200px; min-width: 200px; position: sticky; right: 0; background: white; }

        /* Tipo de comida con color */
        .comida-badge {
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .desayuno { background: #ffc107; color: #000000; }
        .almuerzo { background: #28a745; }
        .cena { background: #6f42c1; }

        /* Celdas especiales */
        .horario-cell {
            white-space: nowrap;
            font-size: 0.8rem;
            text-align: center;
        }

        .numero-cell {
            text-align: center;
            font-weight: 600;
        }

        .badge-sectores {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Botones de acción - MEJORADOS */
        .actions-cell {
            white-space: nowrap;
            position: sticky;
            right: 0;
            background: white;
            z-index: 5;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .btn-action {
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-decoration: none;
            color: #000000;
            background: white;
            transition: all 0.3s ease;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            height: 30px;
        }

        .btn-action:hover {
            background: #000000;
            border-color: #000000;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        /* Mensaje cuando no hay datos */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .btn-small {
            padding: 8px 16px;
            background: #000000;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }

        /* Alertas */
        .alert {
            padding: 15px 20px;
            margin: 0 30px 20px 30px;
            border-radius: 4px;
            font-weight: 500;
            border: 1px solid;
        }

        .alert-success {
            background: #f0f0f0;
            color: #000000;
            border-color: #e0e0e0;
        }

        /* Responsivo mejorado */
        @media (max-width: 1200px) {
            .data-table {
                min-width: 800px;
            }
            
            .data-table th,
            .data-table td {
                padding: 10px 12px;
            }
        }

        @media (max-width: 768px) {
            .search-section {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
                padding: 15px 20px;
            }

            .search-form {
                max-width: none;
            }

            .content-header {
                padding: 20px;
            }

            .content-header h1 {
                font-size: 1.4rem;
            }

            .data-table {
                font-size: 0.8rem;
                min-width: 700px;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }

            .col-acciones {
                width: 180px;
                min-width: 180px;
            }

            .btn-action {
                padding: 5px 8px;
                min-width: 30px;
                height: 28px;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>

    <?php include '../../includes/header.php'; ?>

    <div class="page-container">
        <div class="main-content-area">

            <!-- Encabezado de página -->
            <div class="content-header">
                <h1>📦 Distribución de Alimentos</h1>
            </div>

            <!-- Barra de búsqueda y botón agregar -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" 
                           placeholder="Buscar por fecha (YYYY-MM-DD)..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-primary">➕ Nueva Distribución</a>
            </div>

            <!-- Mensaje de éxito si existe -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de distribuciones - AGRUPADA -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-fecha">Fecha</th>
                            <th class="col-tipo">Tipo Comida</th>
                            <th class="col-hora">Hora</th>
                            <th class="col-sectores">Sectores</th>
                            <th class="col-numeros">Total Colaciones</th>
                            <th class="col-numeros">Total Dietas</th>
                            <th class="col-numeros">Total Viandas</th>
                            <th class="col-pan">Total Pan (kg)</th>
                            <th class="col-te">Té/Mate</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <!-- Fecha formateada -->
                                    <td class="col-fecha"><strong><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></strong></td>

                                    <!-- Tipo de comida con color -->
                                    <td class="col-tipo">
                                        <?php
                                        $clase_comida = strtolower($fila['tipo_comida']);
                                        ?>
                                        <span class="comida-badge <?php echo $clase_comida; ?>">
                                            <?php echo htmlspecialchars($fila['tipo_comida']); ?>
                                        </span>
                                    </td>

                                    <!-- Hora de distribución -->
                                    <td class="col-hora horario-cell"><?php echo $fila['hora'] ? date('H:i', strtotime($fila['hora'])) : '-'; ?></td>

                                    <!-- Número de sectores -->
                                    <td class="col-sectores numero-cell">
                                        <span class="badge-sectores"><?php echo $fila['total_sectores']; ?> sectores</span>
                                    </td>

                                    <!-- Total de colaciones -->
                                    <td class="col-numeros numero-cell"><?php echo $fila['total_colaciones'] ? number_format($fila['total_colaciones']) : '0'; ?></td>

                                    <!-- Total de dietas especiales -->
                                    <td class="col-numeros numero-cell"><?php echo $fila['total_dietas'] ? number_format($fila['total_dietas']) : '0'; ?></td>

                                    <!-- Total de viandas comunes -->
                                    <td class="col-numeros numero-cell"><?php echo $fila['total_viandas_comunes'] ? number_format($fila['total_viandas_comunes']) : '0'; ?></td>

                                    <!-- Total de pan en kg -->
                                    <td class="col-pan numero-cell"><?php echo $fila['total_pan_kg'] ? number_format($fila['total_pan_kg'], 2) : '0.00'; ?></td>

                                    <!-- Té o mate cocido -->
                                    <td class="col-te">
                                        <?php 
                                        if (!empty($fila['tipos_te_mate'])) {
                                            $tipos = array_filter(explode(',', $fila['tipos_te_mate']));
                                            if (count($tipos) > 0) {
                                                echo implode(', ', array_unique($tipos));
                                            } else {
                                                echo '-';
                                            }
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>

                                    <!-- Botones de acción - CORREGIDOS: usar id_distribucion -->
                                    <td class="col-acciones actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $fila['id_distribucion']; ?>" class="btn-action" title="Ver detalles">👁️</a>
                                            <a href="editar.php?id=<?php echo $fila['id_distribucion']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $fila['id_distribucion']; ?>" class="btn-action" title="Generar PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $fila['id_distribucion']; ?>" class="btn-action" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar la distribución de <?php echo $fila['tipo_comida']; ?> del <?php echo date('d/m/Y', strtotime($fila['fecha'])); ?>?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="no-data">
                                    <p>📦 No se encontraron distribuciones de alimentos</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primera distribución</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

</body>
</html>