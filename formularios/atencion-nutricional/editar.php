<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificar permisos para atención nutricional
verificarAtencionNutricional();

// Obtener el ID del registro a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de registro no válido";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Obtener los datos del registro
$sql = "SELECT * FROM atencion_nutricional WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Registro no encontrado";
    header("Location: index.php");
    exit();
}

$registro = $result->fetch_assoc();

// Obtener lista de internos para el select
$query_internos = "SELECT dni, nombre_apellido FROM ppl WHERE estado = 'ACTIVO' ORDER BY nombre_apellido";
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
    'dni_ppl' => $registro['dni_ppl'],
    'fecha' => $registro['fecha'],
    'peso_kg' => $registro['peso_kg'],
    'talla_m' => $registro['talla_m'],
    'observaciones' => $registro['observaciones'],
    'id_sector' => $registro['id_sector'],
    'id_pabellon' => $registro['id_pabellon'],
    'firma_ppl' => $registro['firma_ppl'],
    'aclaracion' => $registro['aclaracion'],
    'dni_firma' => $registro['dni_firma'],
    'firma_oficial' => $registro['firma_oficial']
];

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar los datos del formulario
    $datos['dni_ppl'] = trim($_POST['dni_ppl'] ?? '');
    $datos['fecha'] = trim($_POST['fecha'] ?? '');
    $datos['peso_kg'] = trim($_POST['peso_kg'] ?? '');
    $datos['talla_m'] = trim($_POST['talla_m'] ?? '');
    $datos['observaciones'] = trim($_POST['observaciones'] ?? '');
    $datos['id_sector'] = trim($_POST['id_sector'] ?? '');
    $datos['id_pabellon'] = trim($_POST['id_pabellon'] ?? '');
    $datos['firma_ppl'] = trim($_POST['firma_ppl'] ?? '');
    $datos['aclaracion'] = trim($_POST['aclaracion'] ?? '');
    $datos['dni_firma'] = trim($_POST['dni_firma'] ?? '');
    $datos['firma_oficial'] = trim($_POST['firma_oficial'] ?? '');

    // Validaciones
    if (empty($datos['dni_ppl'])) {
        $errores[] = "El campo interno es obligatorio.";
    }
    
    if (empty($datos['fecha'])) {
        $errores[] = "El campo fecha es obligatorio.";
    }
    
    if (empty($datos['peso_kg']) || !is_numeric($datos['peso_kg']) || $datos['peso_kg'] <= 0) {
        $errores[] = "El peso debe ser un número mayor a cero.";
    }
    
    if (empty($datos['talla_m']) || !is_numeric($datos['talla_m']) || $datos['talla_m'] <= 0) {
        $errores[] = "La talla debe ser un número mayor a cero.";
    }
    
    if (empty($datos['id_sector'])) {
        $errores[] = "El campo sector es obligatorio.";
    }
    
    if (empty($datos['id_pabellon'])) {
        $errores[] = "El campo pabellón es obligatorio.";
    }

    // Si no hay errores, proceder a actualizar
    if (empty($errores)) {
        // Calcular IMC
        $imc = $datos['peso_kg'] / ($datos['talla_m'] * $datos['talla_m']);
        
        // Obtener ID del usuario actual
        $id_usuario = $_SESSION['id_usuario'];
        
        // Actualizar en la base de datos
        $sql = "UPDATE atencion_nutricional 
                SET dni_ppl = ?, fecha = ?, id_sector = ?, id_pabellon = ?, peso_kg = ?, talla_m = ?, observaciones = ?, 
                    firma_ppl = ?, aclaracion = ?, firma_oficial = ?, dni_firma = ?, id_usuario = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param(
                "ssiiddsssssii",
                $datos['dni_ppl'],
                $datos['fecha'],
                $datos['id_sector'],
                $datos['id_pabellon'],
                $datos['peso_kg'],
                $datos['talla_m'],
                $datos['observaciones'],
                $datos['firma_ppl'],
                $datos['aclaracion'],
                $datos['firma_oficial'],
                $datos['dni_firma'],
                $id_usuario,
                $id
            );
            
            if ($stmt->execute()) {
                $_SESSION['mensaje_exito'] = "✅ Acta de atención nutricional actualizada correctamente";
                header("Location: index.php");
                exit();
            } else {
                $errores[] = "Error al actualizar el registro: " . $conexion->error;
            }
        } else {
            $errores[] = "Error en la preparación de la consulta: " . $conexion->error;
        }
    }
}

