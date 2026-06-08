-- Archivo que define la estructura de la base de datos, qué tablas existen,
-- qué columnas contienen, sus tipos, restricciones y relaciones.
-- Aquí tendremos 'autonomos', clientes', 'facturas', lineas_factura'

-- Tabla de autónomos (usuarios del sistema)
CREATE TABLE IF NOT EXISTS autonomos (
    id                  BIGSERIAL PRIMARY KEY,
    email               VARCHAR(255)   NOT NULL UNIQUE,
    password            VARCHAR(255)   NOT NULL,              -- hash BCrypt, nunca texto plano
    nombre              VARCHAR(255)   NOT NULL,          
    apellidos           VARCHAR(255)   NOT NULL,
    nif                 VARCHAR(9)     NOT NULL UNIQUE,
    direccion           TEXT,
    codigo_postal       VARCHAR(10),
    ciudad              VARCHAR(100),
    provincia           VARCHAR(100),
    telefono            VARCHAR(20),
    creado_en           TIMESTAMP       NOT NULL DEFAULT NOW()
);

-- Tabla de clientes (multitenencia: cada cliente pertenece a un autónomo)
CREATE TABLE IF NOT EXISTS clientes (
    id                  BIGSERIAL                PRIMARY KEY,
    autonomo_id         BIGINT              NOT NULL REFERENCES autonomos(id) ON DELETE CASCADE,
    nombre              VARCHAR(255)        NOT NULL,
    nif                 VARCHAR(15)         NOT NULL,              -- 15 admite NIF fiscales extranjeros
    email               VARCHAR(255),
    telefono            VARCHAR(20),
    direccion           TEXT,
    codigo_postal       VARCHAR(10),
    ciudad              VARCHAR(100),
    provincia           VARCHAR(100),
    pais                VARCHAR(100)        NOT NULL DEFAULT 'España',
    creado_en           TIMESTAMP           NOT NULL DEFAULT NOW()
);

-- Tabla de facturas
CREATE TABLE IF NOT EXISTS facturas (
    id                      BIGSERIAL                PRIMARY KEY,
    autonomo_id             BIGINT          NOT NULL REFERENCES autonomos(id) ON DELETE CASCADE,
    cliente_id              BIGINT          NOT NULL REFERENCES clientes(id),
    numero_factura          VARCHAR(20)     NOT NULL,               -- FORMATO YYYY-NNN, ej: 2023_001
    fecha_emision           DATE            NOT NULL,
    fecha_vencimiento       DATE,
    porcentaje_iva          NUMERIC(5,2)    NOT NULL DEFAULT 21.00,
    porcentaje_irpf         NUMERIC(5,2)    NOT NULL DEFAULT 15.00,
    base_imponible          NUMERIC(12,2),
    cuota_iva               NUMERIC(12,2),
    cuota_irpf              NUMERIC(12,2),
    total                   NUMERIC(12,2),
    -- Estado como VARCHAR con CHECK: más explícito que un índice entero
    estado                  VARCHAR(20)     NOT NULL DEFAULT 'BORRADOR' CHECK (estado IN ('BORRADOR','EMITIDA','PAGADA','VENCIDA','CANCELADA')),
    notas                   TEXT,
    creada_en               TIMESTAMP       NOT NULL DEFAULT NOW(),
    -- Unicidad POR autónomo (no global): dos autónomos pueden tener su propio "2023-001"
    UNIQUE (autonomo_id, numero_factura)
);

-- Tabla de líneas de factura
CREATE TABLE IF NOT EXISTS lineas_factura (
    id                      BIGSERIAL                PRIMARY KEY,
    factura_id              BIGINT          NOT NULL REFERENCES facturas(id) ON DELETE CASCADE,
    concepto                VARCHAR(500)    NOT NULL,
    cantidad                NUMERIC(10,2)   NOT NULL,
    precio_unitario         NUMERIC(10,2)   NOT NULL,
    importe                 NUMERIC(12,2)   NOT NULL                -- cantidad x precio_unitari
);

-- Índices para las queries más frecuentes (multitenencia)
CREATE INDEX IF NOT EXISTS idx_clientes_autonomo ON clientes(autonomo_id);
CREATE INDEX IF NOT EXISTS idx_facturas_autonomo ON facturas(autonomo_id);
CREATE INDEX IF NOT EXISTS idx_lineas_factura    ON lineas_factura(factura_id);