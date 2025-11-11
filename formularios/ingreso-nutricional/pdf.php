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

// Obtener los datos del registro (CONSULTA CORREGIDA)
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

// Calcular IMC (CON VALIDACIÓN)
$imc = 0;
if ($registro['talla_m'] > 0) {
    $imc = $registro['peso_kg'] / ($registro['talla_m'] * $registro['talla_m']);
}

// Formatear certificación médica
$certificacion_med = $registro['certificacion_med'] ? 'SI' : 'NO';

// DEBUG: Verificar datos del registro
error_log("=== DATOS DEL REGISTRO EN PDF ===");
error_log("antecedentes_pat: " . $registro['antecedentes_pat']);
error_log("Longitud antecedentes_pat: " . strlen($registro['antecedentes_pat']));
error_log("Tipo de antecedentes_pat: " . gettype($registro['antecedentes_pat']));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLANILLA DE INGRESO NUTRICIONAL - PDF</title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 2cm;
            }
            
            body {
                margin: 0;
                padding: 0;
                font-family: 'Times New Roman', Times, serif;
                font-size: 12pt;
                line-height: 1.2;
                color: #000;
                background: #fff;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            .actions {
                display: none !important;
            }
            
            .page-a4 {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
            
            .logo-img {
                max-height: 80px !important;
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 20px;
            font-size: 12pt;
            line-height: 1.2;
            color: #000;
            background: #f5f5f5;
        }
        
        .page-a4 {
            width: 21cm;
            min-height: 29.7cm;
            margin: 0 auto;
            padding: 2cm;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .logo-img {
            max-height: 80px;
            margin-bottom: 10px;
            display: block;
            margin-left: auto;
            margin-right: auto;
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
            font-weight: bold;
        }
        
        .firma-line {
            border-bottom: 1px solid #000;
            padding: 15px 0 5px 0;
            min-height: 20px;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            font-size: 10pt;
        }
        
        .text-area-content {
            border: 1px solid #000;
            padding: 8px;
            min-height: 80px;
            margin: 10px 0;
            white-space: pre-line;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.4;
        }
        
        .actions {
            text-align: center;
            margin: 20px 0;
            padding: 20px;
        }
        
        .btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin: 0 10px;
        }
        
        .btn-print {
            background-color: #2196F3;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .logo-placeholder {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #ccc;
            margin-bottom: 10px;
            color: #666;
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
    </style>
</head>
<body>
    <div class="page-a4">
        <!-- Encabezado de la planilla con logo -->
        <div class="header">
            <?php
            $logo_path = '../../assets/img/logo.png';
            $logo_exists = file_exists($logo_path);
            
            if ($logo_exists): ?>
                <img src="<?php echo $logo_path; ?>" alt="Logo Gobierno" class="logo-img">
            <?php else: ?>
                <div class="logo-placeholder">
                    LOGO INSTITUCIONAL<br>
                    <small>(No se encontró logo.png en assets/img/)</small>
                </div>
            <?php endif; ?>
            <h1>PLANILLA DE INGRESO NUTRICIONAL</h1>
        </div>

        <div class="info-section no-print">
            <strong>Registrado por:</strong> <?php echo htmlspecialchars($registro['usuario_nombre'] ?? 'N/A'); ?> | 
            <strong>Fecha de registro:</strong> <?php echo htmlspecialchars($registro['fecha_ingreso']); ?>
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
                            <td><?php echo htmlspecialchars($registro['diagnostico']); ?></td>
                            <td><?php echo htmlspecialchars($registro['nombre_dieta'] ?? 'No especificada'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección de antecedentes - CORREGIDA -->
        <div class="section">
            <div class="section-title">ANTECEDENTES PATOLÓGICOS:</div>
            <div class="text-area-content">
                <?php 
                // CORRECCIÓN: Mostrar correctamente los antecedentes
                $antecedentes = $registro['antecedentes_pat'];
                
                // Si está vacío o es 0, mostrar mensaje
                if (empty($antecedentes) || $antecedentes === '0') {
                    echo "No se registraron antecedentes patológicos.";
                } else {
                    // Usar htmlspecialchars y nl2br para preservar saltos de línea
                    echo nl2br(htmlspecialchars($antecedentes));
                }
                ?>
            </div>
            
            <!-- CERTIFICACIÓN MÉDICA EN LÍNEA -->
            <div class="certificacion-medica">
                <span class="certificacion-label">Certificación médica:</span>
                <span class="dato-input"><?php echo $certificacion_med; ?></span>
            </div>
        </div>

        <!-- Sección de firmas - CORREGIDO: SIN ACLARACION -->
        <div class="firma-section">
            <div class="firma-container">
                <!-- Firma PPL -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA PPL</div>
                    <div class="firma-line"><?php echo htmlspecialchars($registro['firma_ppl']); ?></div>
                </div>
                
                <!-- Firma Efectivo -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA EFECTIVO</div>
                    <div class="firma-line"><?php echo htmlspecialchars($registro['firma_efectivo']); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="actions no-print">
        <button onclick="window.print()" class="btn btn-print">🖨️ Imprimir PDF</button>
        <a href="index.php" class="btn btn-secondary">📋 Volver al Listado</a>
        <a href="ver.php?id=<?php echo $id; ?>" class="btn">👁️ Ver Detalles</a>
    </div>

    <script>
        // Auto-imprimir cuando se carga la página (opcional - descomenta si lo quieres)
        // window.onload = function() {
        //     setTimeout(function() {
        //         window.print();
        //     }, 1000);
        // };
        
        // Detectar si se cerró el diálogo de impresión
        window.addEventListener('afterprint', function() {
            // Opcional: redirigir después de imprimir
            // window.location.href = 'index.php';
        });
    </script>
</body>
</html>