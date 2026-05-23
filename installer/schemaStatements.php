<?php

declare(strict_types=1);

/**
 * Idempotent table definitions used by the install wizard. Wrapped in
 * `IF NOT EXISTS` so an operator who restarts the wizard mid-flow does
 * not blow up on the first SQL statement.
 *
 * The schema mirrors the original `installer/createTables.php` and the
 * Phinx migrations under `migrations/M1/`. Newer migrations (M4+) layer
 * on top via the Phinx CLI when available.
 *
 * @return list<string>
 */
return [
    "CREATE TABLE IF NOT EXISTS Item (
          dateCode CHAR(4) NOT NULL
        , serial INT(4) ZEROFILL NOT NULL
        , itemName VARCHAR(50) NOT NULL
        , pla INT(1) NOT NULL DEFAULT 0
        , plaNote VARCHAR(50)
        , paper INT(1) NOT NULL DEFAULT 0
        , paperNote VARCHAR(50)
        , createAt DATETIME NOT NULL
        , concatId CHAR(8) NOT NULL
        , price INT
        , categoryId CHAR(8)
        , updateAt DATETIME
        , archive INT(1) NOT NULL DEFAULT 0
        , archiveNote VARCHAR(50)
        , archiveAt DATETIME
        , PRIMARY KEY(dateCode, serial)
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Category (
          categoryId INT NOT NULL
        , categoryName VARCHAR(50) NOT NULL
        , categoryLeft INT NOT NULL
        , categoryRight INT NOT NULL
        , CHECK (categoryLeft < categoryRight)
        , PRIMARY KEY(categoryId)
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Color (
          concatId CHAR(8) NOT NULL
        , colorCode CHAR(2) NOT NULL
        , colorName VARCHAR(50) NOT NULL
        , image MEDIUMBLOB
        , imageType VARCHAR(100)
        , PRIMARY KEY(concatId, colorCode)
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Size (
          concatId CHAR(8) NOT NULL
        , sizeCode CHAR(2) NOT NULL
        , sizeName VARCHAR(50) NOT NULL
        , orderNumber INT(2)
        , PRIMARY KEY(concatId, sizeCode)
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Detale (
          detaleCode CHAR(12) NOT NULL PRIMARY KEY
        , concatId CHAR(8) NOT NULL
        , colorCode CHAR(2) NOT NULL
        , sizeCode CHAR(2) NOT NULL
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Shelf (
          detaleCode CHAR(12) NOT NULL PRIMARY KEY
        , shelfNumber VARCHAR(15)
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS QuantityLog (
          detaleCode CHAR(12) NOT NULL
        , fluctuation INT(4) NOT NULL
        , inventoryFlag INT(1) DEFAULT 0
        , changeAt DATETIME
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Label (
          labelName VARCHAR(50) NOT NULL PRIMARY KEY
        , marginTop DOUBLE(5,1) NOT NULL
        , marginLeft DOUBLE(5,1) NOT NULL
        , width DOUBLE(5,1) NOT NULL
        , height DOUBLE(5,1) NOT NULL
        , intervalColomn DOUBLE(5,1) NOT NULL
        , intervalRow DOUBLE(5,1) NOT NULL
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS LabelCache (
          detaleCode CHAR(12) NOT NULL PRIMARY KEY
        , sheetsAmount INT(4) NOT NULL
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS Member (
          id CHAR(20) NOT NULL PRIMARY KEY
        , password VARCHAR(255) NOT NULL
        , userName VARCHAR(50) NOT NULL
        , role VARCHAR(20) DEFAULT 'admin'
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS system_setting (
          `key` VARCHAR(120) NOT NULL PRIMARY KEY
        , `value` BLOB NOT NULL
        , `value_type` ENUM('string','int','bool','json','secret') NOT NULL
        , `encrypted` TINYINT(1) NOT NULL DEFAULT 0
        , `updated_at` DATETIME NOT NULL
        , `updated_by` VARCHAR(120) NOT NULL
    ) DEFAULT CHARSET=utf8mb4",
    "CREATE TABLE IF NOT EXISTS system_setting_audit (
          `id` INT AUTO_INCREMENT PRIMARY KEY
        , `key` VARCHAR(120) NOT NULL
        , `old_value` BLOB NULL
        , `new_value` BLOB NULL
        , `changed_by` VARCHAR(120) NOT NULL
        , `changed_at` DATETIME NOT NULL
        , `reason` VARCHAR(255) NULL
    ) DEFAULT CHARSET=utf8mb4",
];
