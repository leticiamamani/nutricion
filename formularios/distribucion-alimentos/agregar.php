<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener tipos de comida desde la base de datos
$tipos_comida = [];
$query_tipos = "SELECT id, nombre FROM tipo_comida WHERE estado = 'ACTIVO'";
$result_tipos = $conexion->query($query_tipos);
if ($result_tipos) {
    while ($row = $result_tipos->fetch_assoc()) {
        $tipos_comida[$row['id']] = $row['nombre'];
    }
} else {
    die("Error al obtener tipos de comida: " . $conexion->error);
}

// Obtener sectores desde la base de datos
$sectores = [];
$query_sectores = "SELECT id, nombre FROM sectores_distribucion WHERE estado = 'ACTIVO'";
$result_sectores = $conexion->query($query_sectores);
if ($result_sectores) {
    while ($row = $result_sectores->fetch_assoc()) {
        $sectores[$row['id']] = $row['nombre'];
    }
} else {
    die("Error al obtener sectores: " . $conexion->error);
}

// Obtener descripciones para DESAYUNO
$descripciones_desayuno = [];
$query_desayuno = "SELECT id_sector_distribucion, descripcion FROM descripciones_desayuno WHERE estado = 'ACTIVO'";
$result_desayuno = $conexion->query($query_desayuno);
if ($result_desayuno) {
    while ($row = $result_desayuno->fetch_assoc()) {
        $descripciones_desayuno[$row['id_sector_distribucion']] = $row['descripcion'];
    }
}

// Obtener descripciones para ALMUERZO
$descripciones_almuerzo = [];
$query_almuerzo = "SELECT id_sector_distribucion, descripcion FROM descripciones_almuerzo WHERE estado = 'ACTIVO'";
$result_almuerzo = $conexion->query($query_almuerzo);
if ($result_almuerzo) {
    while ($row = $result_almuerzo->fetch_assoc()) {
        $descripciones_almuerzo[$row['id_sector_distribucion']] = $row['descripcion'];
    }
}

// Obtener descripciones para CENA
$descripciones_cena = [];
$query_cena = "SELECT id_sector_distribucion, descripcion FROM descripciones_cena WHERE estado = 'ACTIVO'";
$result_cena = $conexion->query($query_cena);
if ($result_cena) {
    while ($row = $result_cena->fetch_assoc()) {
        $descripciones_cena[$row['id_sector_distribucion']] = $row['descripcion'];
    }
}

// ✅ Función para verificar si hay datos
function tieneDatos($data) {
    foreach ($data as $value) {
        if ($value !== '' && $value !== null) {
            if (is_numeric($value)) return true;
            if (is_string($value) && trim($value) !== '') return true;
            if ($value === 'TÉ' || $value === 'MATE_COCIDO') return true;
        }
    }
    return false;
}

// Inicializar datos para cada tipo de comida y sector
$datos = [];
$datos_sectores = [];
$errores = [];

