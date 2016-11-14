<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommandPersianblog;
use Goutte\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PersianblogSyncCommand extends AppCommandPersianblog
{
    protected function configure()
    {
        $this
            ->setName('persianblog:sync')
            ->setDescription('syncing persianblog blog')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $output->writeln("Start syncing");

        $output->writeln(" - select an account for news");
        $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ?1 ORDER BY a.news ASC");
        $query->setParameter(1, self::service);
        $query->setMaxResults(1);
        $account = $query->getSingleResult();

        $output->writeln(" - select a news");
        $query = $em->createQuery("SELECT n FROM AppBundle:News n WHERE n.id > ?1 ORDER BY n.id ASC");
        $query->setParameter(1, $account->getNews());
        $query->setMaxResults(1);
        $article = $query->getOneOrNullResult();

        if($article) {
            $output->writeln(" - news found: " . $article->gettitle());
            $account->setNews($article->getId());
            $content = $article->getContent();
        } else {

            $output->writeln(" - no news found. select an account for update");
            $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ?1 ORDER BY a.lastUpdate ASC");
            $query->setParameter(1, self::service);
            $query->setMaxResults(1);
            $account = $query->getSingleResult();

            $output->writeln(" - select an article");
            $query = $em->createQuery("SELECT a FROM AppBundle:Article a ORDER BY a.id ASC");
            $query->setMaxResults(1);
            $article = $query->getSingleResult();

            $output->writeln(" - article found: " . $article->gettitle());
            $account->setLastUpdate(new \DateTime());
            $content = $this->clearContent($article->getContent());

            $account->incPosts();
        }

        $em->merge($account);
        $em->flush();

        $this->client = new Client();

        $output->writeln(" - signin to blogfa [".$account->getUsername().".persianblog.ir]");
        $this->signin($account);

        $output->writeln(" - submit new post. wait 10 secs  ...");
        $crawler = $this->client->request('GET', 'http://persianblog.ir/CreatePost.aspx?blogID=' . $account->getBlogId());

        $tags = $article->getTags();
        $tags = explode(',', $tags);

        $form = $crawler->filter('#btnPublish')->first()->form();
        $this->client->submit($form, [
            'txtTitle' => $article->getTitle(),
            'intrContent' => $content,
            'keyWord1' => array_shift($tags),
            'keyWord2' => array_shift($tags),
            'keyWord3' => array_shift($tags),
            'keyWord4' => array_shift($tags),
        ]);

        $em->remove($article);
        $em->flush();

        $output->writeln("Operation completed");

    }

}
