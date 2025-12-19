-- =====================================================
-- SCRIPT DE BASE DE DATOS - SISTEMA DE CRÉDITOS FARMACIA
-- SQL Server
-- Fecha: 2025-12-18
-- =====================================================

-- Crear base de datos
USE master;
GO

IF EXISTS (SELECT name FROM sys.databases WHERE name = 'FarmaciaCreditos')
BEGIN
    ALTER DATABASE FarmaciaCreditos SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE FarmaciaCreditos;
END
GO

CREATE DATABASE FarmaciaCreditos;
GO

USE FarmaciaCreditos;
GO

-- =====================================================
-- TABLA: Clientes
-- =====================================================

CREATE TABLE Clientes (
    id_cliente INT IDENTITY(1,1) PRIMARY KEY,
    tipo_cliente VARCHAR(20) NOT NULL CHECK (tipo_cliente IN ('Natural', 'Jurídico')),
    nombre VARCHAR(200) NOT NULL,
    nit VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion VARCHAR(300),

    -- Campos específicos para clientes jurídicos
    razon_social VARCHAR(200),
    representante_legal VARCHAR(200),

    -- Evaluación de riesgo
    riesgo VARCHAR(10) CHECK (riesgo IN ('Bajo', 'Medio', 'Alto')),

    -- Campos de auditoría
    fecha_registro DATETIME DEFAULT GETDATE(),
    usuario_registro VARCHAR(50),
    fecha_actualizacion DATETIME,
    usuario_actualizacion VARCHAR(50),
    estado VARCHAR(20) DEFAULT 'Activo' CHECK (estado IN ('Activo', 'Inactivo', 'Suspendido'))
);

-- Índices
CREATE INDEX idx_clientes_tipo ON Clientes(tipo_cliente);
CREATE INDEX idx_clientes_nit ON Clientes(nit);
CREATE INDEX idx_clientes_estado ON Clientes(estado);

-- =====================================================
-- TABLA: Créditos
-- =====================================================

CREATE TABLE Creditos (
    id_credito INT IDENTITY(1,1) PRIMARY KEY,
    id_cliente INT NOT NULL,

    -- Información del crédito
    monto_capital DECIMAL(18,2) NOT NULL CHECK (monto_capital > 0),
    tasa_interes DECIMAL(5,2) NOT NULL CHECK (tasa_interes >= 0),
    plazo_meses INT NOT NULL CHECK (plazo_meses > 0),

    -- Fechas
    fecha_desembolso DATE NOT NULL,
    fecha_vencimiento_final AS DATEADD(MONTH, plazo_meses, fecha_desembolso),

    -- Estado
    estado VARCHAR(20) DEFAULT 'Activo' CHECK (estado IN ('Activo', 'Cancelado', 'Moroso', 'Castigado')),

    -- Observaciones
    observaciones TEXT,

    -- Campos de auditoría
    fecha_creacion DATETIME DEFAULT GETDATE(),
    usuario_creacion VARCHAR(50),
    fecha_actualizacion DATETIME,
    usuario_actualizacion VARCHAR(50),

    -- Foreign Keys
    CONSTRAINT fk_creditos_cliente FOREIGN KEY (id_cliente)
        REFERENCES Clientes(id_cliente)
);

-- Índices
CREATE INDEX idx_creditos_cliente ON Creditos(id_cliente);
CREATE INDEX idx_creditos_estado ON Creditos(estado);
CREATE INDEX idx_creditos_fecha ON Creditos(fecha_desembolso);

-- =====================================================
-- TABLA: Cuotas
-- =====================================================

