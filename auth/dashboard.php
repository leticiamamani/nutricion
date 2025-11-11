<?php
// Iniciar sesión y verificar autenticación
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit();
}

// Incluir conexión a base de datos
require_once '../includes/conexion.php';

$rol = $_SESSION['permisos'];
$nombre = htmlspecialchars($_SESSION['nombre_usuario']);
?>

<?php include '../includes/header.php'; ?>

<!-- Encabezado personalizado -->
<div class="dashboard-header admin-header">
    <h1>👩‍⚕️ Panel de Control - Administradora</h1>
    <p>Bienvenida, Lic. Daniela Fullana - Responsable del Área de Nutrición</p>
</div>

<!-- Información rápida del usuario -->
<div class="user-quick-info">
    <div class="info-card">
        <span class="info-label">Usuario:</span>
        <span class="info-value"><?php echo $nombre; ?></span>
    </div>
    <div class="info-card">
        <span class="info-label">Rol:</span>
        <span class="info-value"><?php echo $rol; ?></span>
    </div>
    <div class="info-card">
        <span class="info-label">Último acceso:</span>
        <span class="info-value"><?php echo date('d/m/Y H:i'); ?></span>
    </div>
</div>



<!-- Actividad reciente -->
<div class="recent-activity">
    <h2>Actividad Reciente</h2>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>PPL</th>
                    <th>Sector</th>
                    <th>Usuario</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT a.fecha, 'Atención Nutricional' as tipo, p.nombre_apellido, s.nombre as sector, u.nombre_usuario 
                        FROM atencion_nutricional a 
                        JOIN ppl p ON a.dni_ppl = p.dni 
                        JOIN usuarios u ON a.id_usuario = u.id_usuario 
                        LEFT JOIN sector s ON a.id_sector = s.id 
                        UNION ALL
                        SELECT n.fecha, 'Novedad' as tipo, p.nombre_apellido, s.nombre as sector, u.nombre_usuario 
                        FROM acta_novedad n 
                        JOIN ppl p ON n.dni_ppl = p.dni 
                        JOIN usuarios u ON n.id_usuario = u.id_usuario 
                        LEFT JOIN sector s ON n.id_sector = s.id 
                        ORDER BY fecha DESC 
                        LIMIT 10";
                $result = mysqli_query($conexion, $query);
                
                if (mysqli_num_rows($result) > 0) {
                    while ($fila = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $fila['fecha'] . "</td>";
                        echo "<td><span class='badge'>" . $fila['tipo'] . "</span></td>";
                        echo "<td>" . $fila['nombre_apellido'] . "</td>";
                        echo "<td>" . ($fila['sector'] ?: 'N/A') . "</td>";
                        echo "<td>" . $fila['nombre_usuario'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5' class='no-data'>No hay actividad reciente</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>