<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Abraham\TwitterOAuth\TwitterOAuth;
use TYPO3\CMS\Core\Database\DatabaseConnection;
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
     * @param array $channel
     * @return array
     */
    public function grabData($channel)
    {
        $this->initialize();
        $connection = new TwitterOAuth(
            $this->extensionConfiguration['consumer_key'],
            $this->extensionConfiguration['consumer_secret'],
            $this->extensionConfiguration['oauth_access_token'],
            $this->extensionConfiguration['oauth_access_token_secret']
        );
        $response = $connection->get(
            'statuses/user_timeline',
            [
                'screen_name' => $channel['url'],
                'since_id' => $channel['last_post_identifier'],
            ]
        );

        $data = [
            'posts' => []
        ];

        if (is_array($response->errors)) {
            foreach ($response->errors as $error) {
                $this->addFlashMessage('Twitter Grabber', $error->code . ': ' . $error->message, FlashMessage::ERROR);
            }
            return $data;
        }

        if (is_array($response)) {
            foreach ($response as $tweet) {
                $data['posts'][] = [
                    'post_identifier' => $tweet->id,
                    'publication_date' => strtotime($tweet->created_at),
                    'teaser' => $tweet->text,
                    'author' => $tweet->user->name,
                    'author_url' => $tweet->user->url
                ];
            }
            if (count($response) > 0) {
                $this->addFlashMessage('Twitter Grabber', 'Grabbed ' . count($response) . ' tweets.', FlashMessage::OK);
            } else {
                $this->addFlashMessage('Twitter Grabber', 'No new tweets.', FlashMessage::INFO);
            }
        }

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

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
