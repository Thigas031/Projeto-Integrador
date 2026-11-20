USE `gerenciamento_chaves`;

DELIMITER //
DROP PROCEDURE IF EXISTS `migrate_add_nome_chave`//
CREATE PROCEDURE `migrate_add_nome_chave`()
BEGIN
    DECLARE column_exists INT DEFAULT 0;

    SELECT COUNT(*) INTO column_exists
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'chaves'
      AND column_name = 'nome_chave';

    IF column_exists = 0 THEN
        ALTER TABLE `chaves`
            ADD COLUMN `nome_chave` varchar(100) DEFAULT NULL AFTER `numero_chave`;
    END IF;
END//
DELIMITER ;

CALL `migrate_add_nome_chave`();
DROP PROCEDURE `migrate_add_nome_chave`;
