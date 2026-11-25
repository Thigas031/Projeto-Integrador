-- Replace legacy "Outro" user accounts with non-login "Funcionario" accounts.
USE `gerenciamento_chaves`;

ALTER TABLE `usuarios`
  MODIFY COLUMN `cargo` enum(
    'Gerente',
    'Porteiro',
    'Professor',
    'Coordenacao',
    'Funcionario',
    'Outro'
  ) NOT NULL;

UPDATE `usuarios`
SET `cargo` = 'Funcionario'
WHERE `cargo` = 'Outro';

ALTER TABLE `usuarios`
  MODIFY COLUMN `cargo` enum(
    'Gerente',
    'Porteiro',
    'Professor',
    'Coordenacao',
    'Funcionario'
  ) NOT NULL;
