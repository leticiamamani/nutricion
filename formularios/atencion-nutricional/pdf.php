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
$sql = "SELECT an.*, p.nombre_apellido, s.nombre as sector_nombre, pb.nombre as pabellon_nombre
        FROM atencion_nutricional an 
        JOIN ppl p ON an.dni_ppl = p.dni 
        JOIN sector s ON an.id_sector = s.id
        JOIN pabellon pb ON an.id_pabellon = pb.id
        WHERE an.id = ?";
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

// Calcular IMC
$imc = 0;
if ($registro['talla_m'] > 0) {
    $imc = $registro['peso_kg'] / ($registro['talla_m'] * $registro['talla_m']);
}

// Función para clasificar IMC
function clasificarIMC($imc) {
    if ($imc == 0) return "No calculable";
    if ($imc < 18.5) return "Bajo peso";
    if ($imc < 25) return "Peso normal";
    if ($imc < 30) return "Sobrepeso";
    if ($imc < 35) return "Obesidad grado I";
    if ($imc < 40) return "Obesidad grado II";
    return "Obesidad grado III";
}

$clasificacion_imc = clasificarIMC($imc);

// Formatear fecha
$fecha = $registro['fecha'];
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
    <title>ACTA DE ATENCIÓN NUTRICIONAL - PDF</title>
    <style>
        /* Estilos optimizados para impresión PDF */
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
        
        .firma-line {
            border-bottom: 1px solid #000;
            padding: 15px 0 5px 0;
            min-height: 20px;
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
        
        .imc-classification {
            display: inline-block;
            font-style: italic;
            color: #666;
            margin-left: 10px;
        }
        
        .observaciones {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 10px;
            min-height: 80px;
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
        }
    </style>
</head>
<body>
    <div class="page-a4">
        <!-- Encabezado del acta con logo -->
        <div class="header">
            <img src="../../assets/img/logo.png" alt="Logo Gobierno" onerror="this.style.display='none'; document.getElementById('logo-placeholder').style.display='flex';">
            <div id="logo-placeholder" class="logo-placeholder" style="display:none;">LOGO INSTITUCIONAL</div>
            <h3>Jefatura de Sanidad - Área Nutrición</h3>
            <h3>ACTA DE ATENCIÓN NUTRICIONAL</h3>
        </div>

        <div class="acta-content">
            <div class="acta-paragraph">
                En San Juan, Servicio Penitenciario Provincial, a los 
                <span class="inline-field"><?php echo $dia; ?></span> días del mes de 
                <span class="inline-field field-medium"><?php echo $meses[$mes]; ?></span> 
                del año 
                <span class="inline-field"><?php echo $anio; ?></span>
                , se labra la siguiente acta de novedad: Por ello se hace comparecer a/ al interno/a
                <span class="inline-field field-interno"><?php echo htmlspecialchars($registro['nombre_apellido']); ?></span>
                alojado/a en el Sector Nº 
                <span class="inline-field field-medium"><?php echo htmlspecialchars($registro['sector_nombre']); ?></span>
                pabellón Nº 
                <span class="inline-field field-medium"><?php echo htmlspecialchars($registro['pabellon_nombre']); ?></span>
                . Se realiza atención nutricional. Se toman medidas antropométricas (Peso Actual 
                <span class="inline-field"><?php echo htmlspecialchars($registro['peso_kg']); ?></span> Kg, Talla 
                <span class="inline-field"><?php echo htmlspecialchars($registro['talla_m']); ?></span> mt), arrojando un IMC de
                <span class="imc-inline"><?php echo number_format($imc, 2); ?></span>
                <span class="imc-classification">(<?php echo $clasificacion_imc; ?>)</span>
            </div>

            <div class="acta-paragraph">
                Se indican medidas higiénico dietéticas durante la atención nutricional.
            </div>

            <?php if (!empty($registro['observaciones'])): ?>
            <div class="acta-paragraph">
                <strong>Observaciones:</strong>
                <div class="observaciones">
                    <?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="acta-paragraph">
                Sin más se da por finalizado el acta, a un solo efecto, firma y aclaración de puño y letra que certifican la presente, cuyo ejemplar es único.
            </div>
        </div>

        <!-- Sección de firmas -->
        <div class="firma-section">
            <div class="firma-container">
                <!-- Firma PPL -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA</div>
                    <div class="firma-line"><?php echo htmlspecialchars($registro['firma_ppl']); ?></div>
                </div>
                
                <!-- Aclaración -->
                <div class="firma-group">
                    <div class="firma-label">ACLARACIÓN</div>
                    <div class="firma-line"><?php echo htmlspecialchars($registro['aclaracion']); ?></div>
                </div>
                
                <!-- Firma Oficial -->
                <div class="firma-group">
                    <div class="firma-label">FIRMA OFICIAL ACTUANTE</div>
                    <div class="firma-line"><?php echo htmlspecialchars($registro['firma_oficial']); ?></div>
                    <div style="margin-top: 10px; font-size: 10pt;">
                        <span>DNI: <?php echo htmlspecialchars($registro['dni_firma']); ?></span>
                    </div>
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
        // Auto-imprimir cuando se carga la página
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Redirigir después de imprimir
        window.addEventListener('afterprint', function() {
            window.location.href = 'ver.php?id=<?php echo $id; ?>';
        });
    </script>
</body>
</html>