CREATE TABLE IF NOT EXISTS trabajadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    documento VARCHAR(50) NOT NULL,
    rol VARCHAR(30) NOT NULL,
    finca_id INT NULL,
    finca_nombre VARCHAR(150) NULL,
    especialidad VARCHAR(100) NULL,
    inicio_actividades DATE NOT NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabajadores_fincas FOREIGN KEY (finca_id) REFERENCES fincas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
