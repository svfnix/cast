<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommand;
use DOMDocument;
use InvalidArgumentException;
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

        foreach($posts as $post) {

            if (!empty($post['post_content'])) {
                $html = preg_replace_callback(
                    '/<div class=\"demo-gallery\">(.*?)<\/div>/si',
                    function ($find) {
                        $crawler = new Crawler($find[0]);
                        $pretty_photo = $crawler->filter('li')->each(function ($li, $j) {
                            $img = $li->filter('img')->first();
                            return '<li><a rel="prettyPhoto" href="' . $li->attr('data-src') . '"><img src="' . $img->attr('src') . '" width="100%"/></a></li>';
                        });

                        return '<ul class="sm-gallery">' . implode("\n", $pretty_photo) . '</ul>';
                    }, $post['post_content']);

                if($html != $post['post_content']){
                    $conn->executeUpdate('UPDATE `iwpf_posts` SET `post_content` = ? WHERE id = ?', array($html, $post['ID']));
                }
            }
        }

    }
}
