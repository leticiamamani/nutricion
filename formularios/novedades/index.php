<?php
// Verificar autenticación (todos los roles tienen acceso)
require_once '../../includes/auth-check.php';

// Incluir conexión
require_once '../../includes/conexion.php';

// Variables de búsqueda
$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

// Construir consulta base
$sql = "SELECT an.*, p.nombre_apellido, p.dni, s.nombre as sector_nombre, 
               pa.nombre as pabellon_nombre, u.nombre_usuario as usuario_nombre
        FROM acta_novedad an
        INNER JOIN ppl p ON an.dni_ppl = p.dni
        LEFT JOIN sector s ON an.id_sector = s.id
        LEFT JOIN pabellon pa ON an.id_pabellon = pa.id
        INNER JOIN usuarios u ON an.id_usuario = u.id_usuario
        WHERE 1=1";

// Aplicar filtros de búsqueda
if (!empty($busqueda)) {
    $sql .= " AND (p.nombre_apellido LIKE '%$busqueda%' OR p.dni LIKE '%$busqueda%')";
}

$sql .= " ORDER BY an.fecha DESC, an.hora DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novedades - Sistema de Nutrición</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
    /* Estilos específicos para esta página */
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
    
    .table-container {
        flex: 1;
        background: white;
        margin: 0 30px 30px 30px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
        min-width: 900px;
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
    
    .novedad-cell {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
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
            <div class="content-header">
                <h1>📝 Actas de Novedades</h1>
            </div>

            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" 
                           placeholder="Buscar por DNI o nombre del interno..."
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-success">
                    ➕ Nueva Novedad
                </a>
            </div>

            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>DNI Interno</th>
                            <th>Nombre</th>
                            <th>Sector</th>
                            <th>Novedad</th>
                            <th>Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($novedad = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($novedad['fecha'])); ?></strong></td>
                                    <td><?php echo date('H:i', strtotime($novedad['hora'])); ?></td>
                                    <td><?php echo $novedad['dni']; ?></td>
                                    <td><?php echo htmlspecialchars($novedad['nombre_apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($novedad['sector_nombre'] ?: 'N/A'); ?></td>
                                    <td class="novedad-cell" title="<?php echo htmlspecialchars($novedad['detalle_novedad']); ?>">
                                        <?php echo htmlspecialchars(substr($novedad['detalle_novedad'], 0, 50)) . '...'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($novedad['usuario_nombre']); ?></td>
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $novedad['id']; ?>" class="btn-action" title="Ver detalles">👁️</a>
                                            <a href="editar.php?id=<?php echo $novedad['id']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $novedad['id']; ?>" class="btn-action" title="Generar PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $novedad['id']; ?>" class="btn-action" 
                                               onclick="return confirm('¿Está seguro de eliminar esta novedad?')" title="Eliminar">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <p>📝 No se encontraron novedades</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <p>Intente con otros términos de búsqueda</p>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primera novedad</a>
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