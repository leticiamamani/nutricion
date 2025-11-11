<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificar permisos para novedades
verificarNovedades();

// Obtener lista de internos para el select
$query_internos = "SELECT dni, nombre_apellido, estado_legal FROM ppl WHERE estado = 'ACTIVO' ORDER BY nombre_apellido";
$result_internos = $conexion->query($query_internos);

// Obtener sectores para el select
$query_sectores = "SELECT id, nombre FROM sector WHERE estado = 'ACTIVO' ORDER BY nombre";
$result_sectores = $conexion->query($query_sectores);

// Obtener pabellones para el select
$query_pabellones = "SELECT id, nombre FROM pabellon WHERE estado = 'ACTIVO' ORDER BY nombre";
$result_pabellones = $conexion->query($query_pabellones);

// Inicializar variables para el formulario
$errores = [];
$datos = [
    'dni_ppl' => '',
    'fecha' => date('Y-m-d'),
    'hora' => date('H:i'),
    'id_sector' => '',
    'id_pabellon' => '',
    'detalle_novedad' => '',
    'descargos_ppl' => ''
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar los datos del formulario
    $datos['dni_ppl'] = trim($_POST['dni_ppl'] ?? '');
    $datos['fecha'] = trim($_POST['fecha'] ?? '');
    $datos['hora'] = trim($_POST['hora'] ?? '');
    $datos['id_sector'] = trim($_POST['id_sector'] ?? '');
    $datos['id_pabellon'] = trim($_POST['id_pabellon'] ?? '');
    $datos['detalle_novedad'] = trim($_POST['detalle_novedad'] ?? '');
    $datos['descargos_ppl'] = trim($_POST['descargos_ppl'] ?? '');

    // Validaciones
    if (empty($datos['dni_ppl'])) {
        $errores[] = "El campo interno es obligatorio.";
    }
    
    if (empty($datos['fecha'])) {
        $errores[] = "El campo fecha es obligatorio.";
    }
    
    if (empty($datos['hora'])) {
        $errores[] = "El campo hora es obligatorio.";
    }
    
    if (empty($datos['id_sector'])) {
        $errores[] = "El campo sector es obligatorio.";
    }
    
    if (empty($datos['id_pabellon'])) {
        $errores[] = "El campo pabellón es obligatorio.";
    }

    if (empty($datos['detalle_novedad'])) {
        $errores[] = "El detalle de la novedad es obligatorio.";
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        // Obtener ID del usuario actual
        $id_usuario = $_SESSION['id_usuario'];
        
        // Insertar en la base de datos
        $sql = "INSERT INTO acta_novedad 
                (dni_ppl, fecha, hora, id_sector, id_pabellon, detalle_novedad, descargos_ppl, id_usuario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param(
                "sssiissi",
                $datos['dni_ppl'],
                $datos['fecha'],
                $datos['hora'],
                $datos['id_sector'],
                $datos['id_pabellon'],
                $datos['detalle_novedad'],
                $datos['descargos_ppl'],
                $id_usuario
            );
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "✅ Acta de novedad guardada correctamente";
                header("Location: index.php");
                exit();
            } else {
                $errores[] = "Error al guardar el registro: " . $conexion->error;
            }
        } else {
            $errores[] = "Error en la preparación de la consulta: " . $conexion->error;
        }
    }
}

// Función para mostrar errores
function mostrarError($mensaje) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; text-align: center;'>
            <strong>Error:</strong> $mensaje
          </div>";
}

