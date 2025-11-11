<?php
// Solo ADMIN y NUTRICION
require_once '../../includes/auth-check.php';
verificarHuelgaHambre();

require_once '../../includes/conexion.php';

$busqueda = isset($_GET['busqueda']) ? mysqli_real_escape_string($conexion, $_GET['busqueda']) : '';

$sql = "SELECT h.*, p.nombre_apellido, p.dni, s.nombre as sector_nombre, pa.nombre as pabellon_nombre, u.nombre_usuario as usuario_nombre
        FROM huelga_hambre h
        INNER JOIN ppl p ON h.dni_ppl = p.dni
        LEFT JOIN sector s ON h.id_sector = s.id
        LEFT JOIN pabellon pa ON h.id_pabellon = pa.id
        INNER JOIN usuarios u ON h.id_usuario = u.id_usuario
        WHERE 1=1";

if (!empty($busqueda)) {
    $sql .= " AND (p.nombre_apellido LIKE '%$busqueda%' OR p.dni LIKE '%$busqueda%')";
}

$sql .= " ORDER BY h.fecha DESC";

$resultado = mysqli_query($conexion, $sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Huelgas de Hambre - Sistema de Nutrición</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* (Mismo estilo que novedades) */
        .page-container { display:flex; min-height:100vh; background:#f8f9fa; }
        .main-content-area { flex:1; display:flex; flex-direction:column; }
        .content-header { background:white; padding:25px 30px; }
        .content-header h1 { color:#000; font-size:1.6rem; font-weight:700; margin:0; }
        .search-section { background:white; padding:20px 30px; display:flex; justify-content:space-between; align-items:center; gap:20px; }
        .search-form { display:flex; gap:10px; flex:1; max-width:400px; }
        .search-form input { flex:1; padding:10px 12px; border:1px solid #e0e0e0; border-radius:4px; font-size:0.95rem; }
        .btn { padding:10px 20px; border:none; border-radius:4px; cursor:pointer; font-size:0.9rem; text-decoration:none; display:inline-flex; align-items:center; gap:5px; font-weight:500; }
        .btn-primary { background:#000; color:white; }
        .btn-success { background:#000; color:white; }
        .table-container { flex:1; background:white; margin:0 30px 30px 30px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1); overflow:auto; }
        .data-table { width:100%; border-collapse:collapse; font-size:0.9rem; min-width:900px; }
        .data-table th { background:#f8f9fa; padding:15px 20px; text-align:left; font-weight:700; color:#000; border-bottom:2px solid #e0e0e0; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.5px; white-space:nowrap; }
        .data-table td { padding:15px 20px; border-bottom:1px solid #f0f0f0; color:#000; vertical-align:middle; }
        .data-table tr:hover { background-color:#f8f9fa; }
        .imc-badge { padding:6px 10px; border-radius:12px; font-size:0.8rem; font-weight:600; color:white; display:inline-block; min-width:55px; text-align:center; }
        .imc-bajo { background:#ffc107; color:#000; }
        .imc-normal { background:#28a745; }
        .imc-sobrepeso { background:#fd7e14; }
        .imc-obeso { background:#dc3545; }
        .actions-cell { white-space:nowrap; }
        .action-buttons { display:flex; gap:8px; }
        .btn-action { padding:8px 12px; border:1px solid #e0e0e0; border-radius:4px; text-decoration:none; color:#000; background:white; transition:all 0.3s ease; font-size:0.85rem; display:inline-flex; align-items:center; justify-content:center; min-width:40px; }
        .btn-action:hover { background:#f0f0f0; border-color:#000; transform:translateY(-1px); }
        .no-data { text-align:center; padding:60px 20px; color:#666; }
        .btn-small { padding:8px 16px; background:#000; color:white; text-decoration:none; border-radius:4px; font-size:0.9rem; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    <div class="page-container">
        <div class="main-content-area">
            <div class="content-header">
                <h1>🚫 Huelgas de Hambre</h1>
            </div>
            <div class="search-section">
                <form method="GET" class="search-form">
                    <input type="text" name="busqueda" placeholder="Buscar por DNI o nombre..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                </form>
                <a href="agregar.php" class="btn btn-success">➕ Nueva Huelga</a>
            </div>
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
            <?php endif; ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>DNI</th>
                            <th>Nombre</th>
                            <th>Sector</th>
                            <th>Peso (kg)</th>
                            <th>Talla (m)</th>
                            <th>IMC</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($resultado) > 0): ?>
                            <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <td><strong><?php echo date('d/m/Y', strtotime($fila['fecha'])); ?></strong></td>
                                    <td><?php echo $fila['dni']; ?></td>
                                    <td><?php echo htmlspecialchars($fila['nombre_apellido']); ?></td>
                                    <td><?php echo htmlspecialchars($fila['sector_nombre'] ?: 'N/A'); ?></td>
                                    <td><?php echo $fila['peso_kg'] ? number_format($fila['peso_kg'], 1) : '-'; ?></td>
                                    <td><?php echo $fila['talla_m'] ? number_format($fila['talla_m'], 2) : '-'; ?></td>
                                    <td>
                                        <?php if ($fila['imc']): ?>
                                            <?php
                                            $imc = $fila['imc'];
                                            $clase = '';
                                            if ($imc < 18.5) $clase = 'imc-bajo';
                                            elseif ($imc < 25) $clase = 'imc-normal';
                                            elseif ($imc < 30) $clase = 'imc-sobrepeso';
                                            else $clase = 'imc-obeso';
                                            ?>
                                            <span class="imc-badge <?php echo $clase; ?>"><?php echo number_format($imc, 1); ?></span>
                                        <?php else: echo '-'; endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <div class="action-buttons">
                                            <a href="ver.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Ver">👁️</a>
                                            <a href="editar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Editar">✏️</a>
                                            <a href="pdf.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="PDF" target="_blank">📄</a>
                                            <a href="eliminar.php?id=<?php echo $fila['id']; ?>" class="btn-action" title="Eliminar" onclick="return confirm('¿Está seguro?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-data">
                                    <p>🚫 No se encontraron registros de huelga de hambre</p>
                                    <?php if (!empty($busqueda)): ?>
                                        <a href="index.php" class="btn-small">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        <a href="agregar.php" class="btn-small">Registrar primera huelga</a>
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