foreach ($tipos_comida as $tipo_id => $tipo_nombre) {
    $datos[$tipo_id] = [
        'id_tipo_comida' => $tipo_id,
        'fecha' => date('Y-m-d'),
        'hora' => date('H:i')
    ];
    
    $datos_sectores[$tipo_id] = [];
    foreach ($sectores as $sector_id => $sector_nombre) {
        $datos_sectores[$tipo_id][$sector_id] = [
            'nro_colaciones' => '',
            'pan_kg' => '',
            'te_mate_cocido' => '',
            'nro_dietas' => '',
            'nro_viandas_comunes' => '',
            'hora_llegada' => '',
            'hora_recibido' => '',
            'firma_sector' => '',
            'aclaracion_sector' => ''
        ];
    }
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_comida_id = intval($_POST['id_tipo_comida'] ?? 1);
    
    $datos[$tipo_comida_id]['fecha'] = trim($_POST['fecha'] ?? '');
    $datos[$tipo_comida_id]['hora'] = trim($_POST['hora'] ?? '');

    // Recoger datos por sector (por ID) para el tipo de comida específico
    foreach ($sectores as $sector_id => $sector_nombre) {
        $datos_sectores[$tipo_comida_id][$sector_id]['nro_colaciones'] = $_POST['nro_colaciones'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['pan_kg'] = $_POST['pan_kg'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['te_mate_cocido'] = $_POST['te_mate_cocido'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['nro_dietas'] = $_POST['nro_dietas'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['nro_viandas_comunes'] = $_POST['nro_viandas_comunes'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['hora_llegada'] = $_POST['hora_llegada'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['hora_recibido'] = $_POST['hora_recibido'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['firma_sector'] = $_POST['firma_sector'][$sector_id] ?? '';
        $datos_sectores[$tipo_comida_id][$sector_id]['aclaracion_sector'] = $_POST['aclaracion_sector'][$sector_id] ?? '';
    }

    // Validaciones básicas
    if (empty($datos[$tipo_comida_id]['fecha'])) {
        $errores[$tipo_comida_id][] = "El campo fecha es obligatorio.";
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores[$tipo_comida_id])) {
        $id_usuario = $_SESSION['id_usuario'];
        $success_count = 0;
        
        // Obtener el nombre del tipo de comida para mensajes
        $tipo_comida_nombre = $tipos_comida[$tipo_comida_id] ?? 'DESAYUNO';
        
        // Verificar si ya existen registros para esta fecha y tipo de comida
        $check_sql = "SELECT COUNT(*) as count FROM distribucion_alimentos WHERE fecha = ? AND id_tipo_comida = ?";
        $check_stmt = $conexion->prepare($check_sql);
        $check_stmt->bind_param("si", $datos[$tipo_comida_id]['fecha'], $tipo_comida_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $existing_count = $check_result->fetch_assoc()['count'];
        
        if ($existing_count > 0) {
            $errores[$tipo_comida_id][] = "Ya existe una distribución de " . $tipo_comida_nombre . " para la fecha " . $datos[$tipo_comida_id]['fecha'];
        } else {
            // Insertar datos para cada sector
            foreach ($sectores as $sector_id => $sector_nombre) {
                $data = $datos_sectores[$tipo_comida_id][$sector_id];
                
                // ✅ Solo insertar si hay al menos un dato
                if (tieneDatos($data)) {
                    // Determinar la descripción según el tipo de comida
                    $descripcion = '';
                    switch($tipo_comida_id) {
                        case 1: // DESAYUNO
                            $descripcion = $descripciones_desayuno[$sector_id] ?? '';
                            break;
                        case 2: // ALMUERZO
                            $descripcion = $descripciones_almuerzo[$sector_id] ?? '';
                            break;
                        case 3: // CENA
                            $descripcion = $descripciones_cena[$sector_id] ?? '';
                            break;
                    }
                    
                    // Convertir valores vacíos a NULL
                    $nro_colaciones = ($data['nro_colaciones'] === '') ? NULL : (int)$data['nro_colaciones'];
                    $nro_dietas = ($data['nro_dietas'] === '') ? NULL : (int)$data['nro_dietas'];
                    $nro_viandas_comunes = ($data['nro_viandas_comunes'] === '') ? NULL : (int)$data['nro_viandas_comunes'];
                    $pan_kg = ($data['pan_kg'] === '') ? NULL : (float)$data['pan_kg'];
                    $te_mate_cocido = ($data['te_mate_cocido'] === '') ? NULL : $data['te_mate_cocido'];
                    $hora_llegada = ($data['hora_llegada'] === '') ? NULL : $data['hora_llegada'];
                    $hora_recibido = ($data['hora_recibido'] === '') ? NULL : $data['hora_recibido'];
                    $firma_sector = ($data['firma_sector'] === '') ? NULL : $data['firma_sector'];
                    $aclaracion_sector = ($data['aclaracion_sector'] === '') ? NULL : $data['aclaracion_sector'];
                    
                    // Insertar en la base de datos
                    $sql = "INSERT INTO distribucion_alimentos 
                            (id_tipo_comida, fecha, hora, id_sector_distribucion, nro_colaciones, 
                             nro_dietas, nro_viandas_comunes, pan_kg, te_mate_cocido, 
                             hora_llegada, hora_recibido, 
                             firma, aclaracion, id_usuario) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $stmt = $conexion->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param(
                            "issiiiiisssssi",
                            $tipo_comida_id,
                            $datos[$tipo_comida_id]['fecha'],
                            $datos[$tipo_comida_id]['hora'],
                            $sector_id,
                            $nro_colaciones,
                            $nro_dietas,
                            $nro_viandas_comunes,
                            $pan_kg,
                            $te_mate_cocido,
                            $hora_llegada,
                            $hora_recibido,
                            $firma_sector,
                            $aclaracion_sector,
                            $id_usuario
                        );
                        
                        if ($stmt->execute()) {
                            $success_count++;
                        } else {
                            $errores[$tipo_comida_id][] = "Error al guardar sector $sector_nombre: " . $stmt->error;
                        }
                    } else {
                        $errores[$tipo_comida_id][] = "Error preparando consulta para sector $sector_nombre: " . $conexion->error;
                    }
                }
            }
            
            if ($success_count > 0) {
                $_SESSION['mensaje'] = "✅ Distribución de " . $tipo_comida_nombre . " guardada correctamente para $success_count sectores";
                header("Location: index.php");
                exit();
            } else {
                $errores[$tipo_comida_id][] = "No se guardó ningún registro. Verifique que haya ingresado datos en al menos un sector.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Distribución de Alimentos</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-size: 10pt;
            line-height: 1.2;
        }
        
        .page-a4 {
            width: 21cm;
            min-height: 29.7cm;
            margin: 0.5cm auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 0.5cm;
            box-sizing: border-box;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }
        
        .header h3 {
            font-size: 12pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .comida-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 3px;
        }
        
        .fecha-hora {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 10pt;
        }
        
        .comida-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 11pt;
            font-weight: bold;
        }
        
        .comida-selector select {
            font-size: 11pt;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            background-color: white;
        }
        
        .comida-name {
            font-weight: bold;
            font-size: 14pt;
            color: #000;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 9pt;
            table-layout: fixed;
        }
        
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
            height: 28px;
            word-wrap: break-word;
            overflow: hidden;
        }
        
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 6px 4px;
        }
        
        .sector-cell {
            text-align: left;
            font-weight: bold;
            width: 90px;
            padding: 4px 6px;
        }
        
        .descripcion-cell {
            text-align: left;
            padding: 4px 6px;
            font-size: 8.5pt;
        }
        
        .number-input {
            width: 100%;
            height: 24px;
            text-align: center;
            border: none;
            font-size: 9pt;
            padding: 0;
            background: transparent;
        }
        
        select {
            width: 100%;
            height: 24px;
            border: none;
            font-size: 9pt;
            padding: 0;
            background: transparent;
        }
        
        .time-input {
            width: 100%;
            height: 24px;
            text-align: center;
            border: none;
            font-size: 9pt;
            padding: 0;
            background: transparent;
        }
        
        .firma-input {
            width: 100%;
            height: 24px;
            text-align: center;
            border: none;
            font-size: 9pt;
            padding: 0;
            background: transparent;
        }
        
        .actions {
            margin-top: 15px;
            text-align: center;
        }
        
        .btn {
            padding: 10px 25px;
            background-color: #000000;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0 5px;
            font-weight: bold;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 3px;
            margin-bottom: 15px;
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            color: #666;
            font-size: 9pt;
            font-family: Arial, sans-serif;
        }
        
        .leyenda {
            font-size: 7pt;
            color: #666;
            margin-top: 5px;
            font-style: italic;
            text-align: justify;
            padding: 5px;
        }
        
        .inline-field {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 0 3px;
            margin: 0 3px;
            text-align: center;
        }
        
        .field-small {
            width: 100px;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            text-align: center;
            font-size: 9pt;
            font-family: Arial, sans-serif;
        }
        
        /* ESTILOS CORREGIDOS PARA COLUMNAS - AJUSTADOS PARA CABER EN A4 */
        .col-sector { width: 90px; }
        .col-nro { width: 45px; }
        .col-desc { width: 120px; }
        .col-pan { width: 45px; }
        .col-te { width: 55px; }
        .col-dietas { width: 55px; }
        .col-viandas { width: 65px; }
        .col-hora { width: 55px; }
        .col-firma { width: 65px; }
        .col-aclaracion { width: 75px; }
        
        .section-actions {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            border-top: 1px dashed #ccc;
        }
        
        .global-actions {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #000;
        }
        
        /* Estilos para campos dinámicos */
        .desayuno-fields {
            display: none;
        }
        
        .almuerzo-fields {
            display: none;
        }
        
        .cena-fields {
            display: none;
        }
        
        .active-fields {
            display: table-cell;
        }
        
        /* Estilos para encabezados con texto largo */
        .th-text {
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.2;
            padding: 3px 2px;
        }
        
        .th-small {
            font-size: 8.5pt;
        }
        
        /* Ajuste para tabla responsiva */
        .table-container {
            width: 100%;
            overflow-x: auto;
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
            <h3>Jefatura de Sanidad - Área Nutrición</h3>
            <h3>PLANILLA DE DISTRIBUCIÓN DE ALIMENTOS</h3>
        </div>

        <div class="info-box">
            <strong>💡 INFORMACIÓN:</strong> Seleccione el tipo de comida y complete los datos de distribución. 
            Puede guardar cada planilla por separado.
        </div>

        <div class="comida-section">
            <?php 
            // Determinar el tipo de comida activo (el primero por defecto o el que se procesó)
            $tipo_activo = 1;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $tipo_activo = intval($_POST['id_tipo_comida'] ?? 1);
            }
            
            if (!empty($errores[$tipo_activo])):
            ?>
                <div class="error">
                    <strong>Errores:</strong>
                    <ul>
                        <?php foreach ($errores[$tipo_activo] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="comida-form" id="formulario-unico">
                <input type="hidden" name="id_tipo_comida" id="id_tipo_comida" value="<?php echo $tipo_activo; ?>">
                
                <div class="section-header">
                    <div class="comida-selector">
                        <span>Tipo de Comida:</span>
                        <select id="selector-comida" name="selector_comida">
                            <?php foreach ($tipos_comida as $tipo_id => $tipo_nombre): 
                                $selected = ($tipo_id == $tipo_activo) ? 'selected' : '';
                                $icono = '';
                                switch($tipo_nombre) {
                                    case 'DESAYUNO': $icono = '🍳'; break;
                                    case 'ALMUERZO': $icono = '🍲'; break;
                                    case 'CENA': $icono = '🍽️'; break;
                                }
                            ?>
                                <option value="<?php echo $tipo_id; ?>" <?php echo $selected; ?>>
                                    <?php echo $icono . ' ' . $tipo_nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="fecha-hora">
                        📅 Fecha: 
                        <input type="date" name="fecha" value="<?php echo htmlspecialchars($datos[$tipo_activo]['fecha']); ?>" class="inline-field field-small" required>
                        🕒 Hora: 
                        <input type="time" name="hora" value="<?php echo htmlspecialchars($datos[$tipo_activo]['hora']); ?>" class="inline-field field-small">
                    </div>
                </div>
                
                <!-- TABLA ÚNICA CON CAMPOS DINÁMICOS -->
                <div class="table-container">
                    <table class="data-table" id="tabla-distribucion">
                        <thead>
                            <tr>
                                <th class="col-sector th-text">SECTOR</th>
                                <th class="col-nro th-text th-small">N° COLACIONES</th>
                                <th class="col-desc th-text">DESCRIPCIÓN</th>
                                
                                <!-- Campos para DESAYUNO -->
                                <th class="col-pan desayuno-fields th-text th-small">PAN (KG)</th>
                                <th class="col-te desayuno-fields th-text th-small">TÉ O MATE COCIDO</th>
                                
                                <!-- Campos para ALMUERZO -->
                                <th class="col-dietas almuerzo-fields th-text th-small">N° DIETAS</th>
                                <th class="col-viandas almuerzo-fields th-text th-small">N° VIANDAS COMUNES</th>
                                
                                <!-- Campos para CENA -->
                                <th class="col-dietas cena-fields th-text th-small">CANTIDAD DE DIETAS</th>
                                <th class="col-viandas cena-fields th-text th-small">MENÚ NORMAL (cantidad)</th>
                                
                                <th class="col-hora th-text th-small">HORA LLEGADA</th>
                                <th class="col-hora th-text th-small">HORA RECIBIDO</th>
                                <th class="col-firma th-text">FIRMA</th>
                                <th class="col-aclaracion th-text">ACLARACIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectores as $sector_id => $sector_nombre): ?>
                            <tr>
                                <td class="sector-cell"><?php echo htmlspecialchars($sector_nombre); ?></td>
                                <td>
                                    <input type="number" name="nro_colaciones[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['nro_colaciones']); ?>" 
                                           class="number-input" min="0" placeholder="0">
                                </td>
                                <td class="descripcion-cell" id="descripcion-<?php echo $sector_id; ?>">
                                    <?php 
                                    // Mostrar la descripción según el tipo de comida activo
                                    switch($tipo_activo) {
                                        case 1: // DESAYUNO
                                            echo htmlspecialchars($descripciones_desayuno[$sector_id] ?? '');
                                            break;
                                        case 2: // ALMUERZO
                                            echo htmlspecialchars($descripciones_almuerzo[$sector_id] ?? '');
                                            break;
                                        case 3: // CENA
                                            echo htmlspecialchars($descripciones_cena[$sector_id] ?? '');
                                            break;
                                    }
                                    ?>
                                </td>
                                
                                <!-- Campos para DESAYUNO -->
                                <td class="desayuno-fields">
                                    <input type="number" name="pan_kg[<?php echo $sector_id; ?>]" step="0.01" min="0"
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['pan_kg']); ?>" 
                                           class="number-input" placeholder="0.00">
                                </td>
                                <td class="desayuno-fields">
                                    <select name="te_mate_cocido[<?php echo $sector_id; ?>]">
                                        <option value="">-</option>
                                        <option value="TÉ" <?php echo ($datos_sectores[$tipo_activo][$sector_id]['te_mate_cocido'] === 'TÉ') ? 'selected' : ''; ?>>TÉ</option>
                                        <option value="MATE_COCIDO" <?php echo ($datos_sectores[$tipo_activo][$sector_id]['te_mate_cocido'] === 'MATE_COCIDO') ? 'selected' : ''; ?>>MATE COCIDO</option>
                                    </select>
                                </td>
                                
                                <!-- Campos para ALMUERZO -->
                                <td class="almuerzo-fields">
                                    <input type="number" name="nro_dietas[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['nro_dietas']); ?>" 
                                           class="number-input" min="0" placeholder="0">
                                </td>
                                <td class="almuerzo-fields">
                                    <input type="number" name="nro_viandas_comunes[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['nro_viandas_comunes']); ?>" 
                                           class="number-input" min="0" placeholder="0">
                                </td>
                                
                                <!-- Campos para CENA -->
                                <td class="cena-fields">
                                    <input type="number" name="nro_dietas[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['nro_dietas']); ?>" 
                                           class="number-input" min="0" placeholder="0">
                                </td>
                                <td class="cena-fields">
                                    <input type="number" name="nro_viandas_comunes[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['nro_viandas_comunes']); ?>" 
                                           class="number-input" min="0" placeholder="0">
                                </td>
                                
                                <td>
                                    <input type="time" name="hora_llegada[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['hora_llegada']); ?>" 
                                           class="time-input">
                                </td>
                                <td>
                                    <input type="time" name="hora_recibido[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['hora_recibido']); ?>" 
                                           class="time-input">
                                </td>
                                <td>
                                    <input type="text" name="firma_sector[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['firma_sector']); ?>" 
                                           class="firma-input" placeholder="Firma">
                                </td>
                                <td>
                                    <input type="text" name="aclaracion_sector[<?php echo $sector_id; ?>]" 
                                           value="<?php echo htmlspecialchars($datos_sectores[$tipo_activo][$sector_id]['aclaracion_sector']); ?>" 
                                           class="firma-input" placeholder="Aclaración">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="leyenda" id="leyenda-comida">
                    <?php 
                    // Mostrar leyenda según el tipo de comida activo
                    switch($tipo_activo) {
                        case 1: // DESAYUNO
                            echo '*Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria
                            **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego del SPP).
                            ***El pan entregado en el desayuno corresponde al almuerzo para los extraordinarios. Cada sector recibe su pan con las viandas.';
                            break;
                        case 2: // ALMUERZO
                            echo 'Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria
                            **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego).';
                            break;
                        case 3: // CENA
                            echo '*Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentar.
                            **Los reclamos de los alimentos acorde a su estado y/o características organolépticas deben realizarse dentro de los 30min luego de ser entregados al sector correspondiente para su devolución y/o reposición por parte de la empresa A.T.A (según pliego del SPP), fuera de ese horario queda sin efecto.';
                            break;
                    }
                    ?>
                </div>

                <div class="section-actions">
                    <button type="submit" class="btn" id="btn-guardar" onclick="return confirm('¿Está seguro de guardar la distribución?')">
                        💾 Guardar Distribución
                    </button>
                </div>
            </form>
        </div>

        <div class="global-actions">
            <a href="index.php" class="btn btn-secondary">← Volver al Listado</a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectorComida = document.getElementById('selector-comida');
            const idTipoComida = document.getElementById('id_tipo_comida');
            const btnGuardar = document.getElementById('btn-guardar');
            const leyendaComida = document.getElementById('leyenda-comida');
            const tabla = document.getElementById('tabla-distribucion');
            
            // Obtener referencias a todos los campos dinámicos
            const camposDesayuno = document.querySelectorAll('.desayuno-fields');
            const camposAlmuerzo = document.querySelectorAll('.almuerzo-fields');
            const camposCena = document.querySelectorAll('.cena-fields');
            
            // Datos de descripciones por tipo de comida y sector
            const descripciones = {
                1: <?php echo json_encode($descripciones_desayuno); ?>,
                2: <?php echo json_encode($descripciones_almuerzo); ?>,
                3: <?php echo json_encode($descripciones_cena); ?>
            };
            
            // Leyendas por tipo de comida
            const leyendas = {
                1: `*Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria
                **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego del SPP).
                ***El pan entregado en el desayuno corresponde al almuerzo para los extraordinarios. Cada sector recibe su pan con las viandas.`,
                2: `Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentaria
                **Sujeta a variaciones conforme a la selección de alimentos del día y/o a las indicaciones específicas de las Nutricionistas (según artículos del pliego).`,
                3: `*Quien firma y recibe ha aceptado que constató el correcto estado de los servicios recibidos, tanto como las correctas condiciones del alimento, higiene y seguridad alimentar.
                **Los reclamos de los alimentos acorde a su estado y/o características organolépticas deben realizarse dentro de los 30min luego de ser entregados al sector correspondiente para su devolución y/o reposición por parte de la empresa A.T.A (según pliego del SPP), fuera de ese horario queda sin efecto.`
            };
            
            // Función para cambiar el tipo de comida
            function cambiarTipoComida(tipoId) {
                // Actualizar el valor oculto
                idTipoComida.value = tipoId;
                
                // Obtener el nombre del tipo de comida seleccionado
                const nombreComida = selectorComida.options[selectorComida.selectedIndex].text;
                
                // Actualizar el texto del botón
                btnGuardar.innerHTML = '💾 Guardar ' + nombreComida.split(' ')[1];
                
                // Ocultar todos los campos primero
                camposDesayuno.forEach(campo => campo.classList.remove('active-fields'));
                camposAlmuerzo.forEach(campo => campo.classList.remove('active-fields'));
                camposCena.forEach(campo => campo.classList.remove('active-fields'));
                
                // Mostrar campos según el tipo de comida
                if (tipoId == 1) {
                    // DESAYUNO: mostrar campos de pan y té/mate
                    camposDesayuno.forEach(campo => campo.classList.add('active-fields'));
                } else if (tipoId == 2) {
                    // ALMUERZO: mostrar campos de dietas y viandas
                    camposAlmuerzo.forEach(campo => campo.classList.add('active-fields'));
                } else if (tipoId == 3) {
                    // CENA: mostrar campos de dietas y viandas (con nombres diferentes)
                    camposCena.forEach(campo => campo.classList.add('active-fields'));
                }
                
                // Actualizar las descripciones para cada sector
                Object.keys(descripciones[tipoId]).forEach(sectorId => {
                    const celdaDescripcion = document.getElementById('descripcion-' + sectorId);
                    if (celdaDescripcion) {
                        celdaDescripcion.textContent = descripciones[tipoId][sectorId] || '';
                    }
                });
                
                // Actualizar la leyenda
                leyendaComida.textContent = leyendas[tipoId];
            }
            
            // Inicializar con el tipo de comida activo
            cambiarTipoComida(<?php echo $tipo_activo; ?>);
            
            // Event listener para el selector
            selectorComida.addEventListener('change', function() {
                cambiarTipoComida(this.value);
            });
        });
    </script>
</body>
</html>