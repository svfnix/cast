<?php

namespace AppBundle\Command;

use AppBundle\Entity\Article;
use AppBundle\Wrapper\AppCommand;
use AppBundle\Wrapper\Wordpress;
use Goutte\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class MinerZoomitCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:zoomit')
            ->setDescription('crawl zoomit')
        ;
    }

    private function clean($content){

        $content = $this->clearContent($content);

        if (!empty($content)) {
            $content = preg_replace_callback(
                '/<div class=\"demo-gallery\">(.*?)<\/div>/si',
                function ($find) {
                    $crawler = new Crawler($find[0]);
                    $pretty_photo = $crawler->filter('li')->each(function ($li, $j) {
                        $img = $li->filter('img')->first();
                        return '<li><a rel="prettyPhoto" href="' . $li->attr('data-src') . '"><img src="' . $img->attr('src') . '" width="100%"/></a></li>';
                    });

                    return '<ul class="sm-gallery">' . implode("\n", $pretty_photo) . '</ul>';
                }, $content);
        }

        return $content;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start crawling ...");

        $this->startClient();
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
                    $summery = $crawler->filter('.article-summery')->first()->text();

                    $content = $crawler->filter('.article-section')->first()->html();
                    $content = preg_replace('#<div.*?مقالات مرتبط.*?</div>#', '', $content);
                    $content = $this->clean($content);

                    $tags = [];
                    $crawler->filter('.article-tag-row')->filter('a')->each(function($a) use(&$tags){
                        $tags[] = $a->text();
                    });

                    $tags = implode(',', $tags);

                    /*$article = new Article();
                    $article->setTitle($title);
                    $article->setContent($content);
                    $article->setImage($image);
                    $article->setSource($link);
                    $article->setTags($tags);
                    $em->persist($article);
                    $em->flush();*/

                    $client = new Wordpress(
                        $this->getContainer()->getParameter('blog_xmlrpc'),
                        $this->getContainer()->getParameter('blog_user'),
                        $this->getContainer()->getParameter('blog_pass')
                    );

                    $file_name = $this->getRoot() . '/var/cache/zoomit.img';
                    file_put_contents($file_name, file_get_contents($image));
                    if (in_array(exif_imagetype($file_name), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])){

                        $media = $client->uploadFile(
                            'zoomit-' . time() . '-' . basename($image),
                            mime_content_type($file_name),
                            file_get_contents($file_name),
                            true
                        );

                        $client->newPost($title, $content, [
                            'post_status' => 'publish',
                            'post_excerpt' => $summery,
                            'tags_input' => $tags,
                            'post_thumbnail' => $media['id'],
                            'custom_fields' => [
                                ['key' => '_bunyad_featured_post', 'value' => '1'],
                                ['key' => 'source', 'value' => 'زومیت'],
                                ['key' => 'source_url', 'value' => $link]
                            ],
                            'terms' => array('category' => [35])
                        ]);

                        if ($article_id > $setting->getValue()) {
                            $setting->setValue($article_id);
                        }
                    }

                }
            }

        });

        $output->writeln("finished.");

        $em->merge($setting);
        $em->flush();
    }

}