CREATE TABLE Cuotas (
    id_cuota INT IDENTITY(1,1) PRIMARY KEY,
    id_credito INT NOT NULL,
    id_cliente INT NOT NULL,

    -- Información de la cuota
    numero_cuota INT NOT NULL CHECK (numero_cuota > 0),
    monto_capital DECIMAL(18,2) NOT NULL CHECK (monto_capital > 0),
    interes DECIMAL(18,2) NOT NULL CHECK (interes >= 0),
    monto_total AS (monto_capital + interes) PERSISTED,

    -- Fechas
    fecha_programada DATE NOT NULL,
    fecha_pago DATE,

    -- Estado y mora
    estado VARCHAR(20) DEFAULT 'Pendiente' CHECK (estado IN ('Pendiente', 'Pagada', 'Vencida', 'Parcial')),
    dias_mora AS (
        CASE
            WHEN estado = 'Vencida' THEN DATEDIFF(DAY, fecha_programada, GETDATE())
            WHEN estado = 'Pagada' AND fecha_pago > fecha_programada THEN DATEDIFF(DAY, fecha_programada, fecha_pago)
            ELSE 0
        END
    ) PERSISTED,

    -- Montos de pago
    monto_pagado DECIMAL(18,2) DEFAULT 0,
    saldo_pendiente AS (monto_capital + interes - ISNULL(monto_pagado, 0)) PERSISTED,

    -- Observaciones
    observaciones TEXT,

    -- Campos de auditoría
    fecha_creacion DATETIME DEFAULT GETDATE(),
    usuario_creacion VARCHAR(50),
    fecha_actualizacion DATETIME,
    usuario_actualizacion VARCHAR(50),

    -- Foreign Keys
    CONSTRAINT fk_cuotas_credito FOREIGN KEY (id_credito)
        REFERENCES Creditos(id_credito),
    CONSTRAINT fk_cuotas_cliente FOREIGN KEY (id_cliente)
        REFERENCES Clientes(id_cliente),

    -- Constraint única
    CONSTRAINT uq_cuota_credito UNIQUE (id_credito, numero_cuota)
);

-- Índices
CREATE INDEX idx_cuotas_credito ON Cuotas(id_credito);
CREATE INDEX idx_cuotas_cliente ON Cuotas(id_cliente);
CREATE INDEX idx_cuotas_estado ON Cuotas(estado);
CREATE INDEX idx_cuotas_fecha_programada ON Cuotas(fecha_programada);
CREATE INDEX idx_cuotas_fecha_pago ON Cuotas(fecha_pago);

-- =====================================================
-- TABLA: Pagos (Registro de transacciones)
-- =====================================================

CREATE TABLE Pagos (
    id_pago INT IDENTITY(1,1) PRIMARY KEY,
    id_cuota INT NOT NULL,
    id_credito INT NOT NULL,

    -- Información del pago
    monto_pago DECIMAL(18,2) NOT NULL CHECK (monto_pago > 0),
    fecha_pago DATETIME DEFAULT GETDATE(),
    metodo_pago VARCHAR(50) CHECK (metodo_pago IN ('Efectivo', 'Tarjeta', 'Transferencia', 'Cheque', 'Otro')),

    -- Referencias
    numero_referencia VARCHAR(50),
    comprobante VARCHAR(100),

    -- Observaciones
    observaciones TEXT,

    -- Campos de auditoría
    fecha_registro DATETIME DEFAULT GETDATE(),
    usuario_registro VARCHAR(50),

    -- Foreign Keys
    CONSTRAINT fk_pagos_cuota FOREIGN KEY (id_cuota)
        REFERENCES Cuotas(id_cuota),
    CONSTRAINT fk_pagos_credito FOREIGN KEY (id_credito)
        REFERENCES Creditos(id_credito)
);

-- Índices
CREATE INDEX idx_pagos_cuota ON Pagos(id_cuota);
CREATE INDEX idx_pagos_credito ON Pagos(id_credito);
CREATE INDEX idx_pagos_fecha ON Pagos(fecha_pago);

-- =====================================================
-- VISTAS ÚTILES
-- =====================================================

