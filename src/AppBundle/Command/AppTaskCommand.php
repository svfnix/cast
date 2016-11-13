<?php

namespace AppBundle\Command;

use AppBundle\Entity\FakeBlogPool;
use AppBundle\Entity\FakeUserPool;
use AppBundle\Wrapper\AppCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppTaskCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('app:task')
            ->setDescription('...')
            ->addArgument('argument', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $conn = $this->getContainer()->get('database_connection');

        /*$sql = 'SELECT * FROM `tg_profiles`';
        $rows = $conn->query($sql);
        foreach($rows as $row){
            $blog = new FakeBlogPool();
            $blog->setTitle($row['title']);
            $blog->setDescription($row['description']);
            $blog->setTags($row['tags']);
            $em->persist($blog);
            $em->flush();
        }*/

        $sql = 'SELECT * FROM `users`';
        $rows = $conn->query($sql);
        foreach($rows as $row){
            $user = new FakeUserPool();
            $user->setUsername($row['uname']);
            $user->setFname($row['fname']);
            $user->setLname($row['lname']);
            $user->setEmail($row['email']);
            $em->persist($user);
            $em->flush();
        }

        $output->writeln('task done.');
    }

}
