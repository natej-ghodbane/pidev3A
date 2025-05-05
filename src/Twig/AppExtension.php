<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use App\Repository\PartenaireRepository;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private PartenaireRepository $partenaireRepository;

    public function __construct(PartenaireRepository $partenaireRepository)
    {
        $this->partenaireRepository = $partenaireRepository;
    }

    public function getGlobals(): array
    {
        return [
            'best_partenaires' => $this->partenaireRepository->findTopThreeByMontant(),
        ];
    }
}
