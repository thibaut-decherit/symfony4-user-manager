<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RemoveUnactivatedAccountsCommand
 * @package App\Command
 */
class RemoveUnactivatedAccountsCommand extends Command
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * RemoveUnactivatedAccountsCommand constructor.
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:remove-unactivated-accounts-older-than')
            ->setDescription('Removes unactivated accounts older than n days.')
            ->addArgument('days', InputArgument::REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = 100;
        $hasResults = true;
        $days = $input->getArgument('days');

        while ($hasResults) {
            $users = $this->em->getRepository(User::class)->findUnactivatedAccountsOlderThan($days, $limit);

            foreach ($users as $user) {
                $this->em->remove($user);
                $this->em->flush();
            }

            if (empty($users)) {
                $hasResults = false;
            }
        }
    }
}