// Obtener fecha actual para mostrar en formato del acta
$fecha_actual = date('Y-m-d');
$dia_actual = date('d');
$mes_actual = date('m');
$anio_actual = date('Y');
$hora_actual = date('H:i');

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
    <title>Agregar Acta de Novedad</title>
    <style>
        /* Estilos para simular el formato del documento Word A4 */
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
        
        .form-group {
            margin-bottom: 10px;
            display: inline;
        }
        
        label {
            display: inline-block;
            margin-right: 5px;
            font-weight: normal;
        }
        
        input, select, textarea {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            padding: 2px 5px;
            margin: 0 5px;
            display: inline-block;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            background-color: #f0f8ff;
        }
        
        .inline-field {
            display: inline-block;
        }
        
        .text-area-container {
            margin: 15px 0;
        }
        
        .text-area-container textarea {
            width: 100%;
            border: 1px solid #000;
            padding: 8px;
            margin: 5px 0;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            resize: vertical;
            min-height: 80px;
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
        
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-family: Arial, sans-serif;
        }
        
        .error ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            color: #666;
            font-size: 10pt;
            font-family: Arial, sans-serif;
        }
        
        .field-small {
            width: 50px;
        }
        
        .field-time {
            width: 70px;
        }
        
        .field-medium {
            width: 120px;
        }
        
        .field-large {
            width: 300px;
        }
        
        .field-interno {
            width: 350px;
        }
        
        .field-estado-legal {
            width: 150px;
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
        
        .section-title {
            font-weight: bold;
            margin: 20px 0 10px 0;
            text-decoration: underline;
        }
        
        .dashed-line {
            border-bottom: 1px dashed #000;
            margin: 10px 0;
            height: 1px;
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

        <?php if (!empty($errores)): ?>
            <div class="error">
                <strong>Errores encontrados:</strong>
                <ul>
                    <?php foreach ($errores as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="acta-content">
                <div class="acta-paragraph">
                    // En la provincia de San Juan, sede del Servicio Penitenciario Provincial departamento Chimbas, siendo las
                    <input type="time" id="hora" name="hora" class="inline-field field-time" 
                           value="<?php echo $hora_actual; ?>" required> hs, a los
                    <input type="number" id="dia" name="dia" class="inline-field field-small" min="1" max="31" 
                           value="<?php echo $dia_actual; ?>"> días del mes de 
                    <select id="mes" name="mes" class="inline-field field-medium">
                        <?php foreach ($meses as $num => $nombre): ?>
                            <option value="<?php echo $num; ?>" <?php echo ($mes_actual == $num) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select> 
                    del año 
                    <input type="number" id="anio" name="anio" class="inline-field field-small" 
                           value="<?php echo $anio_actual; ?>">
                    se labra la presente acta a fin de dejar constancia que la P.P.L. 
                    
                    <select id="dni_ppl" name="dni_ppl" class="inline-field field-interno" required onchange="actualizarEstadoLegal()">
                        <option value="">Seleccione un interno</option>
                        <?php 
                        if ($result_internos) {
                            while ($interno = $result_internos->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($interno['dni']) . '" data-estado-legal="' . htmlspecialchars($interno['estado_legal']) . '"';
                                echo ($datos['dni_ppl'] == $interno['dni']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($interno['nombre_apellido']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    alojado/a en el Sector Nº 
                    <select id="id_sector" name="id_sector" class="inline-field field-medium" required>
                        <option value="">Seleccione</option>
                        <?php 
                        if ($result_sectores) {
                            while ($sector = $result_sectores->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($sector['id']) . '"';
                                echo ($datos['id_sector'] == $sector['id']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($sector['nombre']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    pabellón Nº 
                    <select id="id_pabellon" name="id_pabellon" class="inline-field field-medium" required>
                        <option value="">Seleccione</option>
                        <?php 
                        if ($result_pabellones) {
                            while ($pabellon = $result_pabellones->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($pabellon['id']) . '"';
                                echo ($datos['id_pabellon'] == $pabellon['id']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($pabellon['nombre']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    que ante la novedad que se detalla:
                </div>

                <div class="text-area-container">
                    <textarea id="detalle_novedad" name="detalle_novedad" placeholder="Describa detalladamente la novedad ocurrida..." required><?php echo htmlspecialchars($datos['detalle_novedad']); ?></textarea>
                </div>

                <div class="acta-paragraph">
                    Se solicita al interno que explique las razones a fines de realizar los descargos respectivos en la presente acta manifestando lo siguiente:
                </div>

                <div class="text-area-container">
                    <textarea id="descargos_ppl" name="descargos_ppl" placeholder="Descargos del interno (opcional)..."><?php echo htmlspecialchars($datos['descargos_ppl']); ?></textarea>
                </div>

                <div class="acta-paragraph">
                    Evaluarán los actuados relacionados a la novedad producida y posteriormente se le notificará lo determinado por la administración penitenciaria.
                </div>

                <div class="acta-paragraph">
                    Sin más se da por finalizado el acto, firmado al pie al interno de referencia, dos copias de un mismo tenor y a un solo efecto por ante mi funcionario efectuante, lo que CERTIFICO-
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn">Guardar Acta</button>
            </div>

            <!-- Campos ocultos para la fecha completa -->
            <input type="hidden" id="fecha" name="fecha" value="<?php echo $fecha_actual; ?>">
        </form>
    </div>

    <script>
        // Función para actualizar el estado legal cuando se selecciona un interno
        function actualizarEstadoLegal() {
            const selectInterno = document.getElementById('dni_ppl');
            const selectEstadoLegal = document.getElementById('estado_legal_display');
            const selectedOption = selectInterno.options[selectInterno.selectedIndex];
            
            if (selectedOption.value !== '') {
                const estadoLegal = selectedOption.getAttribute('data-estado-legal');
                selectEstadoLegal.value = estadoLegal;
            } else {
                selectEstadoLegal.value = 'PENADO';
            }
        }

        // Actualizar fecha completa cuando cambien día, mes o año
        function actualizarFecha() {
            const dia = document.getElementById('dia').value;
            const mes = document.getElementById('mes').value;
            const anio = document.getElementById('anio').value;
            
            if (dia && mes && anio) {
                const fechaCompleta = anio + '-' + mes + '-' + dia.padStart(2, '0');
                document.getElementById('fecha').value = fechaCompleta;
            }
        }

        // Inicializar estado legal al cargar la página
        window.addEventListener('load', function() {
            actualizarEstadoLegal();
            actualizarFecha();
        });

        document.getElementById('dia').addEventListener('change', actualizarFecha);
        document.getElementById('mes').addEventListener('change', actualizarFecha);
        document.getElementById('anio').addEventListener('change', actualizarFecha);
        document.getElementById('dni_ppl').addEventListener('change', actualizarEstadoLegal);
    </script>
</body>
</html>