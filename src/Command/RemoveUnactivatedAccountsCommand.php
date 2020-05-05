<?php

namespace App\Command;

use App\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $limit = 100;
        $removedCount = 0;
        $hasResults = true;
        $days = $input->getArgument('days');
        $minDate = (new DateTime())->sub(new DateInterval("P{$days}D"));

        $this->startOutput($output, $days);

        while ($hasResults) {
            $users = $this->em->getRepository(User::class)->findUnactivatedAccountsOlderThan($minDate, $limit);

            foreach ($users as $user) {
                $this->em->remove($user);
                $this->em->flush();

                $removedCount++;
            }

            if (empty($users)) {
                $hasResults = false;
            }
        }

        $this->endOutput($output, $removedCount);
    }

    /**
     * @param OutputInterface $output
     * @param int $days
     */
    private function startOutput(OutputInterface $output, int $days): void
    {
        $message = "Removing unactivated user accounts older than $days day";

        if ($days > 1) {
            $message = "Removing unactivated user accounts older than $days days";
        }

        $output->writeln($message);
    }

    /**
     * @param OutputInterface $output
     * @param int $removedCount
     */
    private function endOutput(OutputInterface $output, int $removedCount): void
    {
        $message = "<bg=green;fg=black>[OK] $removedCount user has been removed</>";

        if ($removedCount > 1) {
            $message = "<bg=green;fg=black>[OK] $removedCount users have been removed</>";
        }

        $output->writeln($message);
    }
}
