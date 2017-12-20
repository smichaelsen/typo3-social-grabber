<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Andreyco\Instagram\Client;
use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;
use Smichaelsen\SocialGrabber\Service\Instagram\AccessTokenService;
use Smichaelsen\SocialGrabber\Service\Instagram\InstagramApiClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstagramGrabber implements GrabberInterface, UpdatablePostsGrabberInterface
{

    use ExtensionsConfigurationSettable;

    /**
     * @param array $channel
     * @return array
     */
    public function grabData($channel)
    {

        $instagramConnection = GeneralUtility::makeInstance(InstagramApiClient::class);
        $instagramConnection->setAccessToken(GeneralUtility::makeInstance(AccessTokenService::class)->getAccessToken());
        $posts = $instagramConnection->getUserMedia($channel['url']);
    }

    /**
     * @param array $posts
     * @return array
     */
    public function updatePosts($posts)
    {
        // TODO: Implement updatePosts() method.
    }
}
