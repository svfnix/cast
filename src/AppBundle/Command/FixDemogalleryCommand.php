<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommand;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class FixDemogalleryCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('fix:demogallery')
            ->setDescription('fix demo gallery')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $conn = $this->getContainer()->get('doctrine.dbal.blog_connection');
        $posts = $conn->fetchAll('SELECT * FROM `iwpf_posts`');

        $this->startClient();
        $crawler = $this->client->request('GET', 'http://invaroonvar.com/%d8%aa%d8%b5%d8%a7%d9%88%db%8c%d8%b1%db%8c-%d8%a7%d8%b2-%d8%b4%db%8c%d8%a7%d8%a6%d9%88%d9%85%db%8c-%d9%85%db%8c-%d9%85%db%8c%da%a9%d8%b3-%d8%af%d8%b1-%d8%b1%d9%86%da%af%e2%80%8c-%d8%b3%d9%81%db%8c/#prettyPhoto');
        $html = $this->client->getResponse()->getContent();

        $crawler->filter('.demo-gallery')->each(function(Crawler $base, $i) use (&$html){

            $nodes_li = [];
            $base->filter('li')->each(function($li, $j) use (&$nodes_li){
                $nodes_li[] = $li;
            });

            $pretty_photo = [];
            foreach($nodes_li as $node){
                $img = $node->filter('img')->first();
                $pretty_photo [] = '<li><a rel="prettyPhoto" href="'.$node->attr('data-src').'" title="'.strip_tags($node->attr('data-sub-html')).'"><img src="'.$img->attr('src').'" alt="'.$img->attr('title').'" title="'.preg_replace('#[a-zA-Z0-9\s]+#Si', ' ', $img->attr('alt')).'"/></a></li>';
            }

            $html = str_replace($base->html(), '<ul class="sm-gallery">'.implode("\n", $pretty_photo).'</ul>', $html);
        });

        echo $html;

    }

}
