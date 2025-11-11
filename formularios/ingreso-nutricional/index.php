<?php
/**
 * INGRESO NUTRICIONAL - index.php
 * Listado de ingresos nutricionales de PPL
 * Acceso: Solo ADMIN y NUTRICION
 */

// Verificar autenticación y permisos
require_once '../../includes/auth-check.php';
verificarIngresoNutricional(); // Función que permite solo ADMIN y NUTRICION

// Conexión a base de datos
require_once '../../includes/conexion.php';

// Capturar búsqueda si existe
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

// Consulta principal con JOIN a PPL, sector, pabellón, usuario y tipo de dieta
$sql = "SELECT 
            i.id,
            i.dni_ppl,
            i.fecha_ingreso,
            i.peso_kg,
            i.talla_m,
            i.imc,
            i.diagnostico,
            i.antecedentes_pat,
            i.certificacion_med,
            p.nombre_apellido,
            s.nombre AS sector_nombre,
            pa.nombre AS pabellon_nombre,
            td.nombre_dieta,
            u.nombre_usuario AS usuario_nombre
        FROM ingreso_nutricional i
        INNER JOIN ppl p ON i.dni_ppl = p.dni
        LEFT JOIN sector s ON p.id_sector = s.id
        LEFT JOIN pabellon pa ON p.id_pabellon = pa.id
        LEFT JOIN tipos_dieta td ON i.id_dieta = td.id_dieta
        INNER JOIN usuarios u ON i.id_usuario = u.id_usuario
        WHERE 1=1";

// Filtrar por DNI o nombre si se buscó algo
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre_apellido LIKE '%$busqueda%' OR p.dni LIKE '%$busqueda%')";
}

// Ordenar por fecha de ingreso más reciente
$sql .= " ORDER BY i.fecha_ingreso DESC";

// Ejecutar consulta
$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresos Nutricionales - Sistema de Nutrición</title>

    <!-- Estilos generales del sistema -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <!-- Estilos específicos de esta página (mismo diseño que novedades) -->
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

        /* Tabla de datos */
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
            min-width: 1000px;
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

        /* IMC con colores */
        .imc-badge {
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
            display: inline-block;
            min-width: 55px;
            text-align: center;
        }

        .imc-bajo { background: #ffc107; color: #000000; }
        .imc-normal { background: #28a745; }
        .imc-sobrepeso { background: #fd7e14; }
        .imc-obeso { background: #dc3545; }

        /* Celdas especiales */
        .dieta-cell {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .antecedentes-cell {
            max-width: 250px;
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
                min-width: 800px;
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
                <h1>👤 Ingresos Nutricionales</h1>
            </div>

            <!-- Barra de búsqueda y botón agregar -->
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" 
                           placeholder="Buscar por DNI o nombre del interno..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-success">➕ Nuevo Ingreso</a>
            </div>

            <!-- Mensaje de éxito si existe -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>

            <!-- Tabla de ingresos nutricionales -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha Ingreso</th>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Sector</th>
                            <th>Peso (kg)</th>
                            <th>Talla (m)</th>
                            <th>IMC</th>
                            <th>Dieta Asignada</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <!-- Fecha de ingreso formateada -->
                                    <td><strong><?php echo date('d/m/Y', strtotime($fila['fecha_ingreso'])); ?></strong></td>

                                    <!-- DNI del interno -->
                                    <td><?php echo $fila['dni_ppl']; ?></td>

                                    <!-- Nombre completo del interno -->
                                    <td><?php echo htmlspecialchars($fila['nombre_apellido']); ?></td>

                                    <!-- Sector y pabellón -->
                                    <td><?php echo htmlspecialchars($fila['sector_nombre'] . ' - ' . $fila['pabellon_nombre']); ?></td>

                                    <!-- Peso -->
                                    <td><?php echo $fila['peso_kg'] ? number_format($fila['peso_kg'], 1) : '-'; ?></td>

                                    <!-- Talla -->
                                    <td><?php echo $fila['talla_m'] ? number_format($fila['talla_m'], 2) : '-'; ?></td>

                                    <!-- IMC con color según valor -->
                                    <td>
                                        <?php if ($fila['imc']): ?>
                                            <?php
                                            $imc = $fila['imc'];
                                            $clase_imc = '';
                                            if ($imc < 18.5) $clase_imc = 'imc-bajo';
                                            elseif ($imc < 25) $clase_imc = 'imc-normal';
                                            elseif ($imc < 30) $clase_imc = 'imc-sobrepeso';
                                            else $clase_imc = 'imc-obeso';
                                            ?>
                                            <span class="imc-badge <?php echo $clase_imc; ?>">
                                                <?php echo number_format($imc, 1); ?>
                                            </span>
                                        <?php else: echo '-'; endif; ?>
                                    </td>

                                    <!-- Dieta asignada -->
                                    <td class="dieta-cell" title="<?php echo htmlspecialchars($fila['nombre_dieta']); ?>">
                                        <?php echo $fila['nombre_dieta'] ? htmlspecialchars($fila['nombre_dieta']) : '-'; ?>
                                    </td>

                                    

                                    <!-- Botones de acción -->
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Ver detalles">👁️</a>
                                            <a href="editar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Generar PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Eliminar" onclick="return confirm('¿Está seguro de eliminar este ingreso?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="no-data">
                                    <p>👤 No se encontraron ingresos nutricionales</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primer ingreso</a>
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