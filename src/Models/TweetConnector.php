<?php
/**
 * Twitter Connector Class
 */
namespace Rocket\Models;

use Abraham\TwitterOAuth\TwitterOAuth;

class TweetConnector
{
    const TIME_FORMAT = "H:00:00";

    protected $connection;

    protected $days = array(
        1 => "Monday",
        2 => "Tuesday",
        3 => "Wednesday",
        4 => "Thursday",
        5 => "Friday",
        6 => "Saturday",
        7 => "Sunday"
        );

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
        $profile = $this->connection->get("users/show", 
            array('screen_name'=>$handle, 'include_entities' => 'true'));

        /* Return profile as is if an error occures */
        if (isset($profile->errors)) {
            return $profile;
        }

        $tweets = $this->connection->get("statuses/user_timeline", 
            array('screen_name'=>$handle, 'count'=>$limit));

        $processedData = $this->processTweets($tweets);
        return array(
            'profile' => $profile,
            'processed_data' => $processedData
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

    protected function processTweets($tweets)
    {
        $media = 0;
        $retweets = 0;
        $favoriteCount = 0;
        $retweetCount = 0;
        $mentionsCount = 0;
        $repliesCount = 0;
        $displayWeb = 0;
        $displayMobile = 0;
        $displayOther = 0;
        $topRetweets = array();
        $calendarActivity = array();
        $daysActivity = array();
        $timeActivity = array();
        $peakEngagement = array();

        foreach ($tweets as $tweet) {
            $date = new \DateTime($tweet->created_at);

            if ($this->isRetweet($tweet)) {
                $retweets += 1;
            }

            if (isset($tweet->retweeted_status)) {
                $favoriteCount += $tweet->retweeted_status->favorite_count;
                $retweetCount += $tweet->retweeted_status->retweet_count;

                /* Map top retweets */
                $topRetweets[$tweet->retweeted_status->retweet_count] = $tweet;
                $peakEngagement[$tweet->retweeted_status->favorite_count + 
                $tweet->retweeted_status->retweet_count] = $tweet;
            }

            /* Add tweet media */
            if (isset($tweet->entities->media)) {
                $media += count($tweet->entities->media);
            }
            $device = $this->getDeviceFromTweet($tweet);
            switch ($device) {
                case 'mobile':
                    $displayMobile += 1;
                    break;
                case 'web':
                    $displayWeb += 1;
                    break;
                case 'other':
                default:
                    $displayOther += 1;
            }

            /* Map calendar activities */
            if (!isset($calendarActivity[$date->format("N")])) {
                $calendarActivity[$date->format("N")] = array();
            }
            if (!isset($calendarActivity[$date->format("N")][$date->format("G")])) {
                $calendarActivity[$date->format("N")][$date->format("G")] = 0;
            }
            $calendarActivity[$date->format("N")][$date->format("G")] += 1;
            
            if (!isset($daysActivity[$date->format("N")])) {
                $daysActivity[$date->format("N")] = 0;
            }
            $daysActivity[$date->format("N")] += 1;

            if (!isset($timeActivity[$date->format(self::TIME_FORMAT)])) {
                $timeActivity[$date->format(self::TIME_FORMAT)] = 0;
            }
            $timeActivity[$date->format(self::TIME_FORMAT)] += 1;
        }

        $this->canonicalizeCalendar($calendarActivity);
        ksort($calendarActivity);
        var_dump($calendarActivity);

        /* Extract top retweets list */
        ksort($topRetweets, SORT_NUMERIC);
        $topRetweets = array_slice(array_reverse($topRetweets), 0, 3);

        /* Extract top day and hour */
        $topDay = array_search(max($daysActivity), $daysActivity);
        $topTime = array_search(max($timeActivity), $timeActivity);

        /* Extract peak engagement tweet metrics */
        $peakEngagementKeys = array_keys($peakEngagement);
        $topPeakKey = max($peakEngagementKeys);
        $averagePeak = array_sum($peakEngagementKeys) / count($peakEngagementKeys);

        return array(
            'favorite_count' => $favoriteCount,
            'retweet_count' => $retweetCount,
            'media' => $media,
            'retweets' => $retweets,
            'display_web' => $displayWeb,
            'display_mobile' => $displayMobile,
            'display_other' => $displayOther,
            'display_total' => count($tweets),
            'top_retweets' => $topRetweets,
            'top_day' => $this->days[$topDay],
            'top_time' => $topTime,
            'peak_engagement_tweet' => $peakEngagement[$topPeakKey],
            'peak_difference' => ($topPeakKey / $averagePeak) * 100,
            'calendar_activities' => $calendarActivity
            );
    }

    /**
     * Get device type of tweet post
     * @param  \stdClass $tweet
     * @return string
     */
    protected function getDeviceFromTweet($tweet)
    {
        $source = $tweet->source;
        $matches = null;
        $returnValue = preg_match('/(android|iphone)/', $source, $matches);
        if ($returnValue) {
            return 'mobile';
        }
        $source = $tweet->source;
        $matches = null;
        $returnValue = preg_match('/Twitter Web Client/', $source, $matches);
        if ($returnValue) {
            return 'web';
        }
        return 'other';
    }

    /**
     * Check if a tweet is a retweet
     * @param  \stdClass  $tweet
     * @return boolean
     */
    protected function isRetweet($tweet)
    {
        $text = $tweet->text;
        $matches = null;
        $returnValue = preg_match('/^RT/', $text, $matches);
        return (boolean)$returnValue;
    }

    /**
     * Canonicalize tweets per hour
     * @param  array &$calendar
     */
    protected function canonicalizeCalendar(&$calendar)
    {
        $maxValue = 0;
        foreach ($calendar as $hours) {
            if($maxValue < max($hours)) {
                $maxValue = max($hours);
            }
        }
        foreach ($calendar as &$hours) {
            foreach ($hours as &$hour) {
                $hour /= $maxValue;
            }
        }
    }
}