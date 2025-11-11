<?php
/**
 * RECEPCIÓN DE ALIMENTOS - index.php
 * Listado de recepciones de alimentos por PPL y día
 * Acceso: Solo ADMIN y GUARDIA
 */

// Verificar autenticación y permisos (solo ADMIN y GUARDIA)
require_once '../../includes/auth-check.php';
verificarRecepcionAlimentos();

// Conexión a base de datos
require_once '../../includes/conexion.php';

// Capturar búsqueda si existe (por DNI o nombre del PPL)
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

// Consulta principal adaptada a la nueva estructura de tabla
$sql = "SELECT 
            r.id,
            r.dni_ppl,
            r.fecha,
            r.desayuno_hora,
            r.desayuno_firma,
            r.desayuno_aclaracion,
            r.almuerzo_hora,
            r.almuerzo_firma,
            r.almuerzo_aclaracion,
            r.cena_hora,
            r.cena_firma,
            r.cena_aclaracion,
            p.nombre_apellido,
            u.nombre_usuario AS usuario_nombre,
            s.nombre AS sector_nombre
        FROM recepcion_alimentos r
        INNER JOIN ppl p ON r.dni_ppl = p.dni
        INNER JOIN usuarios u ON r.id_usuario = u.id_usuario
        INNER JOIN sector s ON r.id_sector = s.id
        WHERE 1=1";

// Filtrar por DNI o nombre del PPL si se buscó algo
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre_apellido LIKE '%$busqueda%' OR p.dni LIKE '%$busqueda%')";
}

// Ordenar por fecha más reciente
$sql .= " ORDER BY r.fecha DESC, r.id DESC";

