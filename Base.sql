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
    plata INT DEFAULT 5000,
    is_admin BOOLEAN DEFAULT FALSE
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

CREATE TABLE IF NOT EXISTS solicitudes_unidades (
  id INT AUTO_INCREMENT PRIMARY KEY,
  unidad_id INT NOT NULL,
  user_id INT NOT NULL,
  estado ENUM('pendiente','aprobada','rechazada') DEFAULT 'pendiente',
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (unidad_id) REFERENCES unidades(id),
  FOREIGN KEY (user_id) REFERENCES personas(id)
);

CREATE TABLE IF NOT EXISTS news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO unidades (direccion, disponible, usuario_asignado) VALUES
  ('Av. Uruguay 1234, apto 1', 1, NULL),
  ('Gral. Flores 2100, apto 2', 1, NULL),
  ('Bulevar Artigas 455, apto 3', 1, NULL),
  ('Rambla República 88, apto 4', 1, NULL),
  ('Calle Colón 300, apto 5', 1, NULL);


SELECT * FROM unidades;