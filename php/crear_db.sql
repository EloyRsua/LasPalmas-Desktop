-- Script de creación de la base de datos y sus tablas
-- Base de datos: reservas
-- SGBD: MySQL / MariaDB
-- Autor: Eloy Rubio Suárez

CREATE DATABASE IF NOT EXISTS reservas CHARACTER SET utf8 COLLATE utf8_general_ci;
USE reservas;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Agencias Gestoras
CREATE TABLE IF NOT EXISTS agencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20)
) ENGINE=InnoDB;

-- 3. Tabla de Tipos de Recursos Turísticos
CREATE TABLE IF NOT EXISTS tipos_recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_tipo VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT
) ENGINE=InnoDB;

-- 4. Tabla de Recursos Turísticos
CREATE TABLE IF NOT EXISTS recursos_turisticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    id_tipo INT NOT NULL,
    id_agencia INT NOT NULL,
    capacidad_maxima INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_tipo) REFERENCES tipos_recursos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_agencia) REFERENCES agencias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabla de Reservas
CREATE TABLE IF NOT EXISTS reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_recurso INT NOT NULL,
    plazas_reservadas INT NOT NULL,
    fecha_reserva TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado_reserva VARCHAR(50) DEFAULT 'confirmada',
    total_pagar DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_recurso) REFERENCES recursos_turisticos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
