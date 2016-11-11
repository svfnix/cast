<?php
/**
 * Created by PhpStorm.
 * User: svf
 * Date: 11/11/16
 * Time: 3:53 PM
 */

namespace AppBundle\Helper;


class Pixel
{
    private $x;
    private $y;
    private $number;
    private $value;

    function __construct($number, $y, $x, $value)
    {
        $this->number = $number;
        $this->y = $y;
        $this->x = $x;
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setNumber($number)
    {
        $this->number = $number;
    }

    function getNeighborValue($y, $x){
        return isset($this->number[$y - 1][$x]) ? $this->number[$y - 1][$x]->getValue() : 0;
    }

    function top()
    {
        return $this->getNeighborValue($this->y-1, $this->x);
    }

    function top_right()
    {
        return $this->getNeighborValue($this->y-1, $this->x+1);
    }

    function right()
    {
        return $this->getNeighborValue($this->y, $this->x+1);
    }

    function bottom_right()
    {
        return $this->getNeighborValue($this->y+1, $this->x+1);
    }

    function bottom()
    {
        return $this->getNeighborValue($this->y+1, $this->x);
    }

    function bottom_left()
    {
        return $this->getNeighborValue($this->y+1, $this->x-1);
    }

    function left()
    {
        return $this->getNeighborValue($this->y, $this->x-1);
    }

    function top_left()
    {
        return $this->getNeighborValue($this->y-1, $this->x+1);
    }
}