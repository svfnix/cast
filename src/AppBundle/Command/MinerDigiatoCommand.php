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

class MinerDigiatoCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:digiato')
            ->setDescription('crawl digiato')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Start crawling ...");

        $this->startClient();
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $query = $em->createQuery("SELECT s FROM AppBundle:Setting s WHERE s.name = 'digiato_last_crawled_id'");
        $setting = $query->getSingleResult();
        $last_id = $setting->getValue();

        $crawler = $this->client->request('GET', 'http://digiato.com/feed/');
        $crawler->filter('item')->each(function($item) use ($em, $output, $setting, $last_id){

            $title = $item->filter('title')->text();
            $link = $item->filter('link')->text();
            $guid = $item->filter('guid')->text();
            $image = $item->filter('image')->filter('url')->text();

            $article_id = explode('=', $guid);
            $article_id = array_pop($article_id);
            $article_id = intval($article_id);

            if ($article_id > $last_id) {

                $output->writeln(" - item found: " . $guid);

                $client = new Client();
                $crawler = $client->request('GET', $link);

                $content = $crawler->filter('.article-content')->first()->html();
                $content = $this->clearContent($content);

                $summery = $crawler->filter('.article-content')->filter('p')->first()->text();

                $tags = [];
                $crawler->filter('.tag-list')->filter('ul')->filter('li')->each(function($li) use(&$tags){
                    $tags[] = $li->filter('a')->text();
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

                $file_name = $this->getRoot() . '/var/cache/digiato.img';
                file_put_contents($file_name, file_get_contents($image));
                if (in_array(exif_imagetype($file_name), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])) {

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
                            ['key' => 'source', 'value' => 'دیجیاتو'],
                            ['key' => 'source_url', 'value' => $link]
                        ],
                        'terms' => array('category' => [128])
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
