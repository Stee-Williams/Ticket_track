<?php

namespace App\Command;

use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed',
    description: 'Insère les données de démonstration (comptes et tickets de test).'
)]
class SeedCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Initialisation des données de démonstration');

        // Nettoyage complet + réinitialisation des séquences (PostgreSQL)
        $this->em->getConnection()->executeStatement(
            'TRUNCATE TABLE ticket_history, notifications, ticket_comments, tickets, users RESTART IDENTITY CASCADE'
        );

        $password = password_hash('password123', PASSWORD_BCRYPT);

        $definitions = [
            ['Alice Manager',  'manager@tickettrack.com',    User::ROLE_MANAGER,    '+33600000001'],
            ['Bob Technicien', 'technicien@tickettrack.com', User::ROLE_TECHNICIAN, '+33600000002'],
            ['Sam Technicien', 'sam@tickettrack.com',        User::ROLE_TECHNICIAN, '+33600000004'],
            ['Charlie Client', 'client@tickettrack.com',     User::ROLE_USER,       '+33600000003'],
        ];

        /** @var User[] $users */
        $users = [];
        foreach ($definitions as $d) {
            $user = (new User())
                ->setName($d[0])
                ->setEmail($d[1])
                ->setPassword($password)
                ->setRole($d[2])
                ->setPhone($d[3]);
            $this->em->persist($user);
            $users[$d[2] === User::ROLE_USER ? 'client' : ($d[1])] = $user;
            $io->writeln("  Utilisateur créé : {$d[1]} ({$d[2]})");
        }
        $this->em->flush();

        $manager = $users['manager@tickettrack.com'];
        $tech    = $users['technicien@tickettrack.com'];
        $client  = $users['client'];

        $ticketDefs = [
            ['Imprimante hors service', "L'imprimante du 2e étage n'imprime plus depuis ce matin.", 'Matériel', 'haute', 'en_cours', $tech],
            ['Connexion Wi-Fi instable', 'La connexion Wi-Fi se coupe toutes les 10 minutes dans la salle de réunion.', 'Réseau', 'normale', 'en_attente', null],
            ['Écran noir au démarrage', 'Mon ordinateur affiche un écran noir après la mise à jour.', 'Matériel', 'urgente', 'traite', $tech],
            ['Accès messagerie impossible', 'Je ne peux plus accéder à ma boîte mail depuis hier.', 'Logiciel', 'normale', 'en_attente', null],
        ];

        $i = 1;
        foreach ($ticketDefs as $t) {
            $ticket = (new Ticket())
                ->setReference('TCK-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT))
                ->setTitle($t[0])
                ->setDescription($t[1])
                ->setCategory($t[2])
                ->setPriority($t[3])
                ->setStatus($t[4])
                ->setCreatedBy($client)
                ->setAssignedTo($t[5]);
            if ($t[4] === 'traite') {
                $ticket->setResolvedAt(new \DateTime());
            }
            $this->em->persist($ticket);
            $io->writeln("  Ticket créé : {$ticket->getReference()} - {$t[0]}");
            $i++;
        }
        $this->em->flush();

        $io->success('Terminé ! Comptes de test (mot de passe : password123) : manager@ / technicien@ / client@tickettrack.com');

        return Command::SUCCESS;
    }
}
