-- One-time migration for existing databases; preserves borrower details before allowing NULL users.
USE `gerenciamento_chaves`;

ALTER TABLE `movimentacoes`
  ADD COLUMN `nome_retirante` varchar(100) DEFAULT NULL AFTER `id_usuario`,
  ADD COLUMN `cargo_retirante` varchar(100) DEFAULT NULL AFTER `nome_retirante`;

UPDATE `movimentacoes` m
INNER JOIN `usuarios` u ON u.`id_usuario` = m.`id_usuario`
SET m.`nome_retirante` = u.`nome`,
    m.`cargo_retirante` = u.`cargo`
WHERE m.`nome_retirante` IS NULL OR m.`cargo_retirante` IS NULL;

ALTER TABLE `movimentacoes`
  MODIFY COLUMN `id_usuario` int(11) DEFAULT NULL;
