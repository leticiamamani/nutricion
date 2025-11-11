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
    $_SESSION['mensaje_error'] = "❌ No se especificó la recepción a visualizar";
    header("Location: index.php");
    exit();
}

$id_recepcion = intval($_GET['id']);

// Obtener datos de la recepción
$query_recepcion = "SELECT ra.*, p.nombre_apellido, s.nombre as sector_nombre, u.nombre_usuario
                   FROM recepcion_alimentos ra
                   INNER JOIN ppl p ON ra.dni_ppl = p.dni
                   INNER JOIN sector s ON ra.id_sector = s.id
                   INNER JOIN usuarios u ON ra.id_usuario = u.id_usuario
                   WHERE ra.id = ?";
$stmt_recepcion = $conexion->prepare($query_recepcion);
$stmt_recepcion->bind_param("i", $id_recepcion);
$stmt_recepcion->execute();
$result_recepcion = $stmt_recepcion->get_result();

if ($result_recepcion->num_rows === 0) {
    $_SESSION['mensaje_error'] = "❌ Recepción no encontrada";
    header("Location: index.php");
    exit();
}

$recepcion = $result_recepcion->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Recepción de Alimentos</title>
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
        
        .campo-comida-valor {
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            padding: 2px 5px;
            flex: 1;
            min-width: 0;
            min-height: 18px;
        }
        
        /* ESTILO PARA ACLARACIÓN EN LÍNEA */
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
        
        .aclaracion-valor {
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
        
        .readonly-field {
            background-color: #f9f9f9;
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
            <h1>VER RECEPCIÓN DE ALIMENTOS DE INTERNOS</h1>
        </div>

        <!-- Sección de datos básicos - AL LADO DE LOS DOS PUNTOS -->
        <div class="section">
            <div class="datos-basicos-linea">
                <span class="dato-label">NOMBRE:</span>
                <span class="dato-input readonly-field"><?php echo htmlspecialchars($recepcion['nombre_apellido']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">DNI:</span>
                <span class="dato-input readonly-field"><?php echo htmlspecialchars($recepcion['dni_ppl']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">SECTOR:</span>
                <span class="dato-input readonly-field"><?php echo htmlspecialchars($recepcion['sector_nombre']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">FECHA:</span>
                <span class="dato-input readonly-field"><?php echo date('d/m/Y', strtotime($recepcion['fecha'])); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">REGISTRADO POR:</span>
                <span class="dato-input readonly-field"><?php echo htmlspecialchars($recepcion['nombre_usuario']); ?></span>
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
                                        <span class="fecha-display"><?php echo date('d/m/Y', strtotime($recepcion['fecha'])); ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">HORA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo $recepcion['desayuno_hora'] ? date('H:i', strtotime($recepcion['desayuno_hora'])) : ''; ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">FIRMA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo htmlspecialchars($recepcion['desayuno_firma']); ?></span>
                                    </div>
                                    
                                    <!-- ACLARACIÓN EN LÍNEA -->
                                    <div class="aclaracion-linea">
                                        <span class="aclaracion-label">ACLARACIÓN:</span>
                                        <span class="aclaracion-valor readonly-field"><?php echo htmlspecialchars($recepcion['desayuno_aclaracion']); ?></span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Almuerzo -->
                            <td class="comida-cell">
                                <div class="comida-fields">
                                    <div class="fecha-comida">
                                        <span class="fecha-label">FECHA:</span>
                                        <span class="fecha-display"><?php echo date('d/m/Y', strtotime($recepcion['fecha'])); ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">HORA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo $recepcion['almuerzo_hora'] ? date('H:i', strtotime($recepcion['almuerzo_hora'])) : ''; ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">FIRMA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo htmlspecialchars($recepcion['almuerzo_firma']); ?></span>
                                    </div>
                                    
                                    <!-- ACLARACIÓN EN LÍNEA -->
                                    <div class="aclaracion-linea">
                                        <span class="aclaracion-label">ACLARACIÓN:</span>
                                        <span class="aclaracion-valor readonly-field"><?php echo htmlspecialchars($recepcion['almuerzo_aclaracion']); ?></span>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Cena -->
                            <td class="comida-cell">
                                <div class="comida-fields">
                                    <div class="fecha-comida">
                                        <span class="fecha-label">FECHA:</span>
                                        <span class="fecha-display"><?php echo date('d/m/Y', strtotime($recepcion['fecha'])); ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">HORA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo $recepcion['cena_hora'] ? date('H:i', strtotime($recepcion['cena_hora'])) : ''; ?></span>
                                    </div>
                                    
                                    <div class="campo-comida-linea">
                                        <span class="campo-comida-label">FIRMA:</span>
                                        <span class="campo-comida-valor readonly-field"><?php echo htmlspecialchars($recepcion['cena_firma']); ?></span>
                                    </div>
                                    
                                    <!-- ACLARACIÓN EN LÍNEA -->
                                    <div class="aclaracion-linea">
                                        <span class="aclaracion-label">ACLARACIÓN:</span>
                                        <span class="aclaracion-valor readonly-field"><?php echo htmlspecialchars($recepcion['cena_aclaracion']); ?></span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            <a href="editar.php?id=<?php echo $id_recepcion; ?>" class="btn">Editar</a>
            <a href="pdf.php?id=<?php echo $id_recepcion; ?>" target="_blank" class="btn">Generar PDF</a>
        </div>
    </div>
</body>
</html>