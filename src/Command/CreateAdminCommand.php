<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Input\InputOption;

class CreateAdminCommand extends Command
{
    protected static $defaultName = 'app:create-admin';
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a new admin user')
            ->addOption('non-interactive', 'ni', InputOption::VALUE_NONE, 'Create default admin user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('non-interactive')) {
            $email = 'admin@trekswap.com';
            $firstName = 'Admin';
            $lastName = 'Admin';
            $password = 'admin';
        } else {
            $email = $io->ask('Enter admin email');
            $firstName = $io->ask('Enter admin first name');
            $lastName = $io->ask('Enter admin last name');
            $password = $io->askHidden('Enter admin password');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPrenom($firstName);
        $user->setNom($lastName);
        $user->setTypeUser('Admin');
        
        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $io->success('Admin user created successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 