<?php

namespace AppBundle;

use Goutte\Client;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class AppBundle extends Bundle
{
    /**
     * @var Client
     */
    protected $client;
}
