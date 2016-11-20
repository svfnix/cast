<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommand;
use PhpImap\Exception;
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

        $conn = $this->getContainer()->get('database_connection');
        $posts = $conn->fetchAll('SELECT * FROM `iwpf_posts`');
        foreach($posts as $post){
            try{
                $crawler = new Crawler($post['post_content']);
                $crawler->filter('.demo-gallery')->each(function($base, $i){

                    $nodes_li = [];
                    $base->filter('li')->each(function($li, $j) use (&$nodes_li){
                        $nodes_li[] = $li;
                    });

                    $pretty_photo = [];
                    foreach($nodes_li as $node){
                        $img = $node->filter('img')->first();
                        $pretty_photo [] = '<li><a rel="prettyPhoto" href="'.$node->attr('data-src').'" title="'.strip_tags($node->attr('data-sub-html')).'"><img src="'.$img->attr('src').'" alt="'.$img->attr('title').'" title="'.preg_replace('#[a-zA-Z0-9\s]+#Si', ' ', $img->attr('alt')).'"/></a></li>';
                    }

                    $base->getNode(0)->addHtmlContent('<ul class="sm-gallery">'.implode("", $pretty_photo).'</ul>');
                });

                $html = $crawler->html();
                if(strpos($html, 'sm-gallery')) {
                    echo $html;
                }
            } catch (Exception $e){
                echo $e->getMessage();
            }
        }
    }
}
