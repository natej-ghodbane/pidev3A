<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class AddColumnsCommand extends Command
{
    protected static $defaultName = 'app:add-columns';
    private $connection;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this->setDescription('Adds new columns to the user table');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->connection->executeStatement("
                ALTER TABLE `user` 
                ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) DEFAULT 1,
                ADD COLUMN IF NOT EXISTS `last_login` DATETIME NULL,
                ADD COLUMN IF NOT EXISTS `created_at` DATETIME NULL,
                ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL
            ");

            $io->success('New columns added successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 