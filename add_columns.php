<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config/bootstrap.php';

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\SchemaTool;

$entityManager = require __DIR__.'/config/container.php';

$connection = $entityManager->getConnection();

try {
    $sql = "
        ALTER TABLE `user` 
        ADD COLUMN IF NOT EXISTS `is_active` BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL,
        ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL,
        ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL
    ";
    
    $connection->executeQuery($sql);
    echo "Columns added successfully!\n";
} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . "\n";
} 