-- Vista: Resumen de Créditos por Cliente
GO
CREATE VIEW vw_ResumenCreditosCliente AS
SELECT
    c.id_cliente,
    c.nombre,
    c.tipo_cliente,
    c.nit,
    c.riesgo,
    COUNT(cr.id_credito) AS total_creditos,
    SUM(CASE WHEN cr.estado = 'Activo' THEN 1 ELSE 0 END) AS creditos_activos,
    SUM(CASE WHEN cr.estado = 'Moroso' THEN 1 ELSE 0 END) AS creditos_morosos,
    SUM(cr.monto_capital) AS monto_total_creditos,
    SUM(CASE WHEN cr.estado = 'Activo' THEN cr.monto_capital ELSE 0 END) AS monto_creditos_activos
FROM
    Clientes c
    LEFT JOIN Creditos cr ON c.id_cliente = cr.id_cliente
GROUP BY
    c.id_cliente, c.nombre, c.tipo_cliente, c.nit, c.riesgo;
GO

-- Vista: Deuda Impaga por Cliente
GO
CREATE VIEW vw_DeudaImpagaCliente AS
SELECT
    c.id_cliente,
    c.nombre,
    c.tipo_cliente,
    c.nit,
    COUNT(cu.id_cuota) AS cuotas_vencidas,
    SUM(cu.monto_total) AS deuda_total,
    MAX(cu.dias_mora) AS max_dias_mora,
    AVG(CAST(cu.dias_mora AS FLOAT)) AS promedio_dias_mora
FROM
    Clientes c
    INNER JOIN Cuotas cu ON c.id_cliente = cu.id_cliente
WHERE
    cu.estado = 'Vencida'
GROUP BY
    c.id_cliente, c.nombre, c.tipo_cliente, c.nit;
GO

-- Vista: Cuotas Próximas a Vencer (30 días)
GO
CREATE VIEW vw_CuotasProximasVencer AS
SELECT
    cu.id_cuota,
    cu.id_credito,
    cl.id_cliente,
    cl.nombre AS nombre_cliente,
    cl.tipo_cliente,
    cu.numero_cuota,
    cu.monto_total,
    cu.fecha_programada,
    DATEDIFF(DAY, GETDATE(), cu.fecha_programada) AS dias_hasta_vencimiento,
    cr.estado AS estado_credito
FROM
    Cuotas cu
    INNER JOIN Creditos cr ON cu.id_credito = cr.id_credito
    INNER JOIN Clientes cl ON cu.id_cliente = cl.id_cliente
WHERE
    cu.estado = 'Pendiente'
    AND cu.fecha_programada BETWEEN GETDATE() AND DATEADD(DAY, 30, GETDATE());
GO

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- SP: Crear nuevo crédito con cuotas
GO
CREATE PROCEDURE sp_CrearCredito
    @id_cliente INT,
    @monto_capital DECIMAL(18,2),
    @tasa_interes DECIMAL(5,2),
    @plazo_meses INT,
    @fecha_desembolso DATE,
    @usuario VARCHAR(50),
    @id_credito_nuevo INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRANSACTION;

    BEGIN TRY
        -- Insertar crédito
        INSERT INTO Creditos (id_cliente, monto_capital, tasa_interes, plazo_meses,
                             fecha_desembolso, estado, usuario_creacion)
        VALUES (@id_cliente, @monto_capital, @tasa_interes, @plazo_meses,
                @fecha_desembolso, 'Activo', @usuario);

        SET @id_credito_nuevo = SCOPE_IDENTITY();

        -- Generar cuotas
        DECLARE @contador INT = 1;
        DECLARE @monto_cuota DECIMAL(18,2) = @monto_capital / @plazo_meses;
        DECLARE @interes_mensual DECIMAL(18,2) = (@monto_capital * @tasa_interes / 100) / 12;
        DECLARE @fecha_cuota DATE;

        WHILE @contador <= @plazo_meses
        BEGIN
            SET @fecha_cuota = DATEADD(MONTH, @contador, @fecha_desembolso);

            INSERT INTO Cuotas (id_credito, id_cliente, numero_cuota, monto_capital,
                               interes, fecha_programada, estado, usuario_creacion)
            VALUES (@id_credito_nuevo, @id_cliente, @contador, @monto_cuota,
                    @interes_mensual, @fecha_cuota, 'Pendiente', @usuario);

            SET @contador = @contador + 1;
        END

        COMMIT TRANSACTION;
        RETURN 0;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- SP: Registrar pago de cuota
