<?php
namespace Smichaelsen\SocialGrabber\Grabber;

class TwitterGrabber implements GrabberInterface
{

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     *
     */
    protected function initialize()
    {
        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['social_grabber']);
    }

    /**
     * @param string $url
     * @param \DateTimeInterface $lastPostDate
     * @param string $feedEtag
     * @param \DateTimeInterface $feedLastUpdate
     * @return array
     */
    public function grabData($url, \DateTimeInterface $lastPostDate, $feedEtag = null, \DateTimeInterface $feedLastUpdate = null)
    {
        $this->initialize();
        $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield = '?screen_name=frosta_de';
        $requestMethod = 'GET';

        $twitter = new \TwitterAPIExchange([
            'oauth_access_token' => '',
            'oauth_access_token_secret' => '',
            'consumer_key' => $this->extensionConfiguration['twitter_api_key'],
            'consumer_secret' => $this->extensionConfiguration['twitter_api_secret'],
        ]);
        $response = $twitter->setGetfield($getfield)->performRequest();

        var_dump(json_decode($response));
    }
}
