-- =====================================================
-- BASE DE DATOS: sistema_penitenciario_nutricion
-- DESCRIPCIÓN: Sistema integral de gestión nutricional para servicio penitenciario
-- BASADO EN: Formulario Área de Nutrición.pdf
-- FECHA: 2025
-- =====================================================

CREATE DATABASE IF NOT EXISTS nutricion;
USE nutricion;

-- =====================================================
-- 1. TABLAS MAESTRAS (NORMALIZADAS) - MODIFICADAS
-- =====================================================

-- SECTORES - Catálogo de sectores del penal
CREATE TABLE IF NOT EXISTS sector (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    tipo ENUM('ADULTOS','MENORES','SANIDAD') NOT NULL,
    capacidad INT DEFAULT 0,
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) COMMENT='Sectores principales del penal';

-- PABELLONES - Pabellones que dependen de sectores o son independientes
CREATE TABLE IF NOT EXISTS pabellon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sector INT NULL,
    nombre VARCHAR(50) NOT NULL,
    capacidad INT DEFAULT 0,
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pabellon_sector FOREIGN KEY (id_sector) REFERENCES sector(id) ON DELETE SET NULL
) COMMENT='Pabellones por sector o independientes';

-- CODIGOS_DIETA - Códigos estandarizados de tipos de dieta
CREATE TABLE IF NOT EXISTS codigos_dieta (
    id_codigo INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) UNIQUE NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    categoria ENUM('STANDARD', 'ESPECIAL') DEFAULT 'STANDARD',
    estado VARCHAR(10) DEFAULT 'ACTIVO'
) COMMENT = 'Códigos estandarizados de dietas - Planillas de Distribución';

-- TIPOS_DIETA - Tipos específicos de dietas médicas
CREATE TABLE IF NOT EXISTS tipos_dieta (
    id_dieta INT AUTO_INCREMENT PRIMARY KEY,
    id_codigo INT NOT NULL,
    nombre_dieta VARCHAR(50) NOT NULL,
    descripcion TEXT,
    indicacion_medica ENUM('SI', 'NO') NOT NULL,
    estado VARCHAR(10) DEFAULT 'ACTIVA',
    codigo_dieta VARCHAR(10),
    CONSTRAINT fk_tipos_dieta_codigo FOREIGN KEY (id_codigo) REFERENCES codigos_dieta(id_codigo)
) COMMENT = 'Tipos de dietas médicas - Formulario Atención Nutricional';

-- USUARIOS_SISTEMA - Personal del área de nutrición
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(100) NOT NULL,
    cargo VARCHAR(50) NOT NULL,
    area VARCHAR(50) NOT NULL,
    permisos ENUM('ADMIN', 'NUTRICION', 'ADMINISTRATIVO', 'GUARDIA') NOT NULL,
    fecha_alta DATE NOT NULL,
    usuario_login VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    ultimo_acceso DATETIME NULL,
    estado VARCHAR(10) DEFAULT 'ACTIVO',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) COMMENT = 'Personal del área de nutrición - Datos Generales del Formulario';

-- PPL - Personas Privadas de Libertad
CREATE TABLE IF NOT EXISTS ppl (
    dni VARCHAR(8) PRIMARY KEY,
    nombre_apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    edad INT NOT NULL,
    fecha_ingreso DATE NOT NULL,
    id_sector INT NOT NULL,
    id_pabellon INT NOT NULL,
    estado_legal ENUM('PENADO', 'PROCESADO') NOT NULL,
    estado VARCHAR(10) DEFAULT 'ACTIVO',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ppl_sector FOREIGN KEY (id_sector) REFERENCES sector(id),
    CONSTRAINT fk_ppl_pabellon FOREIGN KEY (id_pabellon) REFERENCES pabellon(id)
) COMMENT = 'Registro de Personas Privadas de Libertad - Base del Sistema';

-- =====================================================
-- 2. TABLAS DE NUTRICIÓN (FORMULARIOS PRINCIPALES)
-- =====================================================

