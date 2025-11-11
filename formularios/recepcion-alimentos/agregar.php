<?php
/**
 * FORMULARIO DE RECEPCIÓN DE ALIMENTOS - GUARDADO POR DÍA INDIVIDUAL
 * Sistema para registrar la recepción de alimentos por parte de los internos
 * Guarda un solo día por formulario
 * 
 * @author [Tu Nombre]
 * @version 3.0 - Guardado por día individual
 * @package SistemaNutricion
 */

// Incluir archivos de configuración y seguridad
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener lista de internos activos para el select
$query_internos = "SELECT dni, nombre_apellido FROM ppl WHERE estado = 'ACTIVO' ORDER BY nombre_apellido";
$result_internos = $conexion->query($query_internos);

// Obtener sectores activos para el select
$query_sectores = "SELECT id, nombre FROM sector WHERE estado = 'ACTIVO' ORDER BY nombre";
$result_sectores = $conexion->query($query_sectores);

// Inicializar variables
$errores = [];
$datos = [
    'dni_ppl' => '',
    'sector' => '',
    'fecha' => '',
    // Desayuno
    'desayuno_hora' => '',
    'desayuno_firma' => '',
    'desayuno_aclaracion' => '',
    // Almuerzo
    'almuerzo_hora' => '',
    'almuerzo_firma' => '',
    'almuerzo_aclaracion' => '',
    // Cena
    'cena_hora' => '',
    'cena_firma' => '',
    'cena_aclaracion' => ''
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar los datos del formulario
    $datos['dni_ppl'] = trim($_POST['dni_ppl'] ?? '');
    $datos['sector'] = trim($_POST['sector'] ?? '');
    $datos['fecha'] = trim($_POST['fecha'] ?? '');
    
    // Desayuno
    $datos['desayuno_hora'] = trim($_POST['desayuno_hora'] ?? '');
    $datos['desayuno_firma'] = trim($_POST['desayuno_firma'] ?? '');
    $datos['desayuno_aclaracion'] = trim($_POST['desayuno_aclaracion'] ?? '');
    
    // Almuerzo
    $datos['almuerzo_hora'] = trim($_POST['almuerzo_hora'] ?? '');
    $datos['almuerzo_firma'] = trim($_POST['almuerzo_firma'] ?? '');
    $datos['almuerzo_aclaracion'] = trim($_POST['almuerzo_aclaracion'] ?? '');
    
    // Cena
    $datos['cena_hora'] = trim($_POST['cena_hora'] ?? '');
    $datos['cena_firma'] = trim($_POST['cena_firma'] ?? '');
    $datos['cena_aclaracion'] = trim($_POST['cena_aclaracion'] ?? '');

    // Validaciones básicas
    if (empty($datos['dni_ppl'])) {
        $errores[] = "El campo interno es obligatorio.";
    }
    
    if (empty($datos['sector'])) {
        $errores[] = "El campo sector es obligatorio.";
    }
    
    if (empty($datos['fecha'])) {
        $errores[] = "El campo fecha es obligatorio.";
    }

    // Validar que al menos haya datos de una comida
    $hay_datos_comida = !empty($datos['desayuno_hora']) || !empty($datos['desayuno_firma']) || 
                       !empty($datos['almuerzo_hora']) || !empty($datos['almuerzo_firma']) ||
                       !empty($datos['cena_hora']) || !empty($datos['cena_firma']);
    
    if (!$hay_datos_comida) {
        $errores[] = "Debe completar al menos los datos de una comida (hora o firma).";
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        // Obtener ID del usuario actual
        $id_usuario = $_SESSION['id_usuario'];
        
        try {
            // Verificar si ya existe un registro para este DNI y fecha (evitar duplicados)
            $sql_check = "SELECT id FROM recepcion_alimentos WHERE dni_ppl = ? AND fecha = ?";
            $stmt_check = $conexion->prepare($sql_check);
            $stmt_check->bind_param("ss", $datos['dni_ppl'], $datos['fecha']);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                // Ya existe un registro para este día, actualizar en lugar de insertar
                $sql = "UPDATE recepcion_alimentos SET 
                        desayuno_hora = ?, desayuno_firma = ?, desayuno_aclaracion = ?,
                        almuerzo_hora = ?, almuerzo_firma = ?, almuerzo_aclaracion = ?,
                        cena_hora = ?, cena_firma = ?, cena_aclaracion = ?,
                        id_usuario = ?, id_sector = ?
                        WHERE dni_ppl = ? AND fecha = ?";
                
                $stmt = $conexion->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param(
                        "sssssssssiiss",
                        $datos['desayuno_hora'],
                        $datos['desayuno_firma'],
                        $datos['desayuno_aclaracion'],
                        $datos['almuerzo_hora'],
                        $datos['almuerzo_firma'],
                        $datos['almuerzo_aclaracion'],
                        $datos['cena_hora'],
                        $datos['cena_firma'],
                        $datos['cena_aclaracion'],
                        $id_usuario,
                        $datos['sector'],
                        $datos['dni_ppl'],
                        $datos['fecha']
                    );
                    
                    if ($stmt->execute()) {
                        $_SESSION['mensaje_exito'] = "✅ Recepción actualizada correctamente para la fecha " . date('d/m/Y', strtotime($datos['fecha']));
                        header("Location: index.php");
                        exit();
                    } else {
                        throw new Exception("Error al actualizar el registro: " . $conexion->error);
                    }
                    
                    $stmt->close();
                }
            } else {
                // No existe registro, insertar nuevo
                $sql = "INSERT INTO recepcion_alimentos 
                        (dni_ppl, fecha, 
                         desayuno_hora, desayuno_firma, desayuno_aclaracion,
                         almuerzo_hora, almuerzo_firma, almuerzo_aclaracion,
                         cena_hora, cena_firma, cena_aclaracion,
                         id_usuario, id_sector) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conexion->prepare($sql);
                
                if ($stmt) {
                    $stmt->bind_param(
                        "sssssssssssii",
                        $datos['dni_ppl'],
                        $datos['fecha'],
                        $datos['desayuno_hora'],
                        $datos['desayuno_firma'],
                        $datos['desayuno_aclaracion'],
                        $datos['almuerzo_hora'],
                        $datos['almuerzo_firma'],
                        $datos['almuerzo_aclaracion'],
                        $datos['cena_hora'],
                        $datos['cena_firma'],
                        $datos['cena_aclaracion'],
                        $id_usuario,
                        $datos['sector']
                    );
                    
                    if ($stmt->execute()) {
                        $_SESSION['mensaje_exito'] = "✅ Recepción guardada correctamente para la fecha " . date('d/m/Y', strtotime($datos['fecha']));
                        header("Location: index.php");
                        exit();
                    } else {
                        throw new Exception("Error al guardar el registro: " . $conexion->error);
                    }
                    
                    $stmt->close();
                } else {
                    throw new Exception("Error en la preparación de la consulta: " . $conexion->error);
                }
            }
            
            $stmt_check->close();
            
        } catch (Exception $e) {
            $errores[] = $e->getMessage();
        }
    }
}

