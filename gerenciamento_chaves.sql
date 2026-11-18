-- Inicialização do banco gerenciamento_chaves para MySQL/MariaDB (XAMPP).
-- Cria o banco se necessário; importe este arquivo uma vez em uma instalação nova.
-- Nenhuma conta de usuário ou credencial é inserida por este arquivo.

CREATE DATABASE IF NOT EXISTS `gerenciamento_chaves`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `gerenciamento_chaves`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET time_zone = "+00:00";

START TRANSACTION;

CREATE TABLE `salas` (
  `id_sala` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `bloco` varchar(50) NOT NULL,
  PRIMARY KEY (`id_sala`),
  UNIQUE KEY `uq_salas_numero_bloco` (`numero`, `bloco`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `salas` (`id_sala`, `nome`, `numero`, `bloco`) VALUES
(1, 'Sala 01', '01', 'A'),
(2, 'Sala 02', '02', 'A'),
(3, 'Laboratório', '03', 'B'),
(4, 'Inovaê', '04', 'B');

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cargo` enum('Gerente','Porteiro','Professor','Outro') NOT NULL,
  `cpf` char(11) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `chaves` (
  `id_chave` int(11) NOT NULL AUTO_INCREMENT,
  `id_sala` int(11) NOT NULL,
  `numero_chave` varchar(30) NOT NULL,
  `status_chaves` enum('Disponivel','Emprestada','Atrasada') NOT NULL DEFAULT 'Disponivel',
  `responsavel` int(11) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_chave`),
  UNIQUE KEY `numero_chave` (`numero_chave`),
  KEY `id_sala` (`id_sala`),
  KEY `responsavel` (`responsavel`),
  CONSTRAINT `chaves_ibfk_1` FOREIGN KEY (`id_sala`) REFERENCES `salas` (`id_sala`),
  CONSTRAINT `chaves_ibfk_2` FOREIGN KEY (`responsavel`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `chaves` (`id_chave`, `id_sala`, `numero_chave`, `status_chaves`, `responsavel`, `ativo`) VALUES
(1, 1, '001', 'Disponivel', NULL, 1),
(2, 2, '002', 'Disponivel', NULL, 1),
(3, 3, '003', 'Disponivel', NULL, 1),
(4, 1, '004', 'Disponivel', NULL, 1),
(5, 4, '005', 'Disponivel', NULL, 1);

CREATE TABLE `movimentacoes` (
  `id_movimentacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_chave` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_porteiro` int(11) NOT NULL,
  `data_retirada` datetime NOT NULL DEFAULT current_timestamp(),
  `data_prevista` datetime DEFAULT NULL,
  `data_devolucao` datetime DEFAULT NULL,
  `observacao` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id_movimentacao`),
  KEY `id_chave` (`id_chave`),
  KEY `id_usuario` (`id_usuario`),
  KEY `id_porteiro` (`id_porteiro`),
  CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`id_chave`) REFERENCES `chaves` (`id_chave`),
  CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`id_porteiro`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `observacoes` (
  `id_observacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_porteiro` int(11) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `texto` text NOT NULL,
  PRIMARY KEY (`id_observacao`),
  KEY `id_porteiro` (`id_porteiro`),
  CONSTRAINT `observacoes_ibfk_1` FOREIGN KEY (`id_porteiro`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
