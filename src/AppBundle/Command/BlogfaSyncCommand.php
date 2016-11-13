<?php

namespace AppBundle\Command;

use Goutte\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\DateTime;

class BlogfaSyncCommand extends AppCommandBlogfa
{
    protected function configure()
    {
        $this
            ->setName('blogfa:sync')
            ->setDescription('syncing blogfa blogs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $output->writeln("Start syncing");
        $output->writeln(" - select an account");

        $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ':service' ORDER BY a.lastUpdate ASC");
        $query->setParameter('service', self::service);
        $query->setMaxResults(1);
        $account = $query->getSingleResult();

        $account->setLastUpdate(new \DateTime());
        $em->merge($account);
        $em->flush();

        $output->writeln(" - signin to blogfa [".$account->getUsername().".blogfa.com]");

        $this->client = new Client();
        $this->signin($account);

        $output->writeln(" - submit new post. wait 10 secs  ...");
        $crawler = $this->getMenuLink(1);

        sleep(10);
        $form = $crawler->filter('#btnPublish')->first()->form();
        $this->client->submit($form, [
            'txtTitle' => 'سلام',
            'txtPostBody' => '<p>اولین پست</p>'
        ]);

        $output->writeln(" - post submitted successfully");
        $output->writeln(" - refresh blog");
        $this->client->request('GET', 'http://blogfa.com/Desktop/refreshblog.ashx?r='.time());

        $output->writeln("Operation complated");
    }

}
