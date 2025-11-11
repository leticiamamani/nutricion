<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificación básica de permisos
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Verificar que se recibe el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "❌ No se especificó el registro a editar";
    header("Location: index.php");
    exit();
}

$id_entrega = intval($_GET['id']);

// Obtener datos del registro actual
$query_entrega = "SELECT * FROM entrega_productos WHERE id = ?";
$stmt_entrega = $conexion->prepare($query_entrega);
$stmt_entrega->bind_param("i", $id_entrega);
$stmt_entrega->execute();
$result_entrega = $stmt_entrega->get_result();

if ($result_entrega->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Registro no encontrado";
    header("Location: index.php");
    exit();
}

$registro = $result_entrega->fetch_assoc();

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
    'id_sector' => $registro['id_sector'],
    'id_pabellon' => $registro['id_pabellon'],
    'tipo_producto' => $registro['tipo_producto'],
    'cantidad_leche' => '',
    'fecha_vto_leche' => '',
    'cantidad_galletas' => '',
    'fecha_vto_galletas' => '',
    'cantidad_pan' => '',
    'fecha_vto_pan' => '',
    'firma_ppl' => $registro['firma_ppl'],
    'aclaracion' => $registro['aclaracion'],
    'firma_efectivo' => $registro['firma_efectivo']
];

