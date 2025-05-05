<?php

namespace App\Command;

use App\Service\SmsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-sms',
    description: 'Test SMS sending functionality',
)]
class TestSmsCommand extends Command
{
    private $smsService;

    public function __construct(SmsService $smsService)
    {
        parent::__construct();
        $this->smsService = $smsService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->smsService->sendSms(
                '21658664146',
                'Test message from TrekSwap application!'
            );

            $io->success('SMS sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Failed to send SMS: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 