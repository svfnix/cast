<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommand;
use AppBundle\Wrapper\Wordpress;
use Goutte\Client;
use AppBundle\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MinerDigimagCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('miner:digimag')
            ->setDescription('Crawl digimag')
        ;
    }

    private function clean($content){

        $content = $this->clearContent($content, ['digikala.com']);

        return $content;
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

                $summery = null;
                $html = [];
                $content->children()->each(function($tag, $i) use($output, &$html, &$summery){

                    if(empty($summery)){
                        $summery = $tag->text();
                    }

                    $tag = $tag->html();
                    if(strpos($tag, 'dkmag') === false) {
                        $html[] = $tag;
                    } else {
                        $summery = null;
                    }
                });

                $content = implode("\n", $html);
                $content = preg_replace_callback('/<img.*?data-lazy-src="([^"]+)"[^>]+>/Si', function($image){
                    return '<img src="'.$image[1].'"/>';
                }, $content);
                $content = $this->clean($content);

                $tags = [];
                $crawler->filter('.post-tags')->filter('a')->each(function($a) use(&$tags){
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

                $file_name = $this->getRoot() . '/var/cache/digimag.img';
                file_put_contents($file_name, file_get_contents($image));
                if (in_array(exif_imagetype($file_name), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG])){

                    $media = $client->uploadFile(
                        'digimag-' . time() . '-' . basename($image),
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
                            ['key' => 'source', 'value' => 'دیجیمگ'],
                            ['key' => 'source_url', 'value' => $link]
                        ],
                        //'terms' => array('category' => [35])
                    ]);

                    if ($article_id > $setting->getValue()) {
                        $setting->setValue($article_id);
                    }
                }
            }

        });

        $output->writeln("finished.");

        /*$em->merge($setting);
        $em->flush();*/
    }

}