// Ejecutar consulta
$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recepción de Alimentos - Sistema de Nutrición</title>

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

        /* Tabla de recepciones */
        .table-container {
            flex: 1;
            background: white;
            margin: 0 30px 30px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            min-width: 1400px; /* Aumentado para que quepan todas las columnas */
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
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            color: #000000;
            vertical-align: top;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Columnas específicas con anchos fijos */
        .col-fecha { width: 100px; }
        .col-dia { width: 90px; }
        .col-dni { width: 100px; }
        .col-nombre { width: 180px; }
        .col-sector { width: 120px; }
        .col-comida { width: 200px; min-width: 200px; }
        .col-acciones { width: 180px; }

        /* Badges para comidas */
        .comida-info {
            margin-bottom: 6px;
            padding: 6px;
            border-radius: 4px;
            background: #f8f9fa;
            font-size: 0.8rem;
        }

        .comida-header {
            font-weight: 600;
            margin-bottom: 3px;
            color: #000000;
            font-size: 0.8rem;
        }

        .comida-detail {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        /* Día de la semana */
        .dia-badge {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            background: #e0e0e0;
            color: #000000;
            display: inline-block;
            min-width: 70px;
            text-align: center;
        }

        /* Botones de acción */
        .actions-cell {
            white-space: nowrap;
            position: sticky;
            right: 0;
            background: white;
            z-index: 1;
        }

        .data-table tr:hover .actions-cell {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }

        .btn-action {
            padding: 6px 10px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            text-decoration: none;
            color: #000000;
            background: white;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 35px;
            height: 32px;
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

        /* Scroll personalizado */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
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
                min-width: 1200px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 12px;
            }

            .col-comida { 
                width: 180px; 
                min-width: 180px;
            }

            .btn-action {
                min-width: 32px;
                padding: 5px 8px;
                height: 30px;
            }
        }

        /* Estilos para mejorar la visualización en pantallas grandes */
        @media (min-width: 1400px) {
            .table-container {
                overflow-x: visible;
            }
            
            .data-table {
                min-width: 100%;
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
                <h1>🏭 Recepción de Alimentos</h1>
            </div>

            <!-- Barra de búsqueda y botón agregar -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" 
                           placeholder="Buscar por DNI o nombre del interno..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-success">➕ Nueva Recepción</a>
            </div>

            <!-- Mensaje de éxito si existe -->
            <?php if (isset($_SESSION['mensaje_exito'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de recepciones -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-fecha">Fecha</th>
                            <th class="col-dia">Día</th>
                            <th class="col-dni">DNI</th>
                            <th class="col-nombre">Nombre</th>
                            <th class="col-sector">Sector</th>
                            <th class="col-comida">Desayuno</th>
                            <th class="col-comida">Almuerzo</th>
                            
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <!-- Fecha formateada -->
                                    <td class="col-fecha"><strong><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></strong></td>

                                    <!-- Día de la semana -->
                                    <td class="col-dia">
                                        <?php
                                        $dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                                        $dia_nombre = $dias[date('w', strtotime($fila['fecha']))];
                                        ?>
                                        <span class="dia-badge"><?php echo $dia_nombre; ?></span>
                                    </td>

                                    <!-- DNI del interno -->
                                    <td class="col-dni"><?php echo $fila['dni_ppl']; ?></td>

                                    <!-- Nombre completo del interno -->
                                    <td class="col-nombre"><?php echo htmlspecialchars($fila['nombre_apellido']); ?></td>

                                    <!-- Sector -->
                                    <td class="col-sector"><?php echo htmlspecialchars($fila['sector_nombre']); ?></td>

                                    <!-- Desayuno -->
                                    <td class="col-comida">
                                        <?php if ($fila['desayuno_hora'] || $fila['desayuno_firma']): ?>
                                            <div class="comida-info">
                                                <div class="comida-header">Desayuno</div>
                                                <?php if ($fila['desayuno_hora']): ?>
                                                    <div class="comida-detail">🕒 <?php echo date('H:i', strtotime($fila['desayuno_hora'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($fila['desayuno_firma']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['desayuno_firma']); ?>">
                                                        ✍️ <?php echo htmlspecialchars(substr($fila['desayuno_firma'], 0, 12)) . (strlen($fila['desayuno_firma']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($fila['desayuno_aclaracion']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['desayuno_aclaracion']); ?>">
                                                        📝 <?php echo htmlspecialchars(substr($fila['desayuno_aclaracion'], 0, 12)) . (strlen($fila['desayuno_aclaracion']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Almuerzo -->
                                    <td class="col-comida">
                                        <?php if ($fila['almuerzo_hora'] || $fila['almuerzo_firma']): ?>
                                            <div class="comida-info">
                                                <div class="comida-header">Almuerzo</div>
                                                <?php if ($fila['almuerzo_hora']): ?>
                                                    <div class="comida-detail">🕒 <?php echo date('H:i', strtotime($fila['almuerzo_hora'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($fila['almuerzo_firma']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['almuerzo_firma']); ?>">
                                                        ✍️ <?php echo htmlspecialchars(substr($fila['almuerzo_firma'], 0, 12)) . (strlen($fila['almuerzo_firma']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($fila['almuerzo_aclaracion']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['almuerzo_aclaracion']); ?>">
                                                        📝 <?php echo htmlspecialchars(substr($fila['almuerzo_aclaracion'], 0, 12)) . (strlen($fila['almuerzo_aclaracion']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Cena -->
                                    <td class="col-comida">
                                        <?php if ($fila['cena_hora'] || $fila['cena_firma']): ?>
                                            <div class="comida-info">
                                                <div class="comida-header">Cena</div>
                                                <?php if ($fila['cena_hora']): ?>
                                                    <div class="comida-detail">🕒 <?php echo date('H:i', strtotime($fila['cena_hora'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($fila['cena_firma']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['cena_firma']); ?>">
                                                        ✍️ <?php echo htmlspecialchars(substr($fila['cena_firma'], 0, 12)) . (strlen($fila['cena_firma']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($fila['cena_aclaracion']): ?>
                                                    <div class="comida-detail" title="<?php echo htmlspecialchars($fila['cena_aclaracion']); ?>">
                                                        📝 <?php echo htmlspecialchars(substr($fila['cena_aclaracion'], 0, 12)) . (strlen($fila['cena_aclaracion']) > 12 ? '...' : ''); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #999; font-size: 0.8rem;">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Botones de acción -->
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Ver detalles">👁️</a>
                                            <a href="editar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Generar PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar esta recepción?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="no-data">
                                    <p>🏭 No se encontraron recepciones de alimentos</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primera recepción</a>
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