-- ATENCION_NUTRICIONAL - Acta de Atención Nutricional
CREATE TABLE atencion_nutricional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni_ppl VARCHAR(8) NOT NULL,
    fecha DATE NOT NULL,
    id_sector INT,
    id_pabellon INT,
    peso_kg DECIMAL(5,2),
    talla_m DECIMAL(3,2),
    imc DECIMAL(4,2) AS (peso_kg / (talla_m * talla_m)) STORED,
    observaciones TEXT,
    firma_ppl TEXT,
    aclaracion TEXT,
    firma_oficial TEXT,
    dni_firma VARCHAR(20),
    id_usuario INT NOT NULL,
    CONSTRAINT fk_atencion_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_atencion_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_atencion_sector FOREIGN KEY (id_sector) REFERENCES sector(id) ON DELETE SET NULL,
    CONSTRAINT fk_atencion_pabellon FOREIGN KEY (id_pabellon) REFERENCES pabellon(id) ON DELETE SET NULL
) COMMENT = 'Acta de Atención Nutricional';

-- ACTA_NOVEDAD - Acta de Novedad del Área
CREATE TABLE acta_novedad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni_ppl VARCHAR(8) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    id_sector INT,
    id_pabellon INT,
    detalle_novedad TEXT NOT NULL,
    descargos_ppl TEXT,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_novedad_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_novedad_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_novedad_sector FOREIGN KEY (id_sector) REFERENCES sector(id) ON DELETE SET NULL,
    CONSTRAINT fk_novedad_pabellon FOREIGN KEY (id_pabellon) REFERENCES pabellon(id) ON DELETE SET NULL
) COMMENT = 'Acta de Novedad';

-- HUELGA_HAMBRE - Registro de Huelgas de Hambre
CREATE TABLE huelga_hambre (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni_ppl VARCHAR(8) NOT NULL,
    fecha DATE NOT NULL,
    id_sector INT,
    id_pabellon INT,
    peso_kg DECIMAL(5,2),
    talla_m DECIMAL(3,2),
    imc DECIMAL(4,2) AS (peso_kg / (talla_m * talla_m)) STORED,
    detalles TEXT NOT NULL,
    firma_ppl TEXT,
    aclaracion TEXT,
    firma_efectivo TEXT,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_huelga_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_huelga_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_huelga_sector FOREIGN KEY (id_sector) REFERENCES sector(id) ON DELETE SET NULL,
    CONSTRAINT fk_huelga_pabellon FOREIGN KEY (id_pabellon) REFERENCES pabellon(id) ON DELETE SET NULL
) COMMENT = 'Acta de Huelga de Hambre';

-- INGRESO_NUTRICIONAL - Planilla de Ingreso Nutricional
CREATE TABLE ingreso_nutricional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni_ppl VARCHAR(8) UNIQUE NOT NULL,
    fecha_ingreso DATE NOT NULL,
    peso_kg DECIMAL(5,2),
    talla_m DECIMAL(3,2),
    imc DECIMAL(4,2) AS (peso_kg / (talla_m * talla_m)) STORED,
    diagnostico TEXT,
    id_dieta INT,
    antecedentes_pat TEXT,
    certificacion_med BOOLEAN,
    firma_ppl TEXT,
    firma_efectivo TEXT,
    id_usuario INT,
    CONSTRAINT fk_ingreso_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_ingreso_dieta FOREIGN KEY (id_dieta) REFERENCES tipos_dieta(id_dieta),
    CONSTRAINT fk_ingreso_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) COMMENT = 'Planilla de Ingreso Nutricional';
  

-- Tabla para tipos de comida (solo para relación)
CREATE TABLE IF NOT EXISTS tipo_comida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre ENUM('DESAYUNO','ALMUERZO','CENA') NOT NULL UNIQUE,
    descripcion VARCHAR(255) NULL,
    estado ENUM('ACTIVO','INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar los tipos de comida
INSERT IGNORE INTO tipo_comida (nombre, descripcion) VALUES 
('DESAYUNO', 'Distribución de alimentos para el desayuno'),
('ALMUERZO', 'Distribución de alimentos para el almuerzo'),
('CENA', 'Distribución de alimentos para la cena');

-- Tabla para sectores predefinidos de distribución
CREATE TABLE IF NOT EXISTS sectores_distribucion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    tipo ENUM('SECTOR', 'PABELLON') DEFAULT 'SECTOR',
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar sectores predefinidos (CORREGIDO: SECTOR N°3 MENOR ES)
INSERT IGNORE INTO sectores_distribucion (nombre, tipo) VALUES 
('ATILA ANEXO N°3', 'SECTOR'),
('TANGO PAB 7 Y 8', 'PABELLON'),
('SECTOR N°3 MENOR ES', 'SECTOR'),
('SECTOR N°4 PAB2 S/4', 'SECTOR'),
('SECTOR N° 2', 'SECTOR'),
('SANIDAD', 'SECTOR'),
('PAB 14 Y 15', 'PABELLON');

-- Tabla de descripciones predefinidas para DESAYUNO
CREATE TABLE IF NOT EXISTS descripciones_desayuno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sector_distribucion INT NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_desc_desayuno_sector FOREIGN KEY (id_sector_distribucion) REFERENCES sectores_distribucion(id)
);

