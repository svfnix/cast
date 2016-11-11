<?php
/**
 * Created by PhpStorm.
 * User: svf
 * Date: 11/11/16
 * Time: 3:52 PM
 */

namespace AppBundle\Helper;


class Number
{
    private $pixcels;

    function __construct($map)
    {
        for ($r = 0; $r < count($map); $r++) {
            for ($c = 0; $c < count($map[0]); $c++) {
                $this->pixcels[$r][$c] = new Pixel($this, $r, $c, $map[$r][$c]);
            }
        }
    }
}