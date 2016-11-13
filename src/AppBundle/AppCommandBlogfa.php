<?php
namespace AppBundle;

class AppCommandBlogfa extends AppCommand
{
    const service = "blogfa";

    protected function signin($account) {
        $crawler = $this->client->request('GET', 'http://blogfa.com/Desktop/login.aspx?');
        $form = $crawler->filter('.btn')->first()->form();
        $this->client->submit($form, [
            'usrid' => $account->getUsername(),
            'usrpass' => $account->getPassword()
        ]);
    }

    protected function getMenuLink($order){
        $crawler = $this->client->request('GET', 'http://blogfa.com/Desktop/Default.aspx?');
        return $this->client->request('GET', 'http://blogfa.com/Desktop/'.$crawler->filter('#menu > a')->eq($order)->attr('href'));
    }
}