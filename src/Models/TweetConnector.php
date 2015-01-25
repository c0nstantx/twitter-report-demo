<?php
/**
 * Twitter Connector Class
 */
namespace Rocket\Models;

use Abraham\TwitterOAuth\TwitterOAuth;

class TweetConnector
{
    protected $connection;

    public function __construct(TwitterOAuth $twitterOAuth)
    {
        $this->connection = $twitterOAuth;
        if ($this->userIsAuthorized()) {
            $this->initConnection();
        }
    }

    /**
     * Verify user and add bearer token to session
     */
    public function verifyUser($verifier)
    {
        $this->connection->setOauthToken($_SESSION['twitter_oauth']['oauth_token'], $_SESSION['twitter_oauth']['oauth_token_secret']);
        $accessToken = $this->connection->oauth("oauth/access_token", array("oauth_verifier" => $verifier));

        if (200 == $this->connection->lastHttpCode()) {
            $_SESSION['twitter_oauth'] = $accessToken;
            $_SESSION['twitter_oauth']['status'] = 'verified';
            return true;
        } else {
            return false;
        }
    }

    /**
     * Request token for app
     * @return boolean
     */
    public function requestToken()
    {
        $requestToken = $this->connection->oauth('oauth/request_token', 
            array('oauth_callback' => TWEET_DEMO_REPORT_URL));

        switch ($this->connection->lastHttpCode()) {
            case 200:
                /* Save temporary credentials to session. */
                $_SESSION['twitter_oauth']['oauth_token'] = $requestToken['oauth_token'];
                $_SESSION['twitter_oauth']['oauth_token_secret'] = $requestToken['oauth_token_secret'];
                /* Build authorize URL and redirect user to Twitter. */
                $url = $this->connection->url('oauth/authorize', 
                    array('oauth_token' => $requestToken['oauth_token']));
                break;
            default:
                return false;
        }
        $this->redirect($url);
    }

    /**
     * Get tweets for a specific handle
     * @param  string $handle
     * @param  int    $limit
     * @return array|null
     */
    public function getHandleData($handle, $limit = null)
    {
        if (null === $limit || !is_numeric($limit)) {
            $limit = DEFAULT_TWEET_LIMIT;
        }
        $banner = $this->connection->get("users/profile_banner", 
            array('screen_name'=>$handle));
        if (isset($banner->errors)) {
            return $banner;
        }
        $tweets = $this->connection->get("statuses/user_timeline", 
            array('screen_name'=>$handle, 'count'=>$limit));
        return array(
            'banner' => $banner->sizes->web,
            'tweets' => $tweets
            );
    }

    /**
     * Check if user is already authorized
     * @return boolean
     */
    public function userIsAuthorized()
    {
        return 
        isset($_SESSION['twitter_oauth']['oauth_token']) &&
        isset($_SESSION['twitter_oauth']['oauth_token_secret']);
    }

    /**
     * Redirect to url
     * @param  string $url
     */
    public function redirect($url)
    {
        header("Location: $url");
    }

    /**
     * Initialize connection
     */
    protected function initConnection()
    {
        $this->connection->setOauthToken(
            $_SESSION['twitter_oauth']['oauth_token'], 
            $_SESSION['twitter_oauth']['oauth_token_secret']);
    }
}