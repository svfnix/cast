<?php

namespace AppBundle\Command;

use AppBundle\Entity\Account;
use AppBundle\Wrapper\AppCommandPersianblog;
use Goutte\Client;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\DomCrawler\Field\InputFormField;

class PersianblogSignupCommand extends AppCommandPersianblog
{
    protected function configure()
    {
        $this
            ->setName('persianblog:signup')
            ->setDescription('new signup in persianblog')
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
            $this->client = new Client();
            $crawler = $this->client->request('GET', 'http://persianblog.ir/Signup.aspx');

            $output->writeln(" - grab captcha");
            $this->client->request('GET', 'http://persianblog.ir/SecurityCode.aspx?rnd='. time());
            $this->exportCaptcha($this->client->getResponse()->getContent(), 'captcha.jpg');

            $helper = $this->getHelper('question');

            $code = null;
            while(empty($code)) {

                $question = new Question('Captcha code: ');
                $code = $helper->ask($input, $output, $question);

                if (empty($code)) {
                    $question = new ConfirmationQuestion('Do you want to refresh captcha? (y/yes): ', false);
                    $acceptance = $helper->ask($input, $output, $question);
                    while (in_array(strtolower($acceptance), ['y', 'yes'])) {
                        $this->client->request('GET', 'http://persianblog.ir/SecurityCode.aspx?rnd='. time());
                        $this->exportCaptcha($this->client->getResponse()->getContent(), 'captcha.jpg');
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

                $domdocument = new \DOMDocument;
                $form = $crawler->filter('#signup')->first()->form();

                $ff = $domdocument->createElement('input');
                $ff->setAttribute('name', '__CALLBACKID');
                $ff->setAttribute('value', '__Page');
                $field1 = new InputFormField($ff);

                $ff = $domdocument->createElement('input');
                $ff->setAttribute('name', '__CALLBACKPARAM');
                $ff->setAttribute('value', $blog_username);
                $field2 = new InputFormField($ff);

                $form->set($field1);
                $form->set($field2);

                $this->client->submit($form);
                $result = $this->client->getResponse()->getContent();

            } while($result != 's0');


            $blog_password = substr(md5($blog_username), 12, 8);

            $blog_email = $user->getEmail();
            $blog_email = explode('@', $blog_email);
            $blog_email = $this->getEmailAddress($blog_email[0]);

            $blog_author = implode(' ', [$user->getFname(), $user->getLname()]);
            $security_question = rand(1, 9);
            $security_answer = $user->getFname();

            $form = $crawler->filter('#btnSignup')->first()->form();
            $this->client->submit($form, [
                'TxtUsername' => $blog_username,
                'TxtPassword1' => $blog_password,
                'TxtPassword2' => $blog_password,
                'TxtFullname' => $blog_author,
                'TxtEmail' => $blog_email,
                'CmbQuestion' => $security_question,
                'TxtPasswordA' => $security_answer,
                'TxtSecurityCode' => $code,
                'ChkTerms' => 'on'
            ]);
            
            $output->writeln(" - user created {$blog_username} : {$blog_password}");

            $output->writeln(" - check mailbox");
            $confirmation_link = null;
            while(empty($confirmation_link)) {

                $inbox = imap_open('{138.201.142.75:143/notls/norsh/novalidate-cert}INBOX', 'cast@yourinbox.ir', 'castpasswd');
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
                        if(preg_match('#http://persianblog.ir/Activation.aspx[^\n]+#Si', $message, $matches)) {
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

            $output->writeln(" - activating user");
            $this->client->request('GET', trim($confirmation_link));


            $output->writeln(" - creating blog");

            $counter = '';
            do{
                $blog_name = "{$blog_username}{$counter}";
                $this->client->request('POST', 'http://persianblog.ir/dyn/blogname-availability.aspx', [], [], ['HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded'], "b={$blog_name}");
                $result = $this->client->getResponse()->getContent();
                $counter++;
            }while($result != 'BLOG_getResult(true);');

            $blog_title = $blog->getTitle();
            $crawler = $this->client->request('GET', 'http://persianblog.ir/CreateBlog.aspx');
            $form = $crawler->filter('#btnCreate')->first()->form();
            $this->client->submit($form, [
                'TxtTitle' => $blog_title,
                'TxtBlogAddress' => $blog_name
            ]);

            $account = new Account();
            $account->setService(self::service);
            $account->setUsername($blog_username);
            $account->setPassword($blog_password);
            $account->setEmail($blog_email);
            $account->setLastUpdate(new \DateTime());
            $account->setTask(0);
            $account->setNews(0);
            $account->setPosts(0);
            $account->setSecurityQuestion($security_question);
            $account->setSecurityAnswer($security_answer);
            $account->setBlog($blog_name);

            $em->persist($account);
            $em->flush();

            $output->writeln("weblog successfully created.");
            $question = new Question('Do you want to continue? (y/yes): ');
            $acceptance = $helper->ask($input, $output, $question);

            if (!in_array(strtolower($acceptance), ['y', 'yes', null])) {
                $continue = false;
            }
        }

        $output->writeln("Operation completed");
    }

}
