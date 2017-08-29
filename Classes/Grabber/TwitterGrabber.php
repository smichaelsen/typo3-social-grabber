<?php
namespace Smichaelsen\SocialGrabber\Grabber;

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @param string $screenName
     * @param \DateTimeInterface|null $lastPostDate
     * @param string $feedEtag
     * @param \DateTimeInterface $feedLastUpdate
     * @return array
     */
    public function grabData($screenName, $lastPostDate, $feedEtag = null, \DateTimeInterface $feedLastUpdate = null)
    {
        $this->initialize();
        $url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        $getfield = '?screen_name=' . $screenName;
        $requestMethod = 'GET';
        $twitter = new \TwitterAPIExchange($this->extensionConfiguration);
        echo '<br><br><br><br><br><br>';
        var_dump($this->extensionConfiguration);die();
        $response = json_decode(
            $twitter->buildOauth($url, $requestMethod)->setGetfield($getfield)->performRequest(),
        true
        );

        if (!empty($response['errors'])) {
            foreach ($response['errors'] as $error) {
                $this->addFlashMessage('Twitter Grabber Error', $error['code'] . ': ' . $error['message'], FlashMessage::ERROR);
            }
        }

        $data = [
            'posts' => []
        ];
        return $data;
    }

    /**
     * @param string $title
     * @param string $message
     * @param int $severity
     */
    protected function addFlashMessage($title, $message, $severity)
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage($message, $title, $severity));
    }
}
