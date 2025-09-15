CREATE DATABASE IF NOT EXISTS cooperativa;

USE cooperativa;

CREATE TABLE IF NOT EXISTS personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    mail VARCHAR(100),
    tel BIGINT,
    CI BIGINT UNIQUE,
    dep VARCHAR(50),
    cuota VARCHAR(50),
    confirmacion BOOLEAN DEFAULT FALSE,
    password VARCHAR(255) DEFAULT NULL,
    horas INT DEFAULT 40,
    plata INT DEFAULT 5000
);

CREATE TABLE IF NOT EXISTS pagos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  monto INT NOT NULL,
  recibo VARCHAR(255) NOT NULL,
  estado ENUM('pendiente','aprobado','rechazado') DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES personas(id)
);

CREATE TABLE IF NOT EXISTS unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direccion VARCHAR(255) NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    usuario_asignado INT DEFAULT NULL
);

SELECT * FROM personas;