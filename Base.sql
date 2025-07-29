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
    password VARCHAR(255) DEFAULT NULL
);

CREATE TABLE unidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direccion VARCHAR(255) NOT NULL,
    disponible BOOLEAN DEFAULT TRUE,
    usuario_asignado INT DEFAULT NULL,
    FOREIGN KEY (usuario_asignado) REFERENCES personas(CI)
);

SELECT * FROM personas;


