-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 21/10/2026 às 17:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gerenciamento_chaves`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `chaves`
--

CREATE TABLE `chaves` (
  `id_chave` int(11) NOT NULL,
  `id_sala` int(11) NOT NULL,
  `numero_chave` varchar(30) NOT NULL,
  `status` enum('Disponivel','Emprestada','Atrasada') NOT NULL DEFAULT 'Disponivel',
  `responsavel` int(11) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `chaves`
--

INSERT INTO `chaves` (`id_chave`, `id_sala`, `numero_chave`, `status`, `responsavel`, `ativo`) VALUES
(1, 1, '001', 'Disponivel', NULL, 1),
(2, 2, '002', 'Disponivel', NULL, 1),
(3, 3, '003', 'Disponivel', NULL, 1),
(4, 1, '004', 'Disponivel', NULL, 1),
(5, 4, '005', 'Disponivel', NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes`
--

CREATE TABLE `movimentacoes` (
  `id_movimentacao` int(11) NOT NULL,
  `id_chave` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `id_porteiro` int(11) NOT NULL,
  `data_retirada` datetime NOT NULL DEFAULT current_timestamp(),
  `data_prevista` datetime DEFAULT NULL,
  `data_devolucao` datetime DEFAULT NULL,
  `observacao` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `observacoes`
--

CREATE TABLE `observacoes` (
  `id_observacao` int(11) NOT NULL,
  `id_porteiro` int(11) NOT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `texto` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `salas`
--

CREATE TABLE `salas` (
  `id_sala` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `bloco` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `salas`
--

INSERT INTO `salas` (`id_sala`, `nome`, `numero`, `bloco`) VALUES
(1, 'Sala 01', '01', 'A'),
(2, 'Sala 02', '02', 'A'),
(3, 'Laboratório', '03', 'B'),
(4, 'Inovaê', '04', 'B');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id_usuario` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cargo` enum('Gerente','Porteiro','Professor','Outro') NOT NULL,
  `cpf` char(11) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `chaves`
--
ALTER TABLE `chaves`
  ADD PRIMARY KEY (`id_chave`),
  ADD UNIQUE KEY `numero_chave` (`numero_chave`),
  ADD KEY `id_sala` (`id_sala`),
  ADD KEY `responsavel` (`responsavel`);

--
-- Índices de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD PRIMARY KEY (`id_movimentacao`),
  ADD KEY `id_chave` (`id_chave`),
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `id_porteiro` (`id_porteiro`);

--
-- Índices de tabela `observacoes`
--
ALTER TABLE `observacoes`
  ADD PRIMARY KEY (`id_observacao`),
  ADD KEY `id_porteiro` (`id_porteiro`);

--
-- Índices de tabela `salas`
--
ALTER TABLE `salas`
  ADD PRIMARY KEY (`id_sala`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `chaves`
--
ALTER TABLE `chaves`
  MODIFY `id_chave` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `movimentacoes`
--
ALTER TABLE `movimentacoes`
  MODIFY `id_movimentacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `observacoes`
--
ALTER TABLE `observacoes`
  MODIFY `id_observacao` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `salas`
--
ALTER TABLE `salas`
  MODIFY `id_sala` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `chaves`
--
ALTER TABLE `chaves`
  ADD CONSTRAINT `chaves_ibfk_1` FOREIGN KEY (`id_sala`) REFERENCES `salas` (`id_sala`),
  ADD CONSTRAINT `chaves_ibfk_2` FOREIGN KEY (`responsavel`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `movimentacoes`
--
ALTER TABLE `movimentacoes`
  ADD CONSTRAINT `movimentacoes_ibfk_1` FOREIGN KEY (`id_chave`) REFERENCES `chaves` (`id_chave`),
  ADD CONSTRAINT `movimentacoes_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`),
  ADD CONSTRAINT `movimentacoes_ibfk_3` FOREIGN KEY (`id_porteiro`) REFERENCES `usuarios` (`id_usuario`);

--
-- Restrições para tabelas `observacoes`
--
ALTER TABLE `observacoes`
  ADD CONSTRAINT `observacoes_ibfk_1` FOREIGN KEY (`id_porteiro`) REFERENCES `usuarios` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
