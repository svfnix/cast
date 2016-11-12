<?php

namespace AppBundle\Command;

use AppBundle\AppCommand;
use AppBundle\Entity\Account;
use AppBundle\Entity\Capcha;
use AppBundle\Entity\FakeBlogPool;
use AppBundle\Entity\FakeUserPool;
use Goutte\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class BlogfaSignupCommand extends AppCommand
{
    const service = "blogfa";

    protected function configure()
    {
        $this
            ->setName('blogfa:signup')
            ->setDescription('new signup in blogfa')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $count = 0;
        $em = $this->getContainer()->get('doctrine')->getEntityManager();

        $continue = true;
        while ($continue) {

            $output->writeln(str_repeat('-', 30));

            $count++;
            $output->writeln("create blog: {$count}");

            $output->writeln(" - create client");
            $client = new Client();
            $crawler = $client->request('GET', 'http://blogfa.com/newblog.aspx?');

            $output->writeln(" - grab captcha");
            $client->request('GET', 'http://blogfa.com/captcha.ashx?'. time());
            $this->showImage($client->getResponse()->getContent());

            $helper = $this->getHelper('question');

            $code = null;
            while(empty($code)) {

                $question = new Question('Captcha code: ');
                $code = $helper->ask($input, $output, $question);

                if (empty($code)) {
                    $question = new ConfirmationQuestion('Do you want to refresh captcha? (y/yes): ', false);
                    $acceptance = $helper->ask($input, $output, $question);
                    while (in_array(strtolower($acceptance), ['y', 'yes'])) {
                        $client->request('GET', 'http://blogfa.com/captcha.ashx?' . time());
                        $this->showImage($client->getResponse()->getContent());
                    }
                }
            }

            $output->writeln(" - registring");

            $query = $em->createQuery("SELECT b FROM AppBundle:FakeBlogPool b WHERE b.used = 0");
            $query->setMaxResults(1);
            $blog = $query->getSingleResult();

            $blog->setUsed(true);
            $em->merge($blog);
            $em->flush();

            do{
                $query = $em->createQuery("SELECT u FROM AppBundle:FakeUserPool u WHERE u.used = 0");
                $query->setMaxResults(1);
                $user = $query->getSingleResult();

                $user->setUsed(true);
                $em->merge($user);
                $em->flush();

                $blog_username = $user->getUsername();

                $client->request('GET', 'http://blogfa.com/checkuser.ashx?u=' . $blog_username . '&rnd=0.' . rand());
                $result = $client->getResponse()->getContent();

            } while($result != 'free');

            $blog_password = substr(md5($blog_username), 12, 8);

            $blog_email = $user->getEmail();
            $blog_email = explode('@', $blog_email);
            $blog_email = $blog_email[0] . '@yourinbox.ir';

            $blog_title = $blog->getTitle();

            $blog_description = $blog->getDescription();
            $blog_description = empty($blog_description) ? $blog_title : $blog_description;

            $blog_author = implode(' ', [$user->getFname(), $user->getLname()]);

            $form = $crawler->filter('#master_ContentPlaceHolder1_btnSignUp')->first()->form();
            $client->submit($form, [
                'master$ContentPlaceHolder1$txtUsername' => $blog_username,
                'master$ContentPlaceHolder1$txtPasswordFirst' => $blog_password,
                'master$ContentPlaceHolder1$txtPassword2' => $blog_password,
                'master$ContentPlaceHolder1$txtTitle' => $blog_title,
                'master$ContentPlaceHolder1$txtAuthor' => $blog_author,
                'master$ContentPlaceHolder1$txtDescription' => $blog_description,
                'master$ContentPlaceHolder1$txtPEmail' => $blog_email,
                'txtCaptcha' => $code
            ]);

            $output->writeln(" - weblog created {$blog_username} : {$blog_password} <{$blog_username}.blogfa.com>");

            $output->writeln(" - check mailbox");

            $confirmation_link = null;
            while(empty($confirmation_link)) {

                $inbox = imap_open('{138.201.142.75:143/notls/norsh/novalidate-cert}INBOX', 'cast@yourinbox.ir', 'castpasswd') or die('Cannot connect to Gmail: ' . imap_last_error());
                $emails = imap_search($inbox,'ALL');

                if(!$emails){
                    $output->writeln(" - mailbox is empty. waiting 5 secs.");
                    sleep(5);
                }

                foreach($emails as $email_number) {
                    $overview = imap_fetch_overview($inbox, $email_number, 0);
                    if($overview[0]->to == $blog_email) {
                        $message = imap_fetchbody($inbox, $email_number, 1);
                        $message = base64_decode($message);
                        if(preg_match('#http\:\/\/www\.blogfa\.com\/confirmemail\.aspx\?u\='.$blog_username.'\&c\=[^\"]+#Si', $message, $matches)) {
                            $output->writeln(" - confirmation email found [{$matches[0]}]");
                            $confirmation_link = $matches[0];
                            imap_close($inbox);
                        }
                    }
                }

                if(empty($confirmation_link)){
                    $output->writeln(" - email not received yet. waiting 5 secs.");
                    sleep(5);
                }
            }

            $output->writeln(" - activating blog");
            $client->request('GET', $confirmation_link);

            $account = new Account();
            $account->setService(self::service);
            $account->setUsername($blog_username);
            $account->setPassword($blog_password);
            $account->setEmail($blog_email);

            $em->persist($account);
            $em->flush();

            $output->writeln("weblog successfully created.");
            $question = new Question('Do you want to continue? (y/yes): ');
            $acceptance = $helper->ask($input, $output, $question);
            if (!in_array(strtolower($acceptance), ['y', 'yes', null])) {
                $continue = false;
            }
        }

        $output->writeln("Operation complated");

    }

}
