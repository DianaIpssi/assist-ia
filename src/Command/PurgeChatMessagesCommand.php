<?php

namespace App\Command;

use App\Repository\ChatMessageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:purge-chat-messages',
    description: 'Supprime les conversations du chat de plus de 12 mois (RGPD)',
)]
class PurgeChatMessagesCommand extends Command
{
    // Durée de conservation annoncée dans la politique de confidentialité
    public const RETENTION = '-12 months';

    // Fonction qui initialise l'objet
    public function __construct(private readonly ChatMessageRepository $chatMessageRepository)
    {
        parent::__construct();
    }

    // Fonction qui exécute la commande console
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $deleted = $this->chatMessageRepository->deleteOlderThan(new \DateTimeImmutable(self::RETENTION));

        $io->note(sprintf('%d message(s) de plus de 12 mois supprimé(s).', $deleted));

        return Command::SUCCESS;
    }
}
