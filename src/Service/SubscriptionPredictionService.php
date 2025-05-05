<?php

namespace App\Service;

use App\Entity\Abonnement;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionPredictionService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function predictNonRenewal(Abonnement $abonnement): string
    {
        $today = new \DateTimeImmutable();
        $expirationDate = $abonnement->getDateExpiration();

        if ($expirationDate < $today) {
            $interval = $expirationDate->diff($today);
            $daysSinceExpiration = $interval->days;

            if ($daysSinceExpiration > 7) {
                return 'Non-renewal predicted: subscription expired more than 7 days ago.';
            } else {
                return 'Renewal still possible: subscription expired within the last 7 days.';
            }
        }

        if ($expirationDate < $today->modify('+3 days')) {
            return 'Non-renewal predicted: subscription is expiring very soon (within 3 days).';
        }

        return 'Renewal likely: subscription active.';
    }
}
