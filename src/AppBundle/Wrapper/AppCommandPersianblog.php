<?php
namespace AppBundle\Wrapper;

class AppCommandPersianblog extends AppCommand
{
    const service = "persianblog";

    protected function signin($account) {
        $crawler = $this->client->request('GET', 'http://persianblog.ir/Signin.aspx');
        $form = $crawler->filter('#btnLogin')->first()->form();
        $this->client->submit($form, [
            'TxtUsername' => $account->getUsername(),
            'TxtPassword' => $account->getPassword()
        ]);
    }

    protected function getMenuLink($order){
        $crawler = $this->client->request('GET', 'http://persianblog.ir/Home.aspx');
        return $this->client->request('GET', 'http://persianblog.ir/'.$crawler->filter('.blogmenu > a')->eq($order)->attr('href'));
    }
}