GO
CREATE PROCEDURE sp_RegistrarPago
    @id_cuota INT,
    @monto_pago DECIMAL(18,2),
    @metodo_pago VARCHAR(50),
    @numero_referencia VARCHAR(50) = NULL,
    @usuario VARCHAR(50)
AS
BEGIN
    SET NOCOUNT ON;
    BEGIN TRANSACTION;

    BEGIN TRY
        DECLARE @id_credito INT;
        DECLARE @saldo_pendiente DECIMAL(18,2);

        -- Obtener información de la cuota
        SELECT @id_credito = id_credito, @saldo_pendiente = saldo_pendiente
        FROM Cuotas
        WHERE id_cuota = @id_cuota;

        -- Validar que el monto no exceda el saldo
        IF @monto_pago > @saldo_pendiente
        BEGIN
            RAISERROR('El monto del pago excede el saldo pendiente', 16, 1);
            RETURN -1;
        END

        -- Registrar el pago
        INSERT INTO Pagos (id_cuota, id_credito, monto_pago, metodo_pago,
                          numero_referencia, usuario_registro)
        VALUES (@id_cuota, @id_credito, @monto_pago, @metodo_pago,
                @numero_referencia, @usuario);

        -- Actualizar el monto pagado en la cuota
        UPDATE Cuotas
        SET monto_pagado = ISNULL(monto_pagado, 0) + @monto_pago,
            fecha_pago = CASE
                WHEN (ISNULL(monto_pagado, 0) + @monto_pago) >= monto_total THEN GETDATE()
                ELSE fecha_pago
            END,
            estado = CASE
                WHEN (ISNULL(monto_pagado, 0) + @monto_pago) >= monto_total THEN 'Pagada'
                WHEN (ISNULL(monto_pagado, 0) + @monto_pago) > 0 THEN 'Parcial'
                ELSE estado
            END,
            usuario_actualizacion = @usuario,
            fecha_actualizacion = GETDATE()
        WHERE id_cuota = @id_cuota;

        -- Actualizar estado del crédito si todas las cuotas están pagadas
        IF NOT EXISTS (SELECT 1 FROM Cuotas WHERE id_credito = @id_credito AND estado != 'Pagada')
        BEGIN
            UPDATE Creditos
            SET estado = 'Cancelado',
                usuario_actualizacion = @usuario,
                fecha_actualizacion = GETDATE()
            WHERE id_credito = @id_credito;
        END

        COMMIT TRANSACTION;
        RETURN 0;
    END TRY
    BEGIN CATCH
        ROLLBACK TRANSACTION;
        THROW;
    END CATCH
END;
GO

-- SP: Actualizar estado de cuotas vencidas
GO
CREATE PROCEDURE sp_ActualizarCuotasVencidas
AS
BEGIN
    SET NOCOUNT ON;

    UPDATE Cuotas
    SET estado = 'Vencida'
    WHERE fecha_programada < GETDATE()
      AND estado = 'Pendiente';

    -- Actualizar estado de créditos con cuotas vencidas
    UPDATE Creditos
    SET estado = 'Moroso'
    WHERE id_credito IN (
        SELECT DISTINCT id_credito
        FROM Cuotas
        WHERE estado = 'Vencida'
    )
    AND estado = 'Activo';

    RETURN @@ROWCOUNT;
END;
GO

-- =====================================================
-- FUNCIONES ÚTILES
-- =====================================================

