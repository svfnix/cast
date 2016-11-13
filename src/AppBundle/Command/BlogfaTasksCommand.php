<?php

namespace AppBundle\Command;

use AppBundle\Wrapper\AppCommandBlogfa;
use Goutte\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Field\InputFormField;

class BlogfaTasksCommand extends AppCommandBlogfa
{
    protected function configure()
    {
        $this
            ->setName('blogfa:tasks')
            ->setDescription('run a task on blogfa account')
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
        }

        $output->writeln("Task completed successfully");
    }

    /**
     * Set theme
     */
    private function task_1($account, $output, $em) {

        $output->writeln(" - signin to blogfa [".$account->getUsername().".blogfa.com]");
        $this->signin($account);

        $id = rand(1, 26);
        $this->client->request('GET', 'https://www.blogfa.com/Desktop/SelectTemplate.aspx?id='.$id);
        $this->client->request('GET', 'http://blogfa.com/Desktop/refreshblog.ashx?r='.time());
        $output->writeln(" - theme changed to {$id}");

        $account->setTask(1);
        $em->merge($account);
        $em->flush();
    }

    /**
     * Set links
     */
    private function task_2($account, $output, $em) {

        $output->writeln(" - signin to blogfa [".$account->getUsername().".blogfa.com]");
        $this->signin($account);

        $id = rand(1, 26);
        $crawler = $this->client->request('GET', 'https://www.blogfa.com/Desktop/Links.aspx?t='.time());
        $form = $crawler->filter('#btnSave')->first()->form();

        $domdocument = new \DOMDocument;

        $ff = $domdocument->createElement('input');
        $ff->setAttribute('name', 'txttitle1');
        $ff->setAttribute('value', 'سفارش استیکر');
        $field1 = new InputFormField($ff);

        $ff = $domdocument->createElement('input');
        $ff->setAttribute('name', 'txturl1');
        $ff->setAttribute('value', 'http://telegfa.com');
        $field2 = new InputFormField($ff);

        $form->set($field1);
        $form->set($field2);

        $this->client->submit($form, [
            'lcount' => 1
        ]);

        $output->writeln(" - links updated");

        $account->setTask(2);
        $em->merge($account);
        $em->flush();
    }

}
