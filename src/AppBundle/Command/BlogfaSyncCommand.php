<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommandBlogfa;
use Goutte\Client;
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

        $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ?1 ORDER BY a.lastUpdate ASC");
        $query->setParameter(1, self::service);
        $query->setMaxResults(1);
        $account = $query->getSingleResult();

        $account->setLastUpdate(new \DateTime());
        $em->merge($account);
        $em->flush();

        $this->client = new Client();

        $output->writeln(" - signin to blogfa [".$account->getUsername().".blogfa.com]");
        $this->signin($account);


        $output->writeln(" - select an article");
        $query = $em->createQuery("SELECT a FROM AppBundle:Article a ORDER BY a.id ASC");
        $query->setMaxResults(1);
        $article = $query->getSingleResult();
        $tags = $article->getTags();
        $tags = explode(',', $tags);
        $tags = implode('+', $tags);

        $output->writeln(" - submit new post. wait 10 secs  ...");
        $crawler = $this->getMenuLink(1);

        sleep(10);
        $form = $crawler->filter('#btnPublish')->first()->form();
        $this->client->submit($form, [
            'txtTitle' => $article->getTitle(),
            'txtPostBody' => $article->getContent(),
            'txtTags' => $tags
        ]);

        $em->remove($article);
        $em->flush();

        $output->writeln(" - post submitted successfully");
        $output->writeln(" - refresh blog");
        $this->client->request('GET', 'http://blogfa.com/Desktop/refreshblog.ashx?r='.time());

        $output->writeln("Operation completed");
    }

}
