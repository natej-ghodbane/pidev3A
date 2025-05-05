<?php
namespace App\Schedule;

use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

class AbonnementScheduler
{
    public function __invoke(ScheduleBuilder $schedule): void
    {
        $schedule
            ->add(CommandTask::new('app:expire-abonnements')) 
            ->everyMinute();
    }
}

