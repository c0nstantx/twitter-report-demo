<?php

namespace Rocket\Models;

use Abraham\TwitterOAuth\TwitterOAuth;

class Kernel
{
    protected $tweetConnector;

    protected $renderEngine;

    protected $response;

    public function __construct($config, \Twig_Environment $twig)
    {
        $this->startSession();
        $this->renderEngine = $twig;
        $twitterOauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);
        $this->tweetConnector = new TweetConnector($twitterOauth);
    }

    /**
     * Parse request and return response
     * @return string
     */
    public function parseRequest()
    {
        if ($this->tweetConnector->userIsAuthorized()) {
            if ($this->get('oauth_verifier')) {
                $status = $this->tweetConnector->verifyUser(
                    $this->get('oauth_verifier'));
                if (!$status) {
                    $this->clearSession();
                    $this->renderEngine->render('error.html', 
                        array('html' => 'Error authorize user'));
                } else {
                    $this->tweetConnector->redirect(TWEET_DEMO_REPORT_URL);
                }
            }
            if ($this->get('handle')) {
                $reportData = $this->tweetConnector
                ->getHandleData($this->get('handle'), $this->get('limit'));
                return $this->renderEngine->render('report.html', 
                    array('data'=>$reportData));
            }
            return $this->renderEngine->render('handler_form.html');
        } else {
            $token = $this->tweetConnector->requestToken();
            if ($token === false) {
                $this->clearSession();
                return $this->renderEngine->render('error.html', 
                    array(
                        'html' => "There was an error communicating with Twitter.<br>
                        {$this->tweetConnector->response['response']}"));
            }
        }
    }

    /**
     * Start session
     */
    protected function startSession()
    {
        session_start();
    }

    /**
     * Clear twitter oauth session data
     */
    protected function clearSession()
    {
        unset($_SESSION['twitter_oauth']);
    }

    /**
     * Get parameter from query or post
     * @param  string $parameter
     * @return string|null
     */
    protected function get($parameter)
    {
        if (isset($_GET[$parameter])) {
            return $_GET[$parameter];
        } else if (isset($_POST[$parameter])) {
            return $_POST[$parameter];
        }
        return null;
    }
}