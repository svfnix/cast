<?php

namespace AppBundle\Helper;

/**
 * Created by PhpStorm.
 * User: svf
 * Date: 11/11/16
 * Time: 2:20 PM
 */
class Captcha
{
    private $image;
    private $width;
    private $height;
    private $decode;
    private $crop;

    function __construct($content)
    {
        $this->image = @imagecreatefromstring ($content);
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);

        $this->crop = [];
    }

    public function getCropped()
    {
        return $this->crop;
    }

    function decode()
    {
        $this->decode = [];

        for($y = 0; $y <  $this->height; $y++) {
            for($x = 0; $x <  $this->width; $x++) {
                $color = imagecolorat($this->image, $x, $y);
                $color = imagecolorsforindex($this->image, $color);

                $red = ($color['red'] > 170) ? 0 : 1;
                $blue = ($color['blue'] > 170) ? 0 : 1;
                $green = ($color['green'] > 170) ? 0 : 1;

                if($red || $blue || $green) {
                    $this->decode[$y][$x] = 1;
                } else {
                    $this->decode[$y][$x] = 0;
                }
            }
        }

        return $this;
    }

    function crop()
    {
        $rows = count($this->decode);
        $cols = count($this->decode[0]);

        $flag = 0;
        $n_counter = -1;
        $l_col = 0;
        for($c = 0; $c < $cols; $c++) {

            $sum = 0;
            for($r = 0; $r < $rows; $r++) {
                $sum += $this->decode[$r][$c];
            }

            if($sum){

                if(!$flag){
                    $flag = 1;
                    $n_counter++;
                    $l_col = 0;
                }

                for($r = 0; $r < $rows; $r++) {
                    $this->crop[$n_counter][$r][$l_col] = $this->decode[$r][$c];
                }

                $l_col++;

            } else {
                $flag = 0;
            }

        }


        foreach($this->crop as $key => $number){

            $rows = count($number);
            $cols = count($number[0]);

            $cropped = [];
            for($r = 0; $r < $rows; $r++) {
                $sum = 0;
                for($c = 0; $c < $cols; $c++) {
                    $sum += $number[$r][$c];
                }

                if($sum){
                    $cropped[] = $number[$r];
                }
            }

            $this->crop[$key] = $cropped;
        }

        return $this;
    }

    function print_all(){
        foreach($this->crop as $number) {
            for ($r = 0; $r < count($number); $r++) {
                for ($c = 0; $c < count($number[0]); $c++) {
                    if ($number[$r][$c]) {
                        echo "\033[1;37m .";
                    } else {
                        echo "\033[0;30m .";
                    }
                }
                echo "\033[0m \n";
            }

            echo "\n".str_repeat('=', 30)."\n";
        }
    }

}