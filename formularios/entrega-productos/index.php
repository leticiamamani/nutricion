<?php
/**
 * ENTREGA DE PRODUCTOS - index.php
 * Listado de entregas de productos especiales (leche deslactosada, galletas de arroz, pan sin TACC)
 * Acceso: Solo ADMIN y GUARDIA
 */

// Verificar autenticación y permisos (solo ADMIN y GUARDIA)
require_once '../../includes/auth-check.php';
verificarEntregaProductos();

// Conexión a base de datos
require_once '../../includes/conexion.php';

// Capturar búsqueda si existe (por DNI o nombre del PPL)
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

// Consulta principal con JOIN a PPL, sector, pabellón y usuario
$sql = "SELECT 
            e.id,
            e.fecha,
            e.tipo_producto,
            e.cantidad,
            e.fecha_vto,
            e.firma_ppl,
            e.aclaracion,
            e.firma_efectivo,
            p.dni,
            p.nombre_apellido,
            s.nombre AS sector_nombre,
            pa.nombre AS pabellon_nombre,
            u.nombre_usuario AS usuario_nombre
        FROM entrega_productos e
        INNER JOIN ppl p ON e.dni_ppl = p.dni
        LEFT JOIN sector s ON e.id_sector = s.id
        LEFT JOIN pabellon pa ON e.id_pabellon = pa.id
        INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
        WHERE 1=1";

// Filtrar por DNI o nombre del PPL si se buscó algo
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre_apellido LIKE '%$busqueda%' OR p.dni LIKE '%$busqueda%')";
}

// Ordenar por fecha más reciente
$sql .= " ORDER BY e.fecha DESC";

// Ejecutar consulta
$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrega de Productos - Sistema de Nutrición</title>

    <!-- Estilos generales del sistema -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <!-- Estilos específicos de esta página (igual que novedades) -->
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
        }

        /* Encabezado de página */
        .content-header {
            background: white;
            padding: 25px 30px;
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
        }

        .btn-primary {
            background: #000000;
            color: white;
        }

        .btn-success {
            background: #000000;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        /* Tabla de entregas */
        .table-container {
            flex: 1;
            background: white;
            margin: 0 30px 30px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            min-width: 1100px;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 15px 20px;
            text-align: left;
            font-weight: 700;
            color: #000000;
            border-bottom: 2px solid #e0e0e0;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .data-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f0f0f0;
            color: #000000;
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Tipo de producto con color - CORREGIDO */
        .producto-badge {
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 120px;
            text-align: center;
        }

        .LECHE_DESLACTOSADA { background: #17a2b8; }
        .GALLETAS_ARROZ_PAN_SIN_TACC { background: #fd7e14; }

        /* Fecha de vencimiento */
        .vto-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0e0e0;
            color: #000000;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        /* Celdas especiales */
        .firma-cell {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sector-cell {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Botones de acción */
        .actions-cell {
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-decoration: none;
            color: #000000;
            background: white;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
        }

        .btn-action:hover {
            background: #f0f0f0;
            border-color: #000000;
            transform: translateY(-1px);
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

        /* Responsivo */
        @media (max-width: 768px) {
            .search-section {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .search-form {
                max-width: none;
            }

            .table-container {
                margin: 0 20px 20px 20px;
            }

            .data-table {
                font-size: 0.8rem;
                min-width: 900px;
            }

            .data-table th,
            .data-table td {
                padding: 12px 15px;
            }

            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }

            .btn-action {
                min-width: 35px;
                padding: 6px 10px;
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
                <h1>📦 Entrega de Productos</h1>
            </div>

            <!-- Barra de búsqueda y botón agregar -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" 
                           placeholder="Buscar por DNI o nombre del interno..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-success">➕ Nueva Entrega</a>
            </div>

            <!-- Mensaje de éxito si existe -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de entregas -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Vencimiento</th>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Sector</th>
                            <th>Pabellón</th>
                           
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <!-- Fecha de entrega formateada -->
                                    <td><strong><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></strong></td>

                                    <!-- Tipo de producto con color - CORREGIDO -->
                                    <td>
                                        <?php
                                        // Usar el valor exacto de la base de datos para la clase CSS
                                        $clase_producto = $fila['tipo_producto'];
                                        // Reemplazar el '+' por '_' para que sea un nombre de clase CSS válido
                                        $clase_producto = str_replace('+', '_', $clase_producto);
                                        
                                        // Texto para mostrar
                                        $texto_producto = '';
                                        if ($fila['tipo_producto'] === 'LECHE_DESLACTOSADA') {
                                            $texto_producto = 'Leche Deslactosada';
                                        } elseif ($fila['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
                                            $texto_producto = 'Galletas + Pan Sin TACC';
                                        } else {
                                            $texto_producto = $fila['tipo_producto'];
                                        }
                                        ?>
                                        <span class="producto-badge <?php echo $clase_producto; ?>">
                                            <?php echo htmlspecialchars($texto_producto); ?>
                                        </span>
                                    </td>

                                    <!-- Cantidad entregada -->
                                    <td><?php echo $fila['cantidad'] ?? '-'; ?></td>

                                    <!-- Fecha de vencimiento -->
                                    <td>
                                        <?php if ($fila['fecha_vto']): ?>
                                            <span class="vto-badge">
                                                <?php echo date('d/m/Y', strtotime($fila['fecha_vto'])); ?>
                                            </span>
                                        <?php else: echo '-'; endif; ?>
                                    </td>

                                    <!-- DNI del interno -->
                                    <td><?php echo $fila['dni']; ?></td>

                                    <!-- Nombre completo del interno -->
                                    <td><?php echo htmlspecialchars($fila['nombre_apellido']); ?></td>

                                    <!-- Sector -->
                                    <td class="sector-cell" title="<?php echo htmlspecialchars($fila['sector_nombre']); ?>">
                                        <?php echo $fila['sector_nombre'] ? htmlspecialchars($fila['sector_nombre']) : '-'; ?>
                                    </td>

                                    <!-- Pabellón -->
                                    <td><?php echo $fila['pabellon_nombre'] ? htmlspecialchars($fila['pabellon_nombre']) : '-'; ?></td>

                                    
                                    <!-- Botones de acción -->
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Ver detalles">👁️</a>
                                            <a href="editar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Generar PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta entrega?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="no-data">
                                    <p>📦 No se encontraron entregas de productos</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primera entrega</a>
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