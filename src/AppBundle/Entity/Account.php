<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ServiceAccount
 *
 * @ORM\Table(name="account")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ServiceAccountRepository")
 */
class Account
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="service", type="string", length=255)
     */
    private $service;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255)
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var \Datetime
     *
     * @ORM\Column(name="last_update", type="datetime")
     */
    private $lastUpdate;

    /**
     * @var integer
     *
     * @ORM\Column(name="task")
     */
    private $task;

    /**
     * @var integer
     *
     * @ORM\Column(name="news")
     */
    private $news;

    /**
     * @var integer
     *
     * @ORM\Column(name="posts")
     */
    private $posts;

    /**
     * @var string
     *
     * @ORM\Column(name="security_question", type="string", length=255)
     */
    private $securityQuestion;

    /**
     * @var string
     *
     * @ORM\Column(name="security_answer", type="string", length=255)
     */
    private $securityAnswer;

    /**
     * @var string
     *
     * @ORM\Column(name="blog", type="string", length=255)
     */
    private $blog;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set service
     *
     * @param string $service
     *
     * @return $this
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->password;
    }

    /**
     * @return \Datetime
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param \Datetime $lastUpdate
     *
     * @return $this
     */
    public function setLastUpdate($lastUpdate)
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param mixed $task
     *
     * @return $this
     */
    public function setTask($task)
    {
        $this->task = $task;

        return $this;
    }

    /**
     * @return int
     */
    public function getNews()
    {
        return $this->news;
    }

    /**
     * @param int $news
     *
     * @return $this
     */
    public function setNews($news)
    {
        $this->news = $news;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param int $posts
     *
     * @return $this
     */
    public function setPosts($posts)
    {
        $this->posts = $posts;

        return $this;
    }

    /**
     * @return $this
     */
    public function incPosts()
    {
        $this->posts++;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityQuestion()
    {
        return $this->securityQuestion;
    }

    /**
     * @param string $securityQuestion
     *
     * @return $this
     */
    public function setSecurityQuestion($securityQuestion)
    {
        $this->securityQuestion = $securityQuestion;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityAnswer()
    {
        return $this->securityAnswer;
    }

    /**
     * @param string $securityAnswer
     *
     * @return $this
     */
    public function setSecurityAnswer($securityAnswer)
    {
        $this->securityAnswer = $securityAnswer;

        return $this;
    }

    /**
     * @return string
     */
    public function getBlog()
    {
        return $this->blog;
    }

    /**
     * @param string $blog
     *
     * @return $this
     */
    public function setBlog($blog)
    {
        $this->blog = $blog;

        return $this;
    }

}