-- Insertar descripciones predefinidas para DESAYUNO
INSERT IGNORE INTO descripciones_desayuno (id_sector_distribucion, descripcion) VALUES 
(1, 'General. Leche entera con pan (500g x internos = 5 unidades.'),
(2, 'Celiacos: suplementación de Sobres con leche apta para celiacos.'),
(3, 'Colaciones para'),
(4, 'Celiacos: fruta. Desayunos para'),
(5, 'Diabéticos: te + leche + edulcorante.'),
(6, 'Leche deslactosada para indicaciones'),
(7, 'Especiales.');

-- Tabla de descripciones predefinidas para ALMUERZO
CREATE TABLE IF NOT EXISTS descripciones_almuerzo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sector_distribucion INT NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_desc_almuerzo_sector FOREIGN KEY (id_sector_distribucion) REFERENCES sectores_distribucion(id)
);

-- Insertar descripciones predefinidas para ALMUERZO (ACTUALIZADO según PDF)
INSERT IGNORE INTO descripciones_almuerzo (id_sector_distribucion, descripcion) VALUES 
(1, 'DBT: A'),
(2, 'CELIACOS: A ASTRINGENTES: B'),
(3, 'HIPOSODICAS: A BLANDA/SEMILIQUIDA: D GASTRO/INTESTINAL: A VEGETARIANO: A HEPATO/ALERGIA: A'),
(4, 'GASTRO/INTESTINAL: A VEGETARIANO: A'),
(5, 'HEPATO/ALERGIA: A'),
(6, 'HIPERCALORICA: C'),
(7, '');

-- Tabla de descripciones predefinidas para CENA
CREATE TABLE IF NOT EXISTS descripciones_cena (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_sector_distribucion INT NOT NULL,
    descripcion TEXT NOT NULL,
    estado ENUM('ACTIVO', 'INACTIVO') DEFAULT 'ACTIVO',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_desc_cena_sector FOREIGN KEY (id_sector_distribucion) REFERENCES sectores_distribucion(id)
);

-- Insertar descripciones predefinidas para CENA
INSERT IGNORE INTO descripciones_cena (id_sector_distribucion, descripcion) VALUES 
(1, 'SEGÚN TIPIFICACION INDICADA.'),
(2, 'SEGÚN TIPIFICACION INDICADA'),
(3, 'SEGÚN TIPIFICACION INDICADA'),
(4, 'SEGÚN TIPIFICACION INDICADA'),
(5, 'SEGÚN TIPIFICACION INDICADA'),
(6, 'SEGÚN TIPIFICACION INDICADA'),
(7, 'SEGÚN TIPIFICACION INDICADA');

