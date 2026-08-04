-- ======================================================
-- Script SQL para o Sistema de Login em PHP
-- Compatível com MySQL / MariaDB e SQLite
-- ======================================================

-- Para MySQL / MariaDB (ex: phpMyAdmin ou MySQL Workbench):
-- CREATE DATABASE IF NOT EXISTS sistema_login DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sistema_login;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
