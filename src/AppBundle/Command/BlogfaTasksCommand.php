<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BlogfaTasksCommand extends AppCommandBlogfa
{
    protected function configure()
    {
        $this
            ->setName('blogfa:tasks')
            ->setDescription('run a task on blogfa account')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $output->writeln("Start task");
        $output->writeln(" - select an account");

        $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ':service' ORDER BY a.lastUpdate ASC");
        $query->setParameter('service', self::service);
        $query->setMaxResults(1);
        $account = $query->getSingleResult();

        $this->client = new Client();

        switch ($account->getTask()){
            case 0:
                    $this->task_1($account, $output, $em);
                break;
        }

        $output->writeln("Task completed successfully");
    }

    /**
     * Set theme
     */
    private function task_1($account, $output, $em) {

        $output->writeln(" - signin to blogfa [".$account->getUsername().".blogfa.com]");
        $this->signin($account);

        $id = rand(1, 26);
        $this->client->request('GET', 'https://www.blogfa.com/Desktop/SelectTemplate.aspx?id='.$id);
        $output->writeln(" - theme changed to {$id}");

        $account->setTask(1);
        $em->merge($account);
        $em->flush();
    }

}
