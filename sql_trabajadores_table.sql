CREATE TABLE IF NOT EXISTS peones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    apellido VARCHAR(120) NOT NULL,
    dni VARCHAR(50) NOT NULL,
    fecha_ingreso DATE NOT NULL,
    estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    telefono VARCHAR(30) NULL,
    cuadrilla_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_peones_cuadrilla FOREIGN KEY (cuadrilla_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