-- Tabla principal de distribuciones
CREATE TABLE IF NOT EXISTS distribucion_alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Relación con tipo_comida
    id_tipo_comida INT NOT NULL,
    
    fecha DATE NOT NULL,
    hora TIME,
    
    -- Relación con sectores_distribucion
    id_sector_distribucion INT NOT NULL,
    
    -- Campos específicos por tipo de comida
    nro_colaciones INT COMMENT 'Número de colaciones',
    
    -- Campos para DESAYUNO
    pan_kg DECIMAL(4,2) COMMENT 'Cantidad de pan en kg (solo para desayuno)',
    te_mate_cocido ENUM('TÉ', 'MATE_COCIDO') NULL COMMENT 'Té o mate cocido (solo para desayuno)',
    
    -- Campos para ALMUERZO y CENA
    nro_dietas INT COMMENT 'Número de dietas especiales (almuerzo y cena)',
    nro_viandas_comunes INT COMMENT 'Número de viandas comunes (almuerzo y cena)',
    
    -- Campos de control y recepción
    hora_llegada TIME COMMENT 'Hora de llegada al sector',
    hora_recibido TIME COMMENT 'Hora en que fue recibido',
    firma VARCHAR(100) COMMENT 'Firma del responsable que recibe',
    aclaracion TEXT COMMENT 'Aclaraciones o observaciones',
    
    -- Usuario que registra
    id_usuario INT NOT NULL,
        
    -- CLAVES FORÁNEAS
    CONSTRAINT fk_dist_tipo_comida 
        FOREIGN KEY (id_tipo_comida) REFERENCES tipo_comida(id),
    CONSTRAINT fk_dist_sector_distribucion 
        FOREIGN KEY (id_sector_distribucion) REFERENCES sectores_distribucion(id),
    CONSTRAINT fk_dist_usuario 
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    
    -- Índices
    INDEX idx_tipo_fecha (id_tipo_comida, fecha),
    INDEX idx_fecha (fecha),
    INDEX idx_sector (id_sector_distribucion)
) COMMENT = 'Planillas de Distribución Diaria';

--RECEPCION DE ALIMENTOS planilla de recepcion de alimentos
CREATE TABLE recepcion_alimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dni_ppl VARCHAR(8) NOT NULL,
    fecha DATE NOT NULL,
    
    -- DESAYUNO
    desayuno_hora TIME NULL,
    desayuno_firma VARCHAR(255) NULL,
    desayuno_aclaracion VARCHAR(255) NULL,
    
    -- ALMUERZO
    almuerzo_hora TIME NULL,
    almuerzo_firma VARCHAR(255) NULL,
    almuerzo_aclaracion VARCHAR(255) NULL,
    
    -- CENA
    cena_hora TIME NULL,
    cena_firma VARCHAR(255) NULL,
    cena_aclaracion VARCHAR(255) NULL,
    
    -- Control y auditoría
    id_usuario INT NOT NULL,
    id_sector INT NOT NULL,

    -- Relaciones
    CONSTRAINT fk_recep_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_recep_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    CONSTRAINT fk_recepcion_sector FOREIGN KEY (id_sector) REFERENCES sector(id),
    
    -- Un registro por PPL por día
    UNIQUE KEY unique_ppl_fecha (dni_ppl, fecha)
) COMMENT = 'Planilla de Recepción por PPL - Una fila por día con tres comidas';

-- Índices para mejorar el rendimiento
CREATE INDEX idx_recepcion_fecha ON recepcion_alimentos(fecha);
CREATE INDEX idx_recepcion_dni ON recepcion_alimentos(dni_ppl);
CREATE INDEX idx_recepcion_sector_fecha ON recepcion_alimentos(id_sector, fecha);                 

-- ENTREGA_PRODUCTOS - Acta de Entrega de Productos Especiales
CREATE TABLE entrega_productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    id_sector INT,
    id_pabellon INT,
    dni_ppl VARCHAR(8) NOT NULL,
    tipo_producto ENUM('LECHE_DESLACTOSADA','GALLETAS_ARROZ+PAN_SIN_TACC'),
    cantidad INT,
    fecha_vto DATE,
    firma_ppl TEXT,
    aclaracion TEXT,
    firma_efectivo TEXT,
    id_usuario INT NOT NULL,
    CONSTRAINT fk_entrega_ppl FOREIGN KEY (dni_ppl) REFERENCES ppl(dni),
    CONSTRAINT fk_entrega_sector FOREIGN KEY (id_sector) REFERENCES sector(id),
    CONSTRAINT fk_entrega_pabellon FOREIGN KEY (id_pabellon) REFERENCES pabellon(id) ON DELETE SET NULL,
    CONSTRAINT fk_entrega_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) COMMENT = 'Acta de Entrega de Productos';
ALTER TABLE entrega_productos 
ADD COLUMN cantidad_galletas INT NULL,
ADD COLUMN fecha_vto_galletas DATE NULL,
ADD COLUMN cantidad_pan INT NULL,
ADD COLUMN fecha_vto_pan DATE NULL;

-- Agregar la columna id_pabellon a la tabla distribucion_alimentos
ALTER TABLE distribucion_alimentos 
ADD COLUMN id_pabellon INT NULL;

