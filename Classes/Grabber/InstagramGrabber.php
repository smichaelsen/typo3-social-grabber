<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Andreyco\Instagram\Client;
use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;
use Smichaelsen\SocialGrabber\Service\Instagram\AccessTokenService;
use Smichaelsen\SocialGrabber\Service\Instagram\InstagramApiClient;
use Smichaelsen\SocialGrabber\Service\Instagram\UserIdService;
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
        $data = [
            'posts' => []
        ];
        $posts = $instagramConnection->getUserMedia(UserIdService::getIdForUsername($channel['url']));
        foreach ($posts->data as $post) {
            $postRecord = [
                'post_identifier' => $post->id,
                'publication_date' => $post->created_time,
                'teaser' => $this->replaceTags($post->caption->text),
                'image_url' => json_encode([$post->images->standard_resolution->url]),
                'url' => $post->link,
                'author' => $post->user->full_name,
                'author_image_url' => $post->user->profile_picture,
                'author_url' => sprintf(
                    'https://www.instagram.com/%s/',
                    $post->user->username
                ),
                'reactions' => json_encode([
                    'comment_count' => $post->comments->count,
                    'favorite_count' => $post->likes->count,
                ]),
            ];
            $data['posts'][] = $postRecord;
        }
        return $data;
    }

    /**
     * @param array $posts
     * @return array
     */
    public function updatePosts($posts)
    {
        $instagramConnection = GeneralUtility::makeInstance(InstagramApiClient::class);
        $instagramConnection->setAccessToken(GeneralUtility::makeInstance(AccessTokenService::class)->getAccessToken());
        $updatedPosts = [];
        foreach ($posts as $post) {
            $response = $instagramConnection->getMedia($post['post_identifier']);
            $updatedPosts[] = [
                'post_identifier' => $post['post_identifier'],
                'reactions' => json_encode([
                    'comment_count' => $response->data->comments->count,
                    'favorite_count' => $response->data->likes->count,
                ]),
            ];
        }
        return $updatedPosts;
    }

    /**
     * @param $message
     * @return string
     */
    protected function replaceTags($message)
    {
        $message = preg_replace('/(?:^|\s)#([äöüÄÖÜß0-9a-zA-Z]+)/', ' <a href="https://www.instagram.com/explore/tags/$1/" target="_blank">#$1</a>', $message);
        $message = preg_replace('/(?:^|\s)@(\w+)/', ' <a href="https://www.instagram.com/$1/" target="_blank">@$1</a>', $message);
        return $message;
    }
}
