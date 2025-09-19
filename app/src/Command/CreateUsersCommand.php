<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-users')]
class CreateUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Crée 20 utilisateurs aléatoires dans la base de données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $faker = Factory::create();

        for ($i = 0; $i < 20; $i++) {
            $user = new User();
            $user->setFullname($faker->name());
            $user->setAge($faker->numberBetween(18,35));
            $user->setStudyLevel("Master");
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        $output->writeln('20 utilisateurs créés avec succès !');

        return Command::SUCCESS;
    }
}