-- Agregar la clave foránea que referencia a la tabla pabellon
ALTER TABLE distribucion_alimentos 
ADD CONSTRAINT fk_distribucion_pabellon 
FOREIGN KEY (id_pabellon) REFERENCES pabellon(id) 
ON DELETE SET NULL;

-- =====================================================
-- 3. DATOS INICIALES
-- =====================================================

-- Insertar sectores
INSERT IGNORE INTO sector (id, nombre, tipo, capacidad, estado) VALUES
(1, '1', 'ADULTOS', 100, 'ACTIVO'),
(2, '2', 'ADULTOS', 120, 'ACTIVO'),
(3, '3 MENORES', 'MENORES', 50, 'ACTIVO'),
(4, '4', 'ADULTOS', 80, 'ACTIVO'),
(5, 'SANIDAD', 'SANIDAD', 20, 'ACTIVO'),
(6, 'ATILA ANEXO 3', 'ADULTOS', 60, 'ACTIVO'),
(7, 'TANGO', 'ADULTOS', 90, 'ACTIVO');

-- Insertar pabellones
INSERT IGNORE INTO pabellon (id_sector, nombre, capacidad, estado) VALUES
(4, '2', 40, 'ACTIVO'),
(7, '7', 45, 'ACTIVO'),
(7, '8', 45, 'ACTIVO'),
(NULL, '14', 35, 'ACTIVO'),
(NULL, '+15', 35, 'ACTIVO');

-- Insertar PPL
INSERT IGNORE INTO ppl 
(dni, nombre_apellido, fecha_nacimiento, edad, fecha_ingreso, id_sector, id_pabellon, estado_legal, estado) 
VALUES
('30124567', 'Juan Pérez', '1985-04-12', 40, '2023-01-15', 1, 1, 'PENADO', 'ACTIVO'),
('29458741', 'Carlos Gómez', '1990-09-25', 35, '2022-08-10', 2, 2, 'PROCESADO', 'ACTIVO'),
('31789456', 'Luis Martínez', '1978-11-03', 46, '2021-12-05', 3, 3, 'PENADO', 'ACTIVO'),
('33214578', 'Ricardo López', '1995-02-14', 30, '2024-02-20', 1, 1, 'PROCESADO', 'ACTIVO'),
('28965412', 'Mario Fernández', '1982-06-09', 43, '2020-05-11', 2, 2, 'PENADO', 'ACTIVO'),
('32569874', 'Hugo Ramírez', '1988-07-22', 37, '2019-10-01', 3, 3, 'PENADO', 'ACTIVO'),
('31025698', 'Andrés Castillo', '1975-12-18', 49, '2023-04-12', 1, 1, 'PROCESADO', 'ACTIVO'),
('32874596', 'Diego Torres', '1992-05-05', 33, '2024-01-08', 2, 2, 'PENADO', 'ACTIVO'),
('29985471', 'Gabriel Morales', '1980-03-27', 45, '2018-11-19', 3, 3, 'PENADO', 'ACTIVO'),
('31587412', 'Sergio Díaz', '1993-08-30', 32, '2022-07-07', 1, 1, 'PROCESADO', 'ACTIVO');

-- CÓDIGOS DE DIETA
INSERT IGNORE INTO codigos_dieta (id_codigo, codigo, descripcion, categoria) VALUES
(1, 'A', 'Dieta Tipo A - Estándar', 'STANDARD'),
(2, 'B', 'Dieta Tipo B - Astringente', 'STANDARD'),
(3, 'C', 'Dieta Tipo C - Hipercalórica', 'STANDARD'),
(4, 'D', 'Dieta Tipo D - Blanda/Semilíquida', 'STANDARD'),
(5, 'ESP', 'Dieta Especial - Deslactosada', 'ESPECIAL');

