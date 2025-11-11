<?php
include '../../includes/auth-check.php';
include '../../includes/conexion.php';

// Verificación básica de permisos
if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../login.php");
    exit();
}

// Obtener el ID del registro a visualizar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mensaje_error'] = "ID de registro no válido";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

// Obtener los datos del registro
$sql = "SELECT inr.*, p.nombre_apellido, p.fecha_nacimiento, p.edad, 
               td.nombre_dieta, u.nombre_usuario as usuario_nombre
        FROM ingreso_nutricional inr 
        JOIN ppl p ON inr.dni_ppl = p.dni 
        LEFT JOIN tipos_dieta td ON inr.id_dieta = td.id_dieta
        LEFT JOIN usuarios u ON inr.id_usuario = u.id_usuario
        WHERE inr.id = ?";
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

// DEBUG: Verificar datos
error_log("=== VER.PH DEBUG ===");
error_log("ID: " . $registro['id']);
error_log("Diagnóstico: " . ($registro['diagnostico'] ?? 'NULL'));
error_log("Antecedentes: " . ($registro['antecedentes_pat'] ?? 'NULL'));
error_log("Longitud antecedentes: " . strlen($registro['antecedentes_pat'] ?? '0'));

// Calcular IMC (CON VALIDACIÓN)
$imc = 0;
if ($registro['talla_m'] > 0) {
    $imc = $registro['peso_kg'] / ($registro['talla_m'] * $registro['talla_m']);
}

// Formatear certificación médica
$certificacion_med = $registro['certificacion_med'] ? 'SI' : 'NO';

// FUNCIÓN MEJORADA PARA MOSTRAR CAMPOS DE TEXTO
function mostrarCampoTexto($valor, $campoNombre) {
    // Verificar si el valor existe y no está vacío
    if (isset($valor) && $valor !== '' && trim($valor) !== '') {
        return nl2br(htmlspecialchars($valor));
    } else {
        return '<span style="color: #999; font-style: italic;">No se registró ' . $campoNombre . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Ingreso Nutricional</title>
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
            background: transparent;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            padding: 0 5px;
            display: inline;
            margin-left: 5px;
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
        
        .firma-line {
            border-bottom: 1px solid #000;
            padding: 15px 0 5px 0;
            min-height: 20px;
        }
        
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            color: #666;
            font-size: 10pt;
            font-family: Arial, sans-serif;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        
        .text-area-content {
            border: 1px solid #000;
            padding: 8px;
            margin: 10px 0;
            white-space: pre-wrap;
            line-height: 1.4;
            min-height: 80px;
            background-color: white;
        }
        
        .text-area-content-large {
            border: 1px solid #000;
            padding: 8px;
            margin: 10px 0;
            white-space: pre-wrap;
            line-height: 1.4;
            min-height: 150px;
            background-color: white;
        }
        
        .empty-field {
            color: #666;
            font-style: italic;
        }
        
        .diagnostico-cell {
            vertical-align: top;
            text-align: left;
        }
        
        .content-show {
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.4;
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
        
        @media print {
            .actions, .user-info {
                display: none;
            }
            
            .page-a4 {
                margin: 0;
                box-shadow: none;
                padding: 1.5cm;
            }
            
            body {
                background: white;
            }
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

        <div class="info-section">
            <strong>Registrado por:</strong> <?php echo htmlspecialchars($registro['usuario_nombre'] ?? 'N/A'); ?> | 
            <strong>Fecha de registro:</strong> <?php echo htmlspecialchars($registro['fecha_ingreso']); ?> |
            <strong>ID Registro:</strong> <?php echo htmlspecialchars($registro['id']); ?>
        </div>

        <!-- Sección de datos básicos - SIN LÍNEAS, RESPUESTAS AL LADO DE LOS DOS PUNTOS -->
        <div class="section">
            <div class="datos-basicos-linea">
                <span class="dato-label">FECHA DE INGRESO:</span>
                <span class="dato-input"><?php echo htmlspecialchars($registro['fecha_ingreso']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">NOMBRE Y APELLIDO:</span>
                <span class="dato-input"><?php echo htmlspecialchars($registro['nombre_apellido']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">FECHA DE NACIMIENTO:</span>
                <span class="dato-input"><?php echo htmlspecialchars($registro['fecha_nacimiento']); ?></span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">EDAD:</span>
                <span class="dato-input"><?php echo htmlspecialchars($registro['edad']); ?> años</span>
            </div>
            
            <div class="datos-basicos-linea">
                <span class="dato-label">DNI:</span>
                <span class="dato-input"><?php echo htmlspecialchars($registro['dni_ppl']); ?></span>
            </div>
        </div>

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
                            <td><?php echo htmlspecialchars($registro['peso_kg']); ?></td>
                            <td><?php echo htmlspecialchars($registro['talla_m']); ?></td>
                            <td><?php echo number_format($imc, 2); ?></td>
                            <td class="diagnostico-cell">
                                <div class="text-area-content content-show">
                                    <?php echo mostrarCampoTexto($registro['diagnostico'] ?? '', 'diagnóstico'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($registro['nombre_dieta'] ?? 'No especificada'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección de antecedentes -->
        <div class="section">
            <div class="section-title">ANTECEDENTES PATOLÓGICOS:</div>
            <div class="text-area-content-large content-show">
                <?php echo mostrarCampoTexto($registro['antecedentes_pat'] ?? '', 'antecedentes patológicos'); ?>
            </div>
            
            <!-- CERTIFICACIÓN MÉDICA EN LÍNEA -->
            <div class="certificacion-medica">
                <span class="certificacion-label">Certificación médica:</span>
                <span class="dato-input"><?php echo $certificacion_med; ?></span>
            </div>
        </div>

        <!-- Sección de firmas -->
        <div class="firma-section">
            <div class="firma-container">
                <!-- Firma PPL -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA DEL INTERNO</div>
                    <div class="firma-line">
                        <?php 
                        if (!empty($registro['firma_ppl'])) {
                            echo htmlspecialchars($registro['firma_ppl']);
                        } else {
                            echo '<span class="empty-field">_________________________</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Espacio en blanco -->
                <div class="firma-group">
                    <div class="firma-label">&nbsp;</div>
                    <div class="firma-line">&nbsp;</div>
                </div>
                
                <!-- Firma Efectivo -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA DEL EFECTIVO</div>
                    <div class="firma-line">
                        <?php 
                        if (!empty($registro['firma_efectivo'])) {
                            echo htmlspecialchars($registro['firma_efectivo']);
                        } else {
                            echo '<span class="empty-field">_________________________</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="index.php" class="btn btn-secondary">Volver al Listado</a>
            <a href="pdf.php?id=<?php echo $id; ?>" class="btn" target="_blank">Descargar PDF</a>
            <a href="editar.php?id=<?php echo $id; ?>" class="btn">Editar</a>
        </div>
    </div>

    <script>
        // Función para mejorar la impresión
        function prepararImpresion() {
            const style = document.createElement('style');
            style.textContent = `
                @media print {
                    .actions, .user-info { display: none !important; }
                    body { background: white !important; }
                    .page-a4 { 
                        margin: 0 !important; 
                        box-shadow: none !important; 
                        padding: 1.5cm !important;
                    }
                    .text-area-content, .text-area-content-large {
                        background: white !important;
                        border: 1px solid #000 !important;
                    }
                    div[style*="background: #f0f0f0"] {
                        display: none !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        document.addEventListener('DOMContentLoaded', prepararImpresion);
    </script>
</body>
</html>