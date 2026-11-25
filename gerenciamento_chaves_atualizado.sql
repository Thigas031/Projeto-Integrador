-- Inicialização do banco gerenciamento_chaves para MySQL/MariaDB (XAMPP).
-- Cria o banco se necessário; importe este arquivo uma vez em uma instalação nova.
-- Inclui contas fictícias para demonstração; não use essas contas em produção.

CREATE DATABASE IF NOT EXISTS `gerenciamento_chaves`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `gerenciamento_chaves`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;
SET time_zone = "+00:00";

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `salas` (
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
(4, 'Inovaê', '04', 'B')
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `numero` = VALUES(`numero`),
  `bloco` = VALUES(`bloco`);

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `cargo` enum('Gerente','Porteiro','Professor','Coordenacao','Funcionario') NOT NULL,
  `cpf` char(11) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Uma conta de teste para cada cargo que pode ser cadastrado.
-- A senha inicial é formada pelos primeiros 4 dígitos do CPF.
INSERT INTO `usuarios`
  (`id_usuario`, `nome`, `cargo`, `cpf`, `telefone`, `email`, `senha`, `ativo`)
VALUES
  (23, 'Gerente de Teste', 'Gerente', '11111111111', NULL, 'gerente.teste@example.test', '$2y$10$D.6SuQ1gap5j7UgBrfV4o.2KpZg36jSQA25ym4Um8UjYUpULSrkTC', 1),
  (24, 'Porteiro de Teste', 'Porteiro', '22222222222', NULL, 'porteiro.teste@example.test', '$2y$10$gj/5HR5GjtfxRne3Utt8kOsQn3cN.XymQOQ7K4y5h8w3kD2RBh/dq', 1),
  (30, 'Professor de Teste', 'Professor', '33333333333', NULL, 'professor.teste@example.test', '$2y$10$f54.xWHRRWZnjJo0kzw8humFknAL0QHQ1wX0rb3ewyFfQjCVSQQeC', 1),
  (33, 'Coordenação de Teste', 'Coordenacao', '44444444444', NULL, 'coordenacao.teste@example.test', '$2y$10$dcwZ1ID8iNUKUxvloKhZOevKlwTt8qps98UKvzzQnU6K7owFcQW22', 1),
  (34, 'Funcionário de Teste', 'Funcionario', '55555555555', NULL, 'funcionario.teste@example.test', '$2y$10$gf8k6Y.pC3sX561weWSmJOXxJqsd1abft6SS/d5/LYiRqaiRxvDum', 1)
ON DUPLICATE KEY UPDATE
  `nome` = VALUES(`nome`),
  `cargo` = VALUES(`cargo`),
  `cpf` = VALUES(`cpf`),
  `telefone` = VALUES(`telefone`),
  `email` = VALUES(`email`),
  `senha` = VALUES(`senha`),
  `ativo` = VALUES(`ativo`);

CREATE TABLE IF NOT EXISTS `chaves` (
  `id_chave` int(11) NOT NULL AUTO_INCREMENT,
  `id_sala` int(11) NOT NULL,
  `numero_chave` varchar(30) NOT NULL,
  `nome_chave` varchar(100) DEFAULT NULL,
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
(5, 4, '005', 'Disponivel', NULL, 1)
ON DUPLICATE KEY UPDATE
  `id_sala` = VALUES(`id_sala`),
  `numero_chave` = VALUES(`numero_chave`),
  `status_chaves` = VALUES(`status_chaves`),
  `responsavel` = VALUES(`responsavel`),
  `ativo` = VALUES(`ativo`);

CREATE TABLE IF NOT EXISTS `movimentacoes` (
  `id_movimentacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_chave` int(11) NOT NULL,
  `id_usuario` int(11) DEFAULT NULL,
  `nome_retirante` varchar(100) DEFAULT NULL,
  `cargo_retirante` varchar(100) DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `observacoes` (
  `id_observacao` int(11) NOT NULL AUTO_INCREMENT,
  `id_porteiro` int(11) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `texto` text NOT NULL,
  PRIMARY KEY (`id_observacao`),
  KEY `id_porteiro` (`id_porteiro`),
  CONSTRAINT `observacoes_ibfk_1` FOREIGN KEY (`id_porteiro`) REFERENCES `usuarios` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