-- TIPOS DE DIETA
INSERT IGNORE INTO tipos_dieta (id_dieta, id_codigo, nombre_dieta, descripcion, indicacion_medica, estado, codigo_dieta) VALUES
(1, 1, 'DIABETICA', 'Dieta para diabéticos', 'SI', 'ACTIVA', 'A'),
(2, 1, 'CELIACOS', 'Dieta libre de gluten', 'SI', 'ACTIVA', 'A'),
(3, 2, 'ASTRINGENTE', 'Dieta astringente', 'SI', 'ACTIVA', 'B'),
(4, 1, 'HIPOSODICA', 'Dieta baja en sodio', 'SI', 'ACTIVA', 'A'),
(5, 4, 'BLANDA_SEMILIQUIDA', 'Dieta blanda/semilíquida', 'SI', 'ACTIVA', 'D'),
(6, 1, 'GASTRO_INTESTINAL', 'Dieta gastroenterológica', 'SI', 'ACTIVA', 'A'),
(7, 1, 'VEGETARIANO', 'Dieta vegetariana', 'NO', 'ACTIVA', 'A'),
(8, 1, 'HEPATO_ALERGIA', 'Dieta para hepatopatías/alergias', 'SI', 'ACTIVA', 'A'),
(9, 3, 'HIPERCALORICA', 'Dieta hipercalórica', 'SI', 'ACTIVA', 'C'),
(10, 5, 'DESLACTOSADA', 'Productos sin lactosa', 'SI', 'ACTIVA', 'ESP');

-- USUARIOS
INSERT IGNORE INTO usuarios (nombre_usuario, cargo, area, permisos, fecha_alta, usuario_login, password_hash, estado) 
VALUES ('Lic. Fullana Daniela', 'LIC_NUTRICION', 'NUTRICION', 'ADMIN', '2025-01-01', 'daniela', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ACTIVO');

INSERT IGNORE INTO usuarios 
(nombre_usuario, cargo, area, permisos, fecha_alta, usuario_login, password_hash, estado) VALUES
('Nutricionista 2', 'LIC_NUTRICION', 'NUTRICION', 'NUTRICION', '2025-01-01', 'nutricionista2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ACTIVO'),
('Administrativo 1', 'PERSONAL_ADMIN', 'NUTRICION', 'ADMINISTRATIVO', '2025-01-01', 'admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ACTIVO'),
('Guardia Distribución', 'GUARDIA', 'DISTRIBUCION', 'GUARDIA', '2025-01-01', 'guardia1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ACTIVO');

-- =====================================================
-- 4. ÍNDICES
-- =====================================================

CREATE INDEX idx_ppl_dni ON ppl(dni);
CREATE INDEX idx_atencion_fecha ON atencion_nutricional(fecha);
CREATE INDEX idx_distribucion_fecha ON distribucion_alimentos(fecha);
CREATE INDEX idx_ppl_sector ON ppl(id_sector);
CREATE INDEX idx_ppl_pabellon ON ppl(id_pabellon);
CREATE INDEX idx_pabellon_sector ON pabellon(id_sector);

-- =====================================================
-- 5. VISTAS
-- =====================================================

-- Vista para control de dietas activas
CREATE OR REPLACE VIEW vista_dietas_activas AS
SELECT 
    i.dni,
    i.nombre_apellido,
    i.id_sector,
    s.nombre as nombre_sector,
    i.id_pabellon,
    p.nombre as nombre_pabellon,
    td.nombre_dieta,
    td.descripcion,
    da.fecha_inicio,
    da.fecha_fin,
    u.nombre_usuario as nutricionista_asignado
FROM dietas_asignadas da
JOIN ppl i ON da.dni_ppl = i.dni
JOIN tipos_dieta td ON da.id_dieta = td.id_dieta
JOIN sector s ON i.id_sector = s.id
LEFT JOIN pabellon p ON i.id_pabellon = p.id
JOIN usuarios u ON da.id_usuario = u.id_usuario
WHERE da.estado = 'ACTIVA';

-- Vista para ubicación de internos
CREATE OR REPLACE VIEW vista_ubicacion_ppl AS
SELECT 
    i.dni,
    i.nombre_apellido,
    i.edad,
    i.estado_legal,
    s.nombre as sector,
    s.tipo as tipo_sector,
    p.nombre as pabellon,
    CASE 
        WHEN p.id_sector IS NULL THEN 'INDEPENDIENTE'
        ELSE 'DEPENDIENTE'
    END as tipo_pabellon
FROM ppl i
JOIN sector s ON i.id_sector = s.id
LEFT JOIN pabellon p ON i.id_pabellon = p.id
WHERE i.estado = 'ACTIVO';

-- =====================================================
-- FIN
-- =====================================================