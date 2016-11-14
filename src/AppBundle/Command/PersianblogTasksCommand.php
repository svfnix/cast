<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommandPersianblog;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PersianblogTasksCommand extends AppCommandPersianblog
{
    protected function configure()
    {
        $this
            ->setName('persianblog:tasks')
            ->setDescription('run a task on persianblog account')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $output->writeln("Start task");
        $output->writeln(" - select an account");

        $query = $em->createQuery("SELECT a FROM AppBundle:Account a WHERE a.service = ?1 ORDER BY a.task ASC");
        $query->setParameter(1, self::service);
        $query->setMaxResults(1);
        $account = $query->getSingleResult();

        $this->client = new Client();

        switch ($account->getTask()){
            case 0:
                $this->task_1($account, $output, $em);
                break;
            case 1:
                $this->task_2($account, $output, $em);
                break;
            case 2:
                $this->task_3($account, $output, $em);
                break;
        }

        $output->writeln("Task completed successfully");
    }

    /**
     * Set id
     */
    private function task_1($account, $output, $em) {

        $output->writeln(" - signin to persianblog [".$account->getBlog().".persianblog.ir]");
        $this->signin($account);

        $output->writeln(" - set blog id");
        $this->client->request('GET', 'http://persianblog.ir/Home.aspx');
        $content = $this->client->getResponse()->getContent();

        preg_match('#ManagePosts\.aspx\?blogid=([0-9]+)#Si', $content, $matchs);
        $account->setBlogId($matchs);

        $account->setTask(1);
        $em->merge($account);
        $em->flush();
    }

    /**
     * Set theme
     */
    private function task_2($account, $output, $em) {

        $output->writeln(" - signin to persianblog [".$account->getBlog().".persianblog.ir]");
        $this->signin($account);

        $output->writeln(" - change theme");
        $crawler = $this->client->request('GET', 'http://persianblog.ir/ChangeTemp.aspx?blogID=' . $account->getBlogId());
        $target = $crawler->filter('#pnlTemplates')->filter('.bt2')->eq(rand(0, 33))->filter('input');
        $id = $target->attr('id');
        $form = $target->form();

        $id = explode('-', $id);
        array_shift($id);
        $id = implode('-', $id);

        $this->client->submit($form, [
            'templateID' => $id
        ]);

        $account->setTask(2);
        $em->merge($account);
        $em->flush();
    }

    /**
     * Set links
     */
    private function task_3($account, $output, $em) {

        $output->writeln(" - signin to persianblog [".$account->getBlog().".persianblog.ir]");
        $this->signin($account);

        $output->writeln(" - add telegfa to linkbox");
        $crawler = $this->client->request('GET', 'http://persianblog.ir/ManageLinks.aspx?blogID=' . $account->getBlogId());
        $form = $crawler->filter('#btnLinkCreate')->first()->form();

        $this->client->submit($form, [
            'TxtLinkAddress' => 'http://telegfa.com',
            'TxtLinkTitle' => 'تلگفا',
            'TxtLinkDescription' => 'سفارش استیکر تلگرام، معرفی کانال ها و گروه های تلگرام'
        ]);

        $account->setTask(3);
        $em->merge($account);
        $em->flush();
    }

}
