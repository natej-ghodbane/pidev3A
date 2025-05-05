<?php


namespace App\Command;

use App\Repository\AbonnementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:expire-abonnements', // ⚡ Match this in your scheduler!
    description: 'Checks abonnements and marks expired ones',
)]
class ExpireAbonnementsCommand extends Command
{
    private AbonnementRepository $abonnementRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(AbonnementRepository $abonnementRepository, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->abonnementRepository = $abonnementRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTimeImmutable();

        $abonnements = $this->abonnementRepository->findBy(['statut' => 'actif']);

        foreach ($abonnements as $abonnement) {
            if ($abonnement->getDateExpiration() < $today) {
                $abonnement->setStatut('expire');
                $output->writeln('✅ Abonnement ID ' . $abonnement->getIdAbonnement() . ' expire.');
            }
        }

        $this->entityManager->flush();

        $output->writeln('✅ Checked ' . count($abonnements) . ' abonnements.');

        return Command::SUCCESS;
    }
}