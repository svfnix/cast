<?php

namespace AppBundle\Command;

use Goutte\Client;
use AppBundle\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MinerDigimagCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:digimag')
            ->setDescription('Crawl digimag')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start crawling ...");

        $this->startClient();
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $query = $em->createQuery("SELECT s FROM AppBundle:Setting s WHERE s.name = 'digimag_last_crawled_id'");
        $setting = $query->getSingleResult();
        $last_id = $setting->getValue();

        $crawler = $this->client->request('GET', 'https://mag.digikala.com/feed/');
        $crawler->filter('item')->each(function($item) use ($em, $output, $setting, $last_id){

            $title = $item->filter('title')->text();
            $link = $item->filter('link')->text();
            $guid = $item->filter('guid')->text();

            $article_id = explode('=', $guid);
            $article_id = array_pop($article_id);
            $article_id = intval($article_id);

            if ($article_id > $last_id) {

                $output->writeln(" - item found: " . $guid);

                $client = new Client();
                $crawler = $client->request('GET', $link);
                $content = $crawler->filter('.post-body')->first();

                $image = $content->filter('.attachment-post-thumbnail')->first()->attr('data-lazy-src');

                $html = [];
                $content->filter('p')->each(function($p, $i) use($output, &$html){
                    $html[] = $p->html();
                });

                $content = implode("\n", $html);
                $content = preg_replace_callback('/<img.*?data-lazy-src="([^"]+)"[^>]+>/Si', function($image){
                    return '<img src="'.$image[1].'"/>';
                }, $content);


                $tags = [];
                $crawler->filter('.post-tags')->filter('a')->each(function($a) use(&$tags){
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

        });

        $output->writeln("finished.");

        $em->merge($setting);
        $em->flush();
    }

}
