<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificación básica de permisos
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener lista de internos para el select
$query_internos = "SELECT dni, nombre_apellido, fecha_nacimiento, edad, fecha_ingreso, id_sector, id_pabellon FROM ppl WHERE estado = 'ACTIVO' ORDER BY nombre_apellido";
$result_internos = $conexion->query($query_internos);

// Obtener tipos de dieta para el select
$query_dietas = "SELECT id_dieta, nombre_dieta FROM tipos_dieta WHERE estado = 'ACTIVA' ORDER BY nombre_dieta";
$result_dietas = $conexion->query($query_dietas);

// Inicializar variables para el formulario
$errores = [];
$datos = [
    'dni_ppl' => '',
    'fecha_ingreso' => date('Y-m-d'),
    'peso_kg' => '',
    'talla_m' => '',
    'diagnostico' => '',
    'id_dieta' => '',
    'antecedentes_pat' => '',
    'certificacion_med' => 'NO',
    'firma_ppl' => '',
    'firma_efectivo' => ''
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar los datos del formulario
    $datos['dni_ppl'] = trim($_POST['dni_ppl'] ?? '');
    $datos['fecha_ingreso'] = trim($_POST['fecha_ingreso'] ?? '');
    $datos['peso_kg'] = trim($_POST['peso_kg'] ?? '');
    $datos['talla_m'] = trim($_POST['talla_m'] ?? '');
    $datos['diagnostico'] = trim($_POST['diagnostico'] ?? '');
    $datos['id_dieta'] = trim($_POST['id_dieta'] ?? '');
    $datos['antecedentes_pat'] = trim($_POST['antecedentes_pat'] ?? '');
    $datos['certificacion_med'] = trim($_POST['certificacion_med'] ?? 'NO');
    $datos['firma_ppl'] = trim($_POST['firma_ppl'] ?? '');
    $datos['firma_efectivo'] = trim($_POST['firma_efectivo'] ?? '');

    // DEBUG: Mostrar datos recibidos
    error_log("=== DATOS RECIBIDOS EN POST ===");
    error_log("antecedentes_pat: " . $datos['antecedentes_pat']);
    error_log("Longitud: " . strlen($datos['antecedentes_pat']));

    // Validaciones
    if (empty($datos['dni_ppl'])) {
        $errores[] = "El campo interno es obligatorio.";
    }
    
    if (empty($datos['fecha_ingreso'])) {
        $errores[] = "El campo fecha de ingreso es obligatorio.";
    }
    
    if (empty($datos['peso_kg']) || !is_numeric($datos['peso_kg']) || $datos['peso_kg'] <= 0) {
        $errores[] = "El peso debe ser un número mayor a cero.";
    }
    
    if (empty($datos['talla_m']) || !is_numeric($datos['talla_m']) || $datos['talla_m'] <= 0) {
        $errores[] = "La talla debe ser un número mayor a cero.";
    }

    // Verificar si ya existe un ingreso para este PPL
    if (empty($errores)) {
        $check_sql = "SELECT id FROM ingreso_nutricional WHERE dni_ppl = ?";
        $check_stmt = $conexion->prepare($check_sql);
        $check_stmt->bind_param("s", $datos['dni_ppl']);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $errores[] = "Ya existe un ingreso nutricional registrado para este interno.";
        }
    }

    // Si no hay errores, proceder a insertar
    if (empty($errores)) {
        // Obtener ID del usuario actual
        $id_usuario = $_SESSION['id_usuario'];
        
        // Convertir certificación médica a booleano
        $certificacion_med_bool = ($datos['certificacion_med'] === 'SI') ? 1 : 0;
        
        // Insertar en la base de datos - CON CORRECCIÓN EN bind_param
        $sql = "INSERT INTO ingreso_nutricional 
                (dni_ppl, fecha_ingreso, peso_kg, talla_m, diagnostico, id_dieta, 
                 antecedentes_pat, certificacion_med, firma_ppl, firma_efectivo, id_usuario) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt) {
            // CORRECCIÓN DEFINITIVA: Cadena de tipos corregida
            // "ssddsssisss" significa:
            // s: dni_ppl (string)
            // s: fecha_ingreso (string)
            // d: peso_kg (double)
            // d: talla_m (double)
            // s: diagnostico (string)
            // s: id_dieta (string)
            // s: antecedentes_pat (string) - ¡ESTE ES EL PROBLEMA!
            // i: certificacion_med (integer)
            // s: firma_ppl (string)
            // s: firma_efectivo (string)
            // s: id_usuario (string) - ¡PERO DEBERÍA SER i (integer)!
            
            // SOLUCIÓN: Cambiar a "ssddsssisssi" - el último parámetro es integer
            $stmt->bind_param(
                "ssddsssisss", // 11 parámetros - CORREGIDO
                $datos['dni_ppl'],
                $datos['fecha_ingreso'],
                $datos['peso_kg'],
                $datos['talla_m'],
                $datos['diagnostico'],
                $datos['id_dieta'],
                $datos['antecedentes_pat'], // Campo 7 - debe ser string
                $certificacion_med_bool,    // Campo 8 - debe ser integer
                $datos['firma_ppl'],        // Campo 9 - string
                $datos['firma_efectivo'],   // Campo 10 - string
                $id_usuario                 // Campo 11 - integer
            );
            
            if ($stmt->execute()) {
                // DEBUG: Verificar qué se insertó
                $last_id = $conexion->insert_id;
                error_log("✅ Registro insertado correctamente. ID: " . $last_id);
                
                $_SESSION['mensaje_exito'] = "✅ Planilla de ingreso nutricional guardada correctamente";
                header("Location: index.php");
                exit();
            } else {
                $error_msg = "Error al guardar el registro: " . $conexion->error;
                $errores[] = $error_msg;
                error_log("❌ " . $error_msg);
            }
        } else {
            $error_msg = "Error en la preparación de la consulta: " . $conexion->error;
            $errores[] = $error_msg;
            error_log("❌ " . $error_msg);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planilla de Ingreso Nutricional</title>
    <style>
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
        
        .header h1 {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-weight: bold;
            margin-bottom: 15px;
            text-decoration: underline;
        }
        
        /* ESTILO SIMPLE PARA DATOS BÁSICOS - SIN LÍNEAS */
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
            font-size: 12pt;
            padding: 0 5px;
            display: inline;
            margin-left: 5px;
        }
        
        .dato-input:focus {
            outline: none;
            background-color: #f0f8ff;
        }
        
        .table-container {
            margin: 20px 0;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .data-table th, .data-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }
        
        .data-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .firma-section {
            margin-top: 40px;
        }
        
        .firma-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
        }
        
        .firma-group {
            text-align: center;
            flex: 1;
            margin: 0 15px;
        }
        
        .firma-label {
            font-size: 10pt;
            margin-top: 5px;
            margin-bottom: 10px;
        }
        
        .firma-input {
            width: 100%;
            text-align: center;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            margin-top: 5px;
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
        
        /* QUITAMOS LOS SEPARADORES */
        /* .separator {
            border-bottom: 1px solid #000;
            margin: 20px 0;
        } */
        
        .readonly-field {
            background-color: #f9f9f9;
        }
        
        /* ESTILOS PARA CERTIFICACIÓN MÉDICA EN LÍNEA */
        .certificacion-medica {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        
        .certificacion-label {
            font-weight: bold;
            margin-right: 10px;
            white-space: nowrap;
        }
        
        .inline-radio {
            display: flex;
            gap: 15px;
        }
        
        .inline-radio label {
            display: flex;
            align-items: center;
        }
        
        .inline-radio input[type="radio"] {
            width: auto;
            margin-right: 5px;
        }
        
        /* ESTILOS MEJORADOS PARA TEXTAREAS */
        .textarea-diagnostico {
            width: 100%;
            border: 1px solid #000;
            padding: 8px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            min-height: 60px;
            resize: vertical;
            box-sizing: border-box;
        }
        
        .textarea-antecedentes {
            width: 100%;
            border: 1px solid #000;
            padding: 8px;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            min-height: 120px;
            resize: vertical;
            box-sizing: border-box;
        }
        
        .text-area-container {
            margin: 10px 0;
        }
        
        .field-full {
            width: 100%;
        }
        
        select.dato-input {
            padding: 2px;
            min-width: 200px;
        }
        
        input[type="date"].dato-input {
            padding: 2px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="page-a4">
        <!-- Encabezado de la planilla -->
        <div class="header">
            <h1>PLANILLA DE INGRESO NUTRICIONAL</h1>
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
            <!-- Sección de datos básicos - SIN LÍNEAS, RESPUESTAS AL LADO DE LOS DOS PUNTOS -->
            <div class="section">
                <div class="datos-basicos-linea">
                    <span class="dato-label">FECHA DE INGRESO:</span>
                    <input type="date" id="fecha_ingreso" name="fecha_ingreso" 
                           value="<?php echo htmlspecialchars($datos['fecha_ingreso']); ?>" 
                           class="dato-input" required>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">NOMBRE Y APELLIDO:</span>
                    <select id="dni_ppl" name="dni_ppl" class="dato-input" required onchange="actualizarDatosPPL()">
                        <option value="">Seleccione un interno</option>
                        <?php 
                        if ($result_internos) {
                            $result_internos->data_seek(0);
                            while ($interno = $result_internos->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($interno['dni']) . '" ';
                                echo 'data-fecha-nac="' . htmlspecialchars($interno['fecha_nacimiento']) . '" ';
                                echo 'data-edad="' . htmlspecialchars($interno['edad']) . '" ';
                                echo ($datos['dni_ppl'] == $interno['dni']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($interno['nombre_apellido']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">FECHA DE NACIMIENTO:</span>
                    <input type="text" id="fecha_nacimiento_display" class="dato-input readonly-field" readonly>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">EDAD:</span>
                    <input type="text" id="edad_display" class="dato-input readonly-field" readonly>
                </div>
                
                <div class="datos-basicos-linea">
                    <span class="dato-label">DNI:</span>
                    <input type="text" id="dni_display" class="dato-input readonly-field" readonly>
                </div>
            </div>

            <!-- QUITAMOS EL SEPARADOR -->

            <!-- Sección de datos antropométricos -->
            <div class="section">
                <div class="section-title">DATOS ANTROPOMÉTRICOS</div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>PESO ACTUAL (kg)</th>
                                <th>TALLA (m)</th>
                                <th>IMC</th>
                                <th>DIAGNÓSTICO</th>
                                <th>TIPIFICACIÓN DE DIETA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="number" id="peso_kg" name="peso_kg" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($datos['peso_kg']); ?>" 
                                           class="field-full" required>
                                </td>
                                <td>
                                    <input type="number" id="talla_m" name="talla_m" step="0.01" min="0" 
                                           value="<?php echo htmlspecialchars($datos['talla_m']); ?>" 
                                           class="field-full" required>
                                </td>
                                <td>
                                    <span id="imc_display">-</span>
                                </td>
                                <td>
                                    <div class="text-area-container">
                                        <textarea id="diagnostico" name="diagnostico" 
                                                  class="textarea-diagnostico"
                                                  placeholder="Describa el diagnóstico..."><?php echo htmlspecialchars($datos['diagnostico']); ?></textarea>
                                    </div>
                                </td>
                                <td>
                                    <select id="id_dieta" name="id_dieta" class="field-full">
                                        <option value="">Seleccione dieta</option>
                                        <?php 
                                        if ($result_dietas) {
                                            $result_dietas->data_seek(0);
                                            while ($dieta = $result_dietas->fetch_assoc()) {
                                                echo '<option value="' . htmlspecialchars($dieta['id_dieta']) . '"';
                                                echo ($datos['id_dieta'] == $dieta['id_dieta']) ? ' selected' : '';
                                                echo '>' . htmlspecialchars($dieta['nombre_dieta']) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- QUITAMOS EL SEPARADOR -->

            <!-- Sección de antecedentes -->
            <div class="section">
                <div class="section-title">ANTECEDENTES PATOLÓGICOS:</div>
                <div class="form-group">
                    <div class="text-area-container">
                        <textarea id="antecedentes_pat" name="antecedentes_pat" 
                                  class="textarea-antecedentes"
                                  placeholder="Describa los antecedentes patológicos..."><?php echo htmlspecialchars($datos['antecedentes_pat']); ?></textarea>
                    </div>
                </div>
                
                <!-- CERTIFICACIÓN MÉDICA EN LÍNEA -->
                <div class="certificacion-medica">
                    <span class="certificacion-label">Certificación médica:</span>
                    <div class="inline-radio">
                        <label>
                            <input type="radio" name="certificacion_med" value="SI" 
                                   <?php echo ($datos['certificacion_med'] === 'SI') ? 'checked' : ''; ?>> SI
                        </label>
                        <label>
                            <input type="radio" name="certificacion_med" value="NO" 
                                   <?php echo ($datos['certificacion_med'] === 'NO') ? 'checked' : ''; ?>> NO
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sección de firmas -->
            <div class="firma-section">
                <div class="firma-container">
                    <!-- Firma PPL -->
                    <div class="firma-group">
                        <div class="firma-label">FIRMA</div>
                        <input type="text" class="firma-input" id="firma_ppl" name="firma_ppl" 
                               value="<?php echo htmlspecialchars($datos['firma_ppl']); ?>" 
                               placeholder="Firma del interno">
                    </div>
                    
                    <!-- Aclaración -->
                    <div class="firma-group">
                        <div class="firma-label">ACLARACIÓN</div>
                        <input type="text" class="firma-input" id="aclaracion" name="aclaracion" 
                               value="<?php echo htmlspecialchars($datos['aclaracion'] ?? ''); ?>" 
                               placeholder="Aclaración">
                    </div>
                    
                    <!-- Firma Efectivo -->
                    <div class="firma-group">
                        <div class="firma-label">FIRMA EFECTIVO</div>
                        <input type="text" class="firma-input" id="firma_efectivo" name="firma_efectivo" 
                               value="<?php echo htmlspecialchars($datos['firma_efectivo']); ?>" 
                               placeholder="Firma del efectivo">
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn">Guardar Planilla</button>
            </div>
        </form>
    </div>

    <script>
        // Función para actualizar datos del PPL seleccionado
        function actualizarDatosPPL() {
            const selectPPL = document.getElementById('dni_ppl');
            const selectedOption = selectPPL.options[selectPPL.selectedIndex];
            
            if (selectedOption.value !== '') {
                const fechaNac = selectedOption.getAttribute('data-fecha-nac');
                const edad = selectedOption.getAttribute('data-edad');
                const dni = selectedOption.value;
                
                document.getElementById('fecha_nacimiento_display').value = fechaNac;
                document.getElementById('edad_display').value = edad + ' años';
                document.getElementById('dni_display').value = dni;
            } else {
                document.getElementById('fecha_nacimiento_display').value = '';
                document.getElementById('edad_display').value = '';
                document.getElementById('dni_display').value = '';
            }
        }

        // Función para calcular IMC
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso_kg').value);
            const talla = parseFloat(document.getElementById('talla_m').value);
            const imcDisplay = document.getElementById('imc_display');
            
            if (peso && talla && talla > 0) {
                const imc = peso / (talla * talla);
                imcDisplay.textContent = imc.toFixed(2);
            } else {
                imcDisplay.textContent = '-';
            }
        }

        // Inicializar datos al cargar la página
        window.addEventListener('load', function() {
            actualizarDatosPPL();
            calcularIMC();
        });

        // Event listeners
        document.getElementById('dni_ppl').addEventListener('change', actualizarDatosPPL);
        document.getElementById('peso_kg').addEventListener('input', calcularIMC);
        document.getElementById('talla_m').addEventListener('input', calcularIMC);
    </script>
</body>
</html>