// Preparar datos para JavaScript
$internos_array = [];
if ($result_internos) {
    $result_internos->data_seek(0);
    while ($interno = $result_internos->fetch_assoc()) {
        $internos_array[$interno['dni']] = $interno['nombre_apellido'];
    }
}

$sectores_array = [];
if ($result_sectores) {
    $result_sectores->data_seek(0);
    while ($sector = $result_sectores->fetch_assoc()) {
        $sectores_array[$sector['id']] = $sector['nombre'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Recepción de Alimentos</title>
    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5; 
            font-size: 11pt; 
            line-height: 1.1; 
        }
        
        .page-a4 { 
            width: 22cm; 
            min-height: 29.7cm; 
            margin: 0.5cm auto; 
            background: white; 
            box-shadow: 0 0 10px rgba(0,0,0,0.1); 
            padding: 1.5cm; 
            box-sizing: border-box; 
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
        }
        
        .header h1 { 
            font-size: 14pt; 
            font-weight: bold; 
            margin: 5px 0; 
            text-transform: uppercase; 
        }
        
        .section { 
            margin-bottom: 20px; 
        }
        
        /* ESTILOS PARA DATOS BÁSICOS - AL LADO DE LOS DOS PUNTOS */
        .datos-basicos-linea {
            margin-bottom: 8px;
            display: block;
        }
        
        .dato-label {
            font-weight: bold;
            display: inline;
        }
        
        .dato-input {
            border: none;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            padding: 0 5px;
            display: inline;
            margin-left: 5px;
            min-width: 150px;
        }
        
        .dato-input:focus {
            outline: none;
            background-color: #f0f8ff;
        }
        
        .table-container { 
            margin: 15px 0; 
            overflow-x: auto; 
        }
        
        .recepcion-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 10px 0; 
            table-layout: fixed; 
        }
        
        .recepcion-table th, .recepcion-table td { 
            border: 1px solid #000; 
            padding: 8px; 
            text-align: center; 
            vertical-align: top; 
        }
        
        .recepcion-table th { 
            background-color: #f0f0f0; 
            font-weight: bold; 
            font-size: 10pt; 
        }
        
        .comida-cell { 
            min-width: 200px; 
        }
        
        .actions { 
            margin-top: 25px; 
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
            margin-bottom: 8px; 
            color: #666; 
            font-size: 9pt; 
            font-family: Arial, sans-serif; 
            padding: 10px 20px; 
        }
        
        .separator { 
            border-bottom: 1px solid #000; 
            margin: 15px 0; 
        }
        
        .comida-fields { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
        }
        
        .recepcion-table td { 
            height: 180px; 
        }
        
        .required { 
            color: red; 
        }
        
        /* ESTILOS PARA LOS CAMPOS DE FECHA Y DATOS EN CADA COMIDA */
        .fecha-comida {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .fecha-label {
            font-weight: bold;
            margin-right: 5px;
            white-space: nowrap;
        }
        
        .fecha-display {
            font-weight: normal;
            margin-left: 5px;
        }
        
        /* ESTILOS PARA CAMPOS DE COMIDA - AL LADO DE LOS DOS PUNTOS */
        .campo-comida-linea {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .campo-comida-label {
            font-weight: bold;
            white-space: nowrap;
            margin-right: 5px;
        }
        
        .campo-comida-input {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 2px 5px;
            flex: 1;
            min-width: 0;
        }
        
        .campo-comida-input:focus {
            outline: none;
            background-color: #f0f8ff;
        }
        
        /* NUEVO ESTILO PARA ACLARACIÓN EN LÍNEA */
        .aclaracion-linea {
            display: flex;
            align-items: flex-start;
            margin-top: 5px;
        }
        
        .aclaracion-label {
            font-weight: bold;
            white-space: nowrap;
            margin-right: 5px;
        }
        
        .aclaracion-input {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 2px 5px;
            flex: 1;
            min-width: 0;
            min-height: 20px;
        }
        
        .aclaracion-input:focus {
            outline: none;
            background-color: #f0f8ff;
        }
        
        select.dato-input {
            padding: 2px;
            min-width: 200px;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
        }
        
        input[type="date"].dato-input {
            padding: 2px;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
        }
        
        .readonly-field {
            background-color: #f9f9f9;
        }
        
        input[type="time"].campo-comida-input {
            min-width: 80px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="page-a4">
        <div class="header">
            <h1>PLANILLA DE RECEPCIÓN DE ALIMENTOS DE INTERNOS</h1>
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
            <!-- Sección de datos básicos - AL LADO DE LOS DOS PUNTOS -->
            <div class="section">
                <div class="datos-basicos-linea">
                    <span class="dato-label">NOMBRE:</span>
                    <select id="dni_ppl" name="dni_ppl" class="dato-input" required onchange="actualizarDatosInterno()">
                        <option value="">Seleccione un interno</option>
                        <?php 
                        if ($result_internos) {
                            $result_internos->data_seek(0);
                            while ($interno = $result_internos->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($interno['dni']) . '"';
                                echo ($datos['dni_ppl'] == $interno['dni']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($interno['nombre_apellido']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">DNI:</span>
                    <input type="text" id="dni_display" class="dato-input readonly-field" readonly>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">SECTOR:</span>
                    <select id="sector" name="sector" class="dato-input" required onchange="actualizarDatosInterno()">
                        <option value="">Seleccione sector</option>
                        <?php 
                        if ($result_sectores) {
                            $result_sectores->data_seek(0);
                            while ($sector = $result_sectores->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($sector['id']) . '"';
                                echo ($datos['sector'] == $sector['id']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($sector['nombre']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">FECHA:</span>
                    <input type="date" 
                           name="fecha" 
                           value="<?php echo htmlspecialchars($datos['fecha']); ?>" 
                           class="dato-input"
                           required
                           onchange="actualizarFechasComidas()">
                </div>
            </div>

            <div class="separator"></div>

            <!-- Sección de recepción de alimentos -->
            <div class="section">
                <div class="table-container">
                    <table class="recepcion-table">
                        <thead>
                            <tr>
                                <th>DESAYUNO</th>
                                <th>ALMUERZO</th>
                                <th>CENA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <!-- Desayuno -->
                                <td class="comida-cell">
                                    <div class="comida-fields">
                                        <div class="fecha-comida">
                                            <span class="fecha-label">FECHA:</span>
                                            <span id="fecha_desayuno" class="fecha-display"><?php echo htmlspecialchars($datos['fecha']); ?></span>
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">HORA:</span>
                                            <input type="time" 
                                                   name="desayuno_hora" 
                                                   value="<?php echo htmlspecialchars($datos['desayuno_hora']); ?>" 
                                                   class="campo-comida-input">
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">FIRMA:</span>
                                            <input type="text" 
                                                   name="desayuno_firma" 
                                                   value="<?php echo htmlspecialchars($datos['desayuno_firma']); ?>" 
                                                   class="campo-comida-input"
                                                   placeholder="Firma del interno">
                                        </div>
                                        
                                        <!-- ACLARACIÓN EN LÍNEA -->
                                        <div class="aclaracion-linea">
                                            <span class="aclaracion-label">ACLARACIÓN:</span>
                                            <input type="text" 
                                                   name="desayuno_aclaracion" 
                                                   value="<?php echo htmlspecialchars($datos['desayuno_aclaracion']); ?>" 
                                                   class="aclaracion-input"
                                                   placeholder="Observaciones o aclaraciones">
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Almuerzo -->
                                <td class="comida-cell">
                                    <div class="comida-fields">
                                        <div class="fecha-comida">
                                            <span class="fecha-label">FECHA:</span>
                                            <span id="fecha_almuerzo" class="fecha-display"><?php echo htmlspecialchars($datos['fecha']); ?></span>
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">HORA:</span>
                                            <input type="time" 
                                                   name="almuerzo_hora" 
                                                   value="<?php echo htmlspecialchars($datos['almuerzo_hora']); ?>" 
                                                   class="campo-comida-input">
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">FIRMA:</span>
                                            <input type="text" 
                                                   name="almuerzo_firma" 
                                                   value="<?php echo htmlspecialchars($datos['almuerzo_firma']); ?>" 
                                                   class="campo-comida-input"
                                                   placeholder="Firma del interno">
                                        </div>
                                        
                                        <!-- ACLARACIÓN EN LÍNEA -->
                                        <div class="aclaracion-linea">
                                            <span class="aclaracion-label">ACLARACIÓN:</span>
                                            <input type="text" 
                                                   name="almuerzo_aclaracion" 
                                                   value="<?php echo htmlspecialchars($datos['almuerzo_aclaracion']); ?>" 
                                                   class="aclaracion-input"
                                                   placeholder="Observaciones o aclaraciones">
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Cena -->
                                <td class="comida-cell">
                                    <div class="comida-fields">
                                        <div class="fecha-comida">
                                            <span class="fecha-label">FECHA:</span>
                                            <span id="fecha_cena" class="fecha-display"><?php echo htmlspecialchars($datos['fecha']); ?></span>
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">HORA:</span>
                                            <input type="time" 
                                                   name="cena_hora" 
                                                   value="<?php echo htmlspecialchars($datos['cena_hora']); ?>" 
                                                   class="campo-comida-input">
                                        </div>
                                        
                                        <div class="campo-comida-linea">
                                            <span class="campo-comida-label">FIRMA:</span>
                                            <input type="text" 
                                                   name="cena_firma" 
                                                   value="<?php echo htmlspecialchars($datos['cena_firma']); ?>" 
                                                   class="campo-comida-input"
                                                   placeholder="Firma del interno">
                                        </div>
                                        
                                        <!-- ACLARACIÓN EN LÍNEA -->
                                        <div class="aclaracion-linea">
                                            <span class="aclaracion-label">ACLARACIÓN:</span>
                                            <input type="text" 
                                                   name="cena_aclaracion" 
                                                   value="<?php echo htmlspecialchars($datos['cena_aclaracion']); ?>" 
                                                   class="aclaracion-input"
                                                   placeholder="Observaciones o aclaraciones">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn">Guardar Recepción del Día</button>
            </div>
        </form>
    </div>

    <script>
        const internosData = <?php echo json_encode($internos_array); ?>;
        const sectoresData = <?php echo json_encode($sectores_array); ?>;

        function actualizarDatosInterno() {
            const selectPPL = document.getElementById('dni_ppl');
            const dniDisplay = document.getElementById('dni_display');
            
            if (selectPPL.value !== '') {
                dniDisplay.value = selectPPL.value;
            } else {
                dniDisplay.value = '';
            }
        }

        function actualizarFechasComidas() {
            const fechaInput = document.querySelector('input[name="fecha"]');
            const fechaDesayuno = document.getElementById('fecha_desayuno');
            const fechaAlmuerzo = document.getElementById('fecha_almuerzo');
            const fechaCena = document.getElementById('fecha_cena');
            
            if (fechaInput.value) {
                fechaDesayuno.textContent = fechaInput.value;
                fechaAlmuerzo.textContent = fechaInput.value;
                fechaCena.textContent = fechaInput.value;
            }
        }

        function establecerFechaAutomatica() {
            const fechaInput = document.querySelector('input[name="fecha"]');
            if (fechaInput && !fechaInput.value) {
                const fecha = new Date();
                const fechaStr = fecha.toISOString().split('T')[0];
                fechaInput.value = fechaStr;
                actualizarFechasComidas();
            }
        }

        // Inicializar
        window.addEventListener('load', function() {
            actualizarDatosInterno();
            establecerFechaAutomatica();
        });

        document.getElementById('dni_ppl').addEventListener('change', actualizarDatosInterno);
        document.getElementById('sector').addEventListener('change', actualizarDatosInterno);
    </script>
</body>
</html>