<?php
namespace AppBundle\Wrapper;


use Goutte\Client;
use GuzzleHttp\Cookie\CookieJar;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class AppCommand extends ContainerAwareCommand
{

    /**
     * @var Client
     */
    protected $client;

    protected function startClient()
    {
        $this->client = new Client();
        $this->client->getClient()->getConfig('cookies')->fromArray(
            [
                '__cfduid' => 'd94f50cd67103e2ae2d0440b68c2f8c431479200138',
                'cf_clearance' => '765186d81a807991a36da6f25859849472d4f474-1479200152-3600',
            ],
            '.blogfa.com'
        );
        $this->client->request('GET', 'http://blogfa.com');
        file_put_contents('client', print_r($this->client, 1));
    }

    protected function getRoot()
    {
        return dirname($this->getContainer()->get('kernel')->getRootDir());
    }

    protected function getEmailAddress($uname)
    {
        return 'boostani1988@gmail.com';
    }

    protected function exportCaptcha($captcha, $name='captcha.png'){
        file_put_contents("/var/www/html/{$name}", $captcha);
    }

    protected function showCaptcha($content)
    {
        echo $content;
        $clr_txt  = '1;37m';
        $clr_bg   = '0;30m';

        $img = @imagecreatefromstring ($content);

        $width = imagesx($img);
        $height = imagesy($img);

        for($y = 0; $y < $height; $y++) {
            for($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);
                $color = imagecolorsforindex($img, $color);

                $red = ($color['red'] > 170) ? 0 : 1;
                $blue = ($color['blue'] > 170) ? 0 : 1;
                $green = ($color['green'] > 170) ? 0 : 1;

                if($red || $blue || $green) {
                    echo "\033[{$clr_txt} .";
                } else {
                    echo "\033[{$clr_bg} .";
                }
            }
            echo "\033[0m \n";
        }
    }

    protected function clearContent($content){

        $content = preg_replace('#<a.*?>(.*?)</a>#i', '\1', $content);
        $content = preg_replace_callback('/<img.*?src="([^"]+)"[^>]+>/Si', function($image){
            return '<img src="'.$image[1].'" style="max-width:100%" />';
        }, $content);

        return $content;
    }

}