-- Función: Calcular tasa de morosidad
GO
CREATE FUNCTION fn_TasaMorosidad()
RETURNS DECIMAL(5,2)
AS
BEGIN
    DECLARE @total_creditos DECIMAL(18,2);
    DECLARE @deuda_impaga DECIMAL(18,2);
    DECLARE @tasa DECIMAL(5,2);

    SELECT @total_creditos = SUM(monto_capital)
    FROM Creditos
    WHERE estado IN ('Activo', 'Moroso');

    SELECT @deuda_impaga = SUM(monto_total)
    FROM Cuotas
    WHERE estado = 'Vencida';

    IF @total_creditos > 0
        SET @tasa = (@deuda_impaga / @total_creditos) * 100;
    ELSE
        SET @tasa = 0;

    RETURN ISNULL(@tasa, 0);
END;
GO

-- =====================================================
-- JOB: Actualización automática (crear manualmente en SQL Agent)
-- =====================================================

-- Este script debe ejecutarse diariamente para actualizar estados
-- EXEC sp_ActualizarCuotasVencidas;

-- =====================================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- =====================================================

-- Insertar clientes de ejemplo
-- Clientes Naturales (5)
INSERT INTO Clientes (tipo_cliente, nombre, nit, telefono, email, direccion, riesgo) VALUES
('Natural', 'Juan Pérez García', '12345001', '555-1001', 'juan.perez@email.com', 'Av. Principal #123, Ciudad', 'Bajo'),
('Natural', 'María López Sánchez', '12345002', '555-1002', 'maria.lopez@email.com', 'Calle 45 #67-89, Centro', 'Bajo'),
('Natural', 'Carlos Rodríguez Méndez', '12345003', '555-1003', 'carlos.rodriguez@email.com', 'Carrera 12 #34-56, Norte', 'Medio'),
('Natural', 'Ana Martínez Torres', '12345004', '555-1004', 'ana.martinez@email.com', 'Av. Libertador #78-90, Sur', 'Bajo'),
('Natural', 'Luis Gómez Ramírez', '12345005', '555-1005', 'luis.gomez@email.com', 'Calle 23 #45-67, Este', 'Alto');

-- Clientes Jurídicos (5)
INSERT INTO Clientes (tipo_cliente, nombre, nit, telefono, email, direccion, razon_social, representante_legal, riesgo) VALUES
('Jurídico', 'Farmacia Central S.A.', '987654001', '555-2001', 'contacto@farmaciacentral.com', 'Zona Industrial #100, Ciudad', 'Farmacia Central Sociedad Anónima', 'Pedro González', 'Bajo'),
('Jurídico', 'Distribuidora Médica Ltda.', '987654002', '555-2002', 'ventas@distmedica.com', 'Av. Comercial #200, Centro', 'Distribuidora Médica Limitada', 'Ana Ramírez', 'Medio'),
('Jurídico', 'Clínica San Rafael', '987654003', '555-2003', 'admin@clinicasanrafael.com', 'Calle Salud #300, Norte', 'Clínica San Rafael S.A.', 'Dr. Carlos Méndez', 'Bajo'),
('Jurídico', 'Hospital Metropolitano', '987654004', '555-2004', 'compras@hospitalmetro.com', 'Av. Hospital #400, Sur', 'Hospital Metropolitano S.A.', 'Dra. María Torres', 'Bajo'),
('Jurídico', 'Laboratorios Unidos S.A.', '987654005', '555-2005', 'info@labunidos.com', 'Parque Industrial #500, Este', 'Laboratorios Unidos Sociedad Anónima', 'Ing. Luis Vega', 'Medio');
GO

PRINT 'Base de datos FarmaciaCreditos creada exitosamente';
PRINT 'Tablas: Clientes, Creditos, Cuotas, Pagos';
PRINT 'Vistas: vw_ResumenCreditosCliente, vw_DeudaImpagaCliente, vw_CuotasProximasVencer';
PRINT 'Stored Procedures: sp_CrearCredito, sp_RegistrarPago, sp_ActualizarCuotasVencidas';
PRINT 'Funciones: fn_TasaMorosidad';
GO
