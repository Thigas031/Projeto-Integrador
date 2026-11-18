-- One-time migration for existing databases; safe to rerun after completion.
USE `gerenciamento_chaves`;

DELIMITER //
DROP PROCEDURE IF EXISTS `migrate_issue1_salas_constraints`//
CREATE PROCEDURE `migrate_issue1_salas_constraints`()
BEGIN
    DECLARE invalid_rows INT DEFAULT 0;
    DECLARE duplicate_groups INT DEFAULT 0;
    DECLARE index_parts INT DEFAULT 0;
    DECLARE index_columns INT DEFAULT 0;

    SELECT COUNT(*) INTO invalid_rows
    FROM `salas`
    WHERE `numero` IS NULL OR TRIM(`numero`) = ''
       OR `bloco` IS NULL OR TRIM(`bloco`) = '';

    IF invalid_rows > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration stopped: resolve salas with missing number or block first.';
    END IF;

    SELECT COUNT(*) INTO duplicate_groups
    FROM (
        SELECT `numero`, `bloco`
        FROM `salas`
        GROUP BY `numero`, `bloco`
        HAVING COUNT(*) > 1
    ) AS duplicates;

    IF duplicate_groups > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Migration stopped: resolve duplicate number + block pairs first.';
    END IF;

    SELECT COUNT(*) INTO index_parts
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'salas'
      AND index_name = 'uq_salas_numero_bloco';

    IF index_parts > 0 THEN
        SELECT COUNT(*) INTO index_columns
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'salas'
          AND index_name = 'uq_salas_numero_bloco'
          AND non_unique = 0
          AND (
              (seq_in_index = 1 AND column_name = 'numero')
              OR (seq_in_index = 2 AND column_name = 'bloco')
          );

        IF index_parts <> 2 OR index_columns <> 2 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Migration stopped: index uq_salas_numero_bloco exists with an unexpected definition.';
        END IF;
    END IF;

    ALTER TABLE `salas`
        MODIFY `numero` varchar(20) NOT NULL,
        MODIFY `bloco` varchar(50) NOT NULL;

    IF index_parts = 0 THEN
        ALTER TABLE `salas`
            ADD UNIQUE KEY `uq_salas_numero_bloco` (`numero`, `bloco`);
    END IF;
END//
DELIMITER ;

CALL `migrate_issue1_salas_constraints`();
DROP PROCEDURE `migrate_issue1_salas_constraints`;
