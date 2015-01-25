<?php

namespace Rocket\Models;

use Abraham\TwitterOAuth\TwitterOAuth;

class Kernel
{
    protected $tweetConnector;

    protected $renderer;

    protected $response;

    public function __construct($config)
    {
        $this->startSession();
        $this->renderer = new Renderer();
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
                $status = $this->tweetConnector->verifyUser($this->get('oauth_verifier'));
                if (!$status) {
                    $this->clearSession();
                    $this->renderer->renderError("Error authorize user");
                } else {
                    $this->tweetConnector->redirect(TWEET_DEMO_REPORT_URL);
                }
            }
            if ($this->get('handle')) {
                $reportData = $this->tweetConnector
                ->getHandleData($this->get('handle'), $this->get('limit'));
                return $this->renderer->renderReport($reportData);
            }
            return $this->renderer->renderHandleForm();
        } else {
            $token = $this->tweetConnector->requestToken();
            if ($token === false) {
                $this->clearSession();
                return $this->renderer
                ->renderError("There was an error communicating with Twitter.<br>
                    {$this->tweetConnector->response['response']}");
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