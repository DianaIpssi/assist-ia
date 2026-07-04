<?php

namespace App\Command;

use App\Service\AvailabilitySlotGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ensure-availability-slots',
    description: 'Génère automatiquement les créneaux RDV des 14 prochains jours ouvrés',
)]
class EnsureAvailabilitySlotsCommand extends Command
{
    // Fonction qui initialise l'objet
    public function __construct(private readonly AvailabilitySlotGeneratorService $slotGenerator)
    {
        parent::__construct();
    }
    
    // Fonction qui exécute la commande console
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = $this->slotGenerator->ensureUpcomingSlots();

        if ($created > 0) {
            $io->success(sprintf('%d créneau(x) généré(s) automatiquement.', $created));
        } else {
            $io->note('Les créneaux à venir sont déjà à jour.');
        }

        return Command::SUCCESS;
    }
}
