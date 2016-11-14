<?php

namespace AppBundle\Command;

use AppBundle\Entity\Article;
use Goutte\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MinerZoomitCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:zoomit')
            ->setDescription('crawl zoomit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start crawling ...");

        $this->client = new Client();
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $query = $em->createQuery("SELECT s FROM AppBundle:Setting s WHERE s.name = 'zoomit_last_crawled_id'");
        $setting = $query->getSingleResult();
        $last_id = $setting->getValue();

        $crawler = $this->client->request('GET', 'http://www.zoomit.ir/feed/');
        $crawler->filter('item')->each(function($item) use ($em, $output, $setting, $last_id){

            $title = $item->filter('title')->text();
            $link = $item->filter('link')->text();
            $owner = explode('/', $link);
            if($owner[2] == 'www.zoomit.ir') {

                $article_id = explode('/', $link);
                $article_id = intval($article_id[6]);

                if ($article_id > $last_id) {

                    $output->writeln(" - item found: " . $title);

                    $image = '';
                    $description = $item->filter('description')->text();
                    preg_match('/<img.*?src="([^"]+)"[^>]+>/i', $description, $images);
                    if (isset($images[1])) {
                        $image = $images[1];
                    }

                    $client = new Client();
                    $crawler = $client->request('GET', $link);
                    $content = $crawler->filter('.article-section')->first()->html();

                    $tags = [];
                    $crawler->filter('.article-tag-row')->filter('a')->each(function($a) use(&$tags){
                        $tags[] = $a->text();
                    });

                    $tags = implode(',', $tags);

                    $article = new Article();
                    $article->setTitle($title);
                    $article->setContent($content);
                    $article->setImage($image);
                    $article->setSource($link);
                    $article->setTags($tags);

                    $em->persist($article);
                    $em->flush();

                    if ($article_id > $setting->getValue()) {
                        $setting->setValue($article_id);
                    }

                }
            }

        });

        $output->writeln("finished.");

        $em->merge($setting);
        $em->flush();
    }

}
