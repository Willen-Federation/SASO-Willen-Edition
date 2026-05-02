<?php

declare(strict_types=1);

use Saso\Infrastructure\Migration\Migration;

/**
 * M1 baseline: Create legacy application tables before schema modifications.
 *
 * This migration initializes the core schema (Member, Item, Category, etc.)
 * that pre-existed before Phinx migrations were introduced. Subsequent M1
 * migrations (like WidenPasswordColumn) assume these tables exist and modify
 * them incrementally.
 *
 * Idempotent — all CREATE TABLE use IF NOT EXISTS to handle the case where
 * a database was previously initialized via the legacy installer.
 */
final class CreateLegacyTables extends Migration
{
    public function up(): void
    {
        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Member (
              id            CHAR(20)     NOT NULL PRIMARY KEY,
              password      VARCHAR(80)  NOT NULL,
              userName      VARCHAR(50)  NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Item (
              dateCode      CHAR(4)      NOT NULL,
              serial        INT(4)       ZEROFILL NOT NULL,
              itemName      VARCHAR(50)  NOT NULL,
              pla           INT(1)       NOT NULL DEFAULT 0,
              plaNote       VARCHAR(50),
              paper         INT(1)       NOT NULL DEFAULT 0,
              paperNote     VARCHAR(50),
              createAt      DATETIME     NOT NULL,
              concatId      CHAR(8)      NOT NULL,
              price         INT,
              categoryId    CHAR(8),
              updateAt      DATETIME,
              archive       INT(1)       NOT NULL DEFAULT 0,
              archiveNote   VARCHAR(50),
              archiveAt     DATETIME,
              PRIMARY KEY (dateCode, serial)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Category (
              categoryId    INT          NOT NULL PRIMARY KEY,
              categoryName  VARCHAR(50)  NOT NULL,
              categoryLeft  INT          NOT NULL,
              categoryRight INT          NOT NULL,
              CHECK (categoryLeft < categoryRight)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Color (
              concatId      CHAR(8)      NOT NULL,
              colorCode     CHAR(2)      NOT NULL,
              colorName     VARCHAR(50)  NOT NULL,
              image         MEDIUMBLOB,
              imageType     VARCHAR(100),
              PRIMARY KEY (concatId, colorCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Size (
              concatId      CHAR(8)      NOT NULL,
              sizeCode      CHAR(2)      NOT NULL,
              sizeName      VARCHAR(50)  NOT NULL,
              orderNumber   INT(2),
              PRIMARY KEY (concatId, sizeCode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Detale (
              detaleCode    CHAR(12)     NOT NULL PRIMARY KEY,
              concatId      CHAR(8)      NOT NULL,
              colorCode     CHAR(2)      NOT NULL,
              sizeCode      CHAR(2)      NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Shelf (
              detaleCode    CHAR(12)     NOT NULL PRIMARY KEY,
              shelfNumber   VARCHAR(15)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS QuantityLog (
              detaleCode    CHAR(12)     NOT NULL,
              fluctuation   INT(4)       NOT NULL,
              inventoryFlag INT(1)       DEFAULT 0,
              changeAt      DATETIME
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS Label (
              labelName              VARCHAR(50)  NOT NULL PRIMARY KEY,
              marginTop              DOUBLE(5,1)  NOT NULL,
              marginLeft             DOUBLE(5,1)  NOT NULL,
              width                  DOUBLE(5,1)  NOT NULL,
              height                 DOUBLE(5,1)  NOT NULL,
              intervalColomn         DOUBLE(5,1)  NOT NULL,
              intervalRow            DOUBLE(5,1)  NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );

        $this->execute(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS LabelCache (
              detaleCode    CHAR(12)     NOT NULL PRIMARY KEY,
              sheetsAmount  INT(4)       NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        );
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS LabelCache');
        $this->execute('DROP TABLE IF EXISTS Label');
        $this->execute('DROP TABLE IF EXISTS QuantityLog');
        $this->execute('DROP TABLE IF EXISTS Shelf');
        $this->execute('DROP TABLE IF EXISTS Detale');
        $this->execute('DROP TABLE IF EXISTS Size');
        $this->execute('DROP TABLE IF EXISTS Color');
        $this->execute('DROP TABLE IF EXISTS Category');
        $this->execute('DROP TABLE IF EXISTS Item');
        $this->execute('DROP TABLE IF EXISTS Member');
    }
}
