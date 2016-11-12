<?php

namespace AppBundle\Command;

use AppBundle\AppCommand;
use AppBundle\Helper\Captcha;
use AppBundle\Helper\Number;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AppCaptchaCommand extends AppCommand
{
    protected function configure()
    {
        $this
            ->setName('app:captcha')
            ->setDescription('...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $captcha = new Captcha(file_get_contents('cap.jpeg'));
        $captcha->decode()->crop()->print_all();
        $numbers = $captcha->getCropped();
        $number = new Number($numbers[0]);
    }

}
