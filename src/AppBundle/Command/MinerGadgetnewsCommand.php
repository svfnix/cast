<?php

namespace AppBundle\Command;

use AppBundle\Entity\Article;
use AppBundle\Wrapper\Wordpress;
use Goutte\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

class MinerGadgetnewsCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:gadgetnews')
            ->setDescription('crawl gadgetnews')
        ;
    }

    private function clean($content){

        $content = $this->clearContent($content);

        if (!empty($content)) {

            $content = preg_replace_callback(
                '/<div class=\"gallery-images\">(.*?)<\/div>/si',
                function ($find) {
                    $crawler = new Crawler($find[0]);
                    $pretty_photo = $crawler->filter('a')->each(function ($a, $j) {
                        $img = $a->filter('img')->first();
                        return '<li><a rel="prettyPhoto" href="' . $a->attr('href') . '"><img src="' . $img->attr('src') . '" width="100%"/></a></li>';
                    });

                    return '<ul class="sm-gallery">' . implode("\n", $pretty_photo) . '</ul>';
                }, $content);

            $content = preg_replace_callback(
                '/<div class=\"source-link\">(.*?)<\/div>/si',
                function ($find) {
                    return '';
                }, $content);

        }

        return $content;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start crawling ...");

        $this->startClient();
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $query = $em->createQuery("SELECT s FROM AppBundle:Setting s WHERE s.name = 'gadgetnews_last_crawled_id'");
        $setting = $query->getSingleResult();
        $last_id = $setting->getValue();

        $crawler = $this->client->request('GET', 'http://gadgetnews.ir/feed/');
        $crawler->filter('item')->each(function($item) use ($em, $output, $setting, $last_id){

            $title = $item->filter('title')->text();
            $link = $item->filter('link')->text();
            $guid = $item->filter('guid')->text();

            $article_id = explode('=', $guid);
            $article_id = array_pop($article_id);
            $article_id = intval($article_id);

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
                $summery = $crawler->filter('.entry')->filter('p')->first()->text();

                $content = $crawler->filter('.entry')->first()->html();
                $content = $this->clean($content);


                $tags = [];
                $crawler->filter('.post-tag')->filter('a')->each(function($a) use(&$tags){
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

                $file_name = $this->getRoot() . '/var/cache/gadgetnews.img';
                file_put_contents($file_name, file_get_contents($image));
                if (in_array(exif_imagetype($file_name), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])){

                    $media = $client->uploadFile(
                        'gadgetnews-' . time() . '-' . basename($image),
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
                            ['key' => 'source', 'value' => 'گجت نیوز'],
                            ['key' => 'source_url', 'value' => $link]
                        ],
                        /*'terms' => array('category' => [35])*/
                    ]);

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