// Obtener fecha del registro para mostrar en formato del acta
$fecha = $datos['fecha'];
list($anio, $mes, $dia) = explode('-', $fecha);

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
    <title>Editar Atención Nutricional</title>
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
            margin-bottom: 20px;
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
        
        .inline-field {
            display: inline-block;
            border-bottom: 1px solid #000;
            padding: 1px 3px;
            margin: 0 3px;
            min-width: 30px;
            text-align: center;
            font-size: 10pt;
        }
        
        .field-medium {
            min-width: 70px;
        }
        
        .field-interno {
            min-width: 180px;
        }
        
        .firma-section {
            margin-top: 60px;
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
            font-weight: bold;
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
        
        .imc-inline {
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px solid #000;
            padding: 1px 3px;
            text-align: center;
            margin: 0 3px;
            font-size: 10pt;
        }
        
        .observaciones {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 10px;
            min-height: 80px;
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
        
        select.inline-field {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 1px 3px;
            margin: 0 3px;
            text-align: center;
        }
        
        input.inline-field {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 1px 3px;
            margin: 0 3px;
            text-align: center;
        }
        
        .hidden-field {
            display: none;
        }
        
        /* Estilos específicos para campos numéricos compactos */
        input[type="number"].inline-field {
            width: 60px;
        }
        
        /* Asegurar que los select no sean demasiado anchos */
        select.inline-field.field-medium {
            max-width: 90px;
        }
        
        select.inline-field.field-interno {
            max-width: 200px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        Usuario: <strong><?php echo $_SESSION['nombre_usuario'] ?? 'Usuario'; ?></strong> | 
        Rol: <strong><?php echo $_SESSION['permisos'] ?? 'Sin rol'; ?></strong>
    </div>

    <div class="page-a4">
        <!-- Encabezado del acta con logo - SIN LÍNEA -->
        <div class="header">
            <img src="../../assets/img/logo.png" alt="Logo Gobierno" onerror="this.style.display='none'; document.getElementById('logo-placeholder').style.display='flex';">
            <div id="logo-placeholder" class="logo-placeholder" style="display:none;">LOGO INSTITUCIONAL</div>
            <h3>Jefatura de Sanidad - Área Nutrición</h3>
            <h3>ACTA DE ATENCIÓN NUTRICIONAL</h3>
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
            <!-- Campo de fecha oculto para el procesamiento -->
            <input type="date" id="fecha" name="fecha" value="<?php echo htmlspecialchars($datos['fecha']); ?>" class="hidden-field" required>
            
            <!-- Contenido del acta - ESTRUCTURA IDÉNTICA AL AGREGAR.PHP -->
            <div class="acta-content">
                <div class="acta-paragraph">
                    En San Juan, Servicio Penitenciario Provincial, a los 
                    <span class="inline-field"><?php echo $dia; ?></span> días del mes de 
                    <span class="inline-field field-medium"><?php echo $meses[$mes]; ?></span> 
                    del año 
                    <span class="inline-field"><?php echo $anio; ?></span>
                    , se labra la siguiente acta de novedad: Por ello se hace comparecer a/ al interno/a
                    <select id="dni_ppl" name="dni_ppl" class="inline-field field-interno" required>
                        <option value="">Seleccione un interno</option>
                        <?php 
                        if ($result_internos) {
                            $result_internos->data_seek(0);
                            while ($interno = $result_internos->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($interno['dni']) . '" ';
                                echo ($datos['dni_ppl'] == $interno['dni']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($interno['nombre_apellido']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    alojado/a en el Sector Nº 
                    <select id="id_sector" name="id_sector" class="inline-field field-medium" required>
                        <option value="">Seleccione sector</option>
                        <?php 
                        if ($result_sectores) {
                            $result_sectores->data_seek(0);
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
                        <option value="">Seleccione pabellón</option>
                        <?php 
                        if ($result_pabellones) {
                            $result_pabellones->data_seek(0);
                            while ($pabellon = $result_pabellones->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($pabellon['id']) . '"';
                                echo ($datos['id_pabellon'] == $pabellon['id']) ? ' selected' : '';
                                echo '>' . htmlspecialchars($pabellon['nombre']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    . Se realiza atención nutricional. Se toman medidas antropométricas (Peso Actual 
                    <input type="number" id="peso_kg" name="peso_kg" step="0.01" min="0" 
                           value="<?php echo htmlspecialchars($datos['peso_kg']); ?>" 
                           class="inline-field" required> Kg, Talla 
                    <input type="number" id="talla_m" name="talla_m" step="0.01" min="0" 
                           value="<?php echo htmlspecialchars($datos['talla_m']); ?>" 
                           class="inline-field" required> mt), arrojando un IMC de
                    <span id="imc_display" class="imc-inline">-</span>
                </div>

                <div class="acta-paragraph">
                    Se indican medidas higiénico dietéticas durante la atención nutricional.
                </div>

                <div class="acta-paragraph">
                    <strong>Observaciones:</strong>
                    <div class="observaciones">
                        <textarea id="observaciones" name="observaciones" 
                                  style="width: 100%; border: none; padding: 5px; font-family: 'Times New Roman', Times, serif; font-size: 12pt; min-height: 60px; resize: vertical; box-sizing: border-box;"
                                  placeholder="Escriba aquí las observaciones..."><?php echo htmlspecialchars($datos['observaciones']); ?></textarea>
                    </div>
                </div>

                <div class="acta-paragraph">
                    Sin más se da por finalizado el acta, a un solo efecto, firma y aclaración de puño y letra que certifican la presente, cuyo ejemplar es único.
                </div>
            </div>

            <!-- Sección de firmas - IGUAL AL AGREGAR.PHP -->
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
                               value="<?php echo htmlspecialchars($datos['aclaracion']); ?>" 
                               placeholder="Aclaración">
                    </div>
                    
                    <!-- Firma Oficial -->
                    <div class="firma-group">
                        <div class="firma-label">FIRMA OFICIAL ACTUANTE</div>
                        <input type="text" class="firma-input" id="firma_oficial" name="firma_oficial" 
                               value="<?php echo htmlspecialchars($datos['firma_oficial']); ?>" 
                               placeholder="Firma del oficial">
                        <div style="margin-top: 10px; font-size: 10pt;">
                            <span>DNI: </span>
                            <input type="text" class="firma-input" id="dni_firma" name="dni_firma" 
                                   value="<?php echo htmlspecialchars($datos['dni_firma']); ?>" 
                                   placeholder="DNI" style="width: 100px; display: inline-block;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn">Actualizar Acta</button>
            </div>
        </form>
    </div>

    <script>
        // Función para calcular IMC y clasificación
        function calcularIMC() {
            const peso = parseFloat(document.getElementById('peso_kg').value);
            const talla = parseFloat(document.getElementById('talla_m').value);
            const imcDisplay = document.getElementById('imc_display');
            
            if (peso && talla && talla > 0) {
                const imc = peso / (talla * talla);
                
                // Clasificar IMC
                let clasificacion = '';
                if (imc < 18.5) {
                    clasificacion = 'Bajo peso';
                } else if (imc < 25) {
                    clasificacion = 'Peso normal';
                } else if (imc < 30) {
                    clasificacion = 'Sobrepeso';
                } else if (imc < 35) {
                    clasificacion = 'Obesidad grado I';
                } else if (imc < 40) {
                    clasificacion = 'Obesidad grado II';
                } else {
                    clasificacion = 'Obesidad grado III';
                }
                
                // Mostrar el IMC con el formato: valor (clasificación)
                imcDisplay.textContent = `${imc.toFixed(2)} (${clasificacion})`;
            } else {
                imcDisplay.textContent = '-';
            }
        }

        // Inicializar datos al cargar la página
        window.addEventListener('load', function() {
            calcularIMC();
        });

        // Event listeners
        document.getElementById('peso_kg').addEventListener('input', calcularIMC);
        document.getElementById('talla_m').addEventListener('input', calcularIMC);
    </script>
</body>
</html>