// Procesar datos existentes según el tipo de producto
if ($registro['tipo_producto'] === 'LECHE_DESLACTOSADA') {
    $datos['cantidad_leche'] = $registro['cantidad'];
    $datos['fecha_vto_leche'] = $registro['fecha_vto'];
} elseif ($registro['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
    $datos['cantidad_galletas'] = $registro['cantidad_galletas'];
    $datos['fecha_vto_galletas'] = $registro['fecha_vto_galletas'];
    $datos['cantidad_pan'] = $registro['cantidad_pan'];
    $datos['fecha_vto_pan'] = $registro['fecha_vto_pan'];
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y validar los datos del formulario
    $datos['dni_ppl'] = trim($_POST['dni_ppl'] ?? '');
    $datos['fecha'] = trim($_POST['fecha'] ?? '');
    $datos['id_sector'] = trim($_POST['id_sector'] ?? '');
    $datos['id_pabellon'] = trim($_POST['id_pabellon'] ?? '');
    $datos['tipo_producto'] = trim($_POST['tipo_producto'] ?? '');
    $datos['firma_ppl'] = trim($_POST['firma_ppl'] ?? '');
    $datos['aclaracion'] = trim($_POST['aclaracion'] ?? '');
    $datos['firma_efectivo'] = trim($_POST['firma_efectivo'] ?? '');

    // Validaciones comunes
    if (empty($datos['dni_ppl'])) {
        $errores[] = "El campo interno es obligatorio.";
    }
    
    if (empty($datos['fecha'])) {
        $errores[] = "El campo fecha es obligatorio.";
    }
    
    if (empty($datos['id_sector'])) {
        $errores[] = "El campo sector es obligatorio.";
    }
    
    if (empty($datos['id_pabellon'])) {
        $errores[] = "El campo pabellón es obligatorio.";
    }

    if (empty($datos['tipo_producto'])) {
        $errores[] = "Debe seleccionar el tipo de producto.";
    }

    // Validaciones específicas por tipo de producto
    if ($datos['tipo_producto'] === 'LECHE_DESLACTOSADA') {
        $datos['cantidad_leche'] = trim($_POST['cantidad_leche'] ?? '');
        $datos['fecha_vto_leche'] = trim($_POST['fecha_vto_leche'] ?? '');
        
        if (empty($datos['cantidad_leche'])) {
            $errores[] = "La cantidad de leche es obligatoria.";
        }
        if (empty($datos['fecha_vto_leche'])) {
            $errores[] = "La fecha de vencimiento de la leche es obligatoria.";
        }
    } elseif ($datos['tipo_producto'] === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
        $datos['cantidad_galletas'] = trim($_POST['cantidad_galletas'] ?? '');
        $datos['cantidad_pan'] = trim($_POST['cantidad_pan'] ?? '');
        $datos['fecha_vto_galletas'] = trim($_POST['fecha_vto_galletas'] ?? '');
        $datos['fecha_vto_pan'] = trim($_POST['fecha_vto_pan'] ?? '');
        
        if (empty($datos['cantidad_galletas'])) {
            $errores[] = "La cantidad de galletas es obligatoria.";
        }
        if (empty($datos['cantidad_pan'])) {
            $errores[] = "La cantidad de pan es obligatoria.";
        }
        if (empty($datos['fecha_vto_galletas'])) {
            $errores[] = "La fecha de vencimiento de las galletas es obligatoria.";
        }
        if (empty($datos['fecha_vto_pan'])) {
            $errores[] = "La fecha de vencimiento del pan es obligatoria.";
        }
    }

    // Si no hay errores, proceder a actualizar
    if (empty($errores)) {
        // Determinar valores según el tipo de producto
        if ($datos['tipo_producto'] === 'LECHE_DESLACTOSADA') {
            $cantidad = $datos['cantidad_leche'];
            $fecha_vto = $datos['fecha_vto_leche'];
            $cantidad_galletas = NULL;
            $fecha_vto_galletas = NULL;
            $cantidad_pan_db = NULL;
            $fecha_vto_pan_db = NULL;
        } else {
            $cantidad = NULL;
            $fecha_vto = NULL;
            $cantidad_galletas = $datos['cantidad_galletas'];
            $fecha_vto_galletas = $datos['fecha_vto_galletas'];
            $cantidad_pan_db = $datos['cantidad_pan'];
            $fecha_vto_pan_db = $datos['fecha_vto_pan'];
        }
        
        // Actualizar en la base de datos
        $sql = "UPDATE entrega_productos 
                SET fecha = ?, id_sector = ?, id_pabellon = ?, dni_ppl = ?, 
                    tipo_producto = ?, cantidad = ?, fecha_vto = ?, 
                    cantidad_galletas = ?, fecha_vto_galletas = ?, 
                    cantidad_pan = ?, fecha_vto_pan = ?,
                    firma_ppl = ?, aclaracion = ?, firma_efectivo = ?
                WHERE id = ?";
        
        $stmt = $conexion->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param(
                "siisssssssssssi",
                $datos['fecha'],
                $datos['id_sector'],
                $datos['id_pabellon'],
                $datos['dni_ppl'],
                $datos['tipo_producto'],
                $cantidad,
                $fecha_vto,
                $cantidad_galletas,
                $fecha_vto_galletas,
                $cantidad_pan_db,
                $fecha_vto_pan_db,
                $datos['firma_ppl'],
                $datos['aclaracion'],
                $datos['firma_efectivo'],
                $id_entrega
            );
            
            if ($stmt->execute()) {
                $_SESSION['mensaje'] = "✅ Acta de entrega actualizada correctamente";
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
$fecha_registro = $registro['fecha'];
$dia_registro = date('d', strtotime($fecha_registro));
$mes_registro = date('m', strtotime($fecha_registro));
$anio_registro = date('Y', strtotime($fecha_registro));

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
    <title>Editar Acta de Entrega</title>
    <style>
        /* Estilos idénticos a agregar.php */
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
        
        .field-small {
            width: 50px;
        }
        
        .field-medium {
            width: 120px;
        }
        
        .field-interno {
            width: 350px;
        }
        
        .field-cantidad {
            width: 80px;
        }
        
        .field-fecha-vto {
            width: 120px;
        }
        
        .selector-tipo {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        
        .selector-tipo label {
            font-weight: bold;
            margin-right: 15px;
        }
        
        .selector-tipo select {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px;
            background: white;
        }
        
        .acta-leche, .acta-galletas-pan {
            display: none;
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
            <h3>EFECTIVO ACTUANTE</h3>
            <h3>ACTA DE ENTREGA</h3>
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
            <!-- Selector de tipo de producto -->
            <div class="selector-tipo">
                <label><b>Seleccionar tipo de Producto:</b></label>
                <select id="tipo_producto" name="tipo_producto" onchange="mostrarProducto(this.value)" class="form-control" style="max-width:300px;" required>
                    <option value="">-- Elegir --</option>
                    <option value="LECHE_DESLACTOSADA" <?php echo ($datos['tipo_producto'] == 'LECHE_DESLACTOSADA') ? 'selected' : ''; ?>>Leche Deslactosada</option>
                    <option value="GALLETAS_ARROZ+PAN_SIN_TACC" <?php echo ($datos['tipo_producto'] == 'GALLETAS_ARROZ+PAN_SIN_TACC') ? 'selected' : ''; ?>>Galletas de Arroz + Pan Sin TACC</option>
                </select>
            </div>

            <div class="acta-content">
                <div class="acta-paragraph">
                    En San Juan, Servicio Penitenciario Provincial, a los 
                    <input type="number" id="dia" name="dia" class="inline-field field-small" min="1" max="31" 
                           value="<?php echo $dia_registro; ?>"> días del mes de 
                    <select id="mes" name="mes" class="inline-field field-medium">
                        <?php foreach ($meses as $num => $nombre): ?>
                            <option value="<?php echo $num; ?>" <?php echo ($mes_registro == $num) ? 'selected' : ''; ?>>
                                <?php echo $nombre; ?>
                            </option>
                        <?php endforeach; ?>
                    </select> 
                    del año 
                    <input type="number" id="anio" name="anio" class="inline-field field-small" 
                           value="<?php echo $anio_registro; ?>">
                    se labra la siguiente acta de Entrega: Por ello se hace comparecer a la P.P.L.
                    <select id="dni_ppl" name="dni_ppl" class="inline-field field-interno" required>
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
                    alojado en el Sector Nº 
                    <select id="id_sector" name="id_sector" class="inline-field field-medium" required>
                        <option value="">Seleccione</option>
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
                    Pabellón Nº 
                    <select id="id_pabellon" name="id_pabellon" class="inline-field field-medium" required>
                        <option value="">Seleccione</option>
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
                </div>

                <!-- Sección para Leche Deslactosada -->
                <div id="acta-leche" class="acta-leche">
                    <div class="acta-paragraph">
                        Se le hace entrega de 
                        <input type="number" id="cantidad_leche" name="cantidad_leche" class="inline-field field-cantidad" 
                               value="<?php echo htmlspecialchars($datos['cantidad_leche']); ?>" 
                               min="1" placeholder="Cant."> 
                        unidades de leche deslactosada VTO 
                        <input type="date" id="fecha_vto_leche" name="fecha_vto_leche" class="inline-field field-fecha-vto" 
                               value="<?php echo htmlspecialchars($datos['fecha_vto_leche']); ?>">
                    </div>
                </div>

                <!-- Sección para Galletas + Pan -->
                <div id="acta-galletas-pan" class="acta-galletas-pan">
                    <div class="acta-paragraph">
                        Se le hace entrega de 
                        <input type="number" id="cantidad_galletas" name="cantidad_galletas" class="inline-field field-cantidad" 
                               value="<?php echo htmlspecialchars($datos['cantidad_galletas']); ?>" 
                               min="1" placeholder="Cant."> 
                        paquetes de galletas de arroz VTO 
                        <input type="date" id="fecha_vto_galletas" name="fecha_vto_galletas" class="inline-field field-fecha-vto" 
                               value="<?php echo htmlspecialchars($datos['fecha_vto_galletas']); ?>">
                        y 
                        <input type="number" id="cantidad_pan" name="cantidad_pan" class="inline-field field-cantidad" 
                               value="<?php echo htmlspecialchars($datos['cantidad_pan']); ?>" 
                               min="1" placeholder="Cant."> 
                        unidades de pan tipo facial sin TACC VTO 
                        <input type="date" id="fecha_vto_pan" name="fecha_vto_pan" class="inline-field field-fecha-vto" 
                               value="<?php echo htmlspecialchars($datos['fecha_vto_pan']); ?>">
                    </div>
                </div>

                <div class="acta-paragraph">
                    Sin más se da por finalizado el acta, a un solo efecto, firma y aclaración de puño y letra que certifican la presente, cuyo ejemplar es único.
                </div>
            </div>

            <!-- Sección de firmas -->
            <div class="firma-section">
                <div class="firma-container">
                    <div class="firma-group">
                        <div class="firma-label">FIRMA</div>
                        <input type="text" class="firma-input" id="firma_ppl" name="firma_ppl" 
                               value="<?php echo htmlspecialchars($datos['firma_ppl']); ?>" 
                               placeholder="Firma del interno">
                    </div>
                    
                    <div class="firma-group">
                        <div class="firma-label">ACLARACIÓN</div>
                        <input type="text" class="firma-input" id="aclaracion" name="aclaracion" 
                               value="<?php echo htmlspecialchars($datos['aclaracion']); ?>" 
                               placeholder="Aclaración adicional">
                    </div>
                    
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
                <button type="submit" class="btn">Actualizar Acta</button>
            </div>

            <!-- Campo oculto para la fecha completa -->
            <input type="hidden" id="fecha" name="fecha" value="<?php echo $datos['fecha']; ?>">
        </form>
    </div>

    <script>
        // Función para mostrar el tipo de producto seleccionado
        function mostrarProducto(tipo) {
            // Ocultar todas las secciones primero
            document.getElementById('acta-leche').style.display = 'none';
            document.getElementById('acta-galletas-pan').style.display = 'none';
            
            // Mostrar la sección seleccionada
            if (tipo === 'LECHE_DESLACTOSADA') {
                document.getElementById('acta-leche').style.display = 'block';
            } else if (tipo === 'GALLETAS_ARROZ+PAN_SIN_TACC') {
                document.getElementById('acta-galletas-pan').style.display = 'block';
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

        // Mostrar la sección correspondiente al cargar la página si ya hay un tipo seleccionado
        window.addEventListener('load', function() {
            const tipoSeleccionado = document.getElementById('tipo_producto').value;
            if (tipoSeleccionado) {
                mostrarProducto(tipoSeleccionado);
            }
            actualizarFecha();
        });

        document.getElementById('dia').addEventListener('change', actualizarFecha);
        document.getElementById('mes').addEventListener('change', actualizarFecha);
        document.getElementById('anio').addEventListener('change', actualizarFecha);
    </script>
</body>
</html>