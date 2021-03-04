<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;
use Smichaelsen\SocialGrabber\Service\Instagram\AccessTokenService;
use Smichaelsen\SocialGrabber\Service\Instagram\InstagramApiClient;
use Smichaelsen\SocialGrabber\Service\Instagram\UserIdService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class InstagramGrabber implements GrabberInterface, UpdatablePostsGrabberInterface, TopicFilterableGrabberInterface
{
    use ExtensionsConfigurationSettable;

    /**
     * @param array $channel
     * @return array
     * @throws \Andreyco\Instagram\Exception\AuthException
     * @throws \Andreyco\Instagram\Exception\CurlException
     * @throws \Andreyco\Instagram\Exception\InvalidParameterException
     * @throws \Exception
     */
    public function grabData($channel)
    {
        $instagramConnection = GeneralUtility::makeInstance(InstagramApiClient::class);
        $instagramConnection->setAccessToken(GeneralUtility::makeInstance(AccessTokenService::class)->getAccessToken());
        $data = [
            'posts' => []
        ];
        $userId = UserIdService::getIdForUsername($channel['url']);
        // this method seems broken on instagram's side: It ignores the "since" (min_id) parameter, that's why we have a workround inside this function
        $posts = $instagramConnection->getUserMediaSince($userId, $channel['last_post_identifier']);
        if (isset($posts->meta->code) && $posts->meta->code === 400) {
            throw new \Exception('Error while grabbing instagram posts: ' . $posts->meta->error_message);
        }
        foreach ($posts->data as $post) {
            // this is the workaround for the "min_id" bug:
            if ($channel['last_post_date'] >= $post->created_time) {
                continue;
            }
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
                'media_url' => '',
            ];
            if (!empty($post->videos)) {
                $postRecord['media_url'] = json_encode([$post->videos->standard_resolution->url]);
            }
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
            $updatedPost = [
                'post_identifier' => $post['post_identifier'],
                'reactions' => json_encode([
                    'comment_count' => $response->data->comments->count,
                    'favorite_count' => $response->data->likes->count,
                ]),
            ];
            if (!empty($response->data->videos)) {
                $updatedPost['media_url'] = json_encode([$response->data->videos->standard_resolution->url]);
            }
            $updatedPosts[] = $updatedPost;
        }
        return $updatedPosts;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function replaceTags($message)
    {
        // replace hashtags
        $message = preg_replace('/(?:^|\s)#([äöüÄÖÜß0-9a-zA-Z]+)/', ' <a href="https://www.instagram.com/explore/tags/$1/" target="_blank">#$1</a>', $message);
        // replace user mentions
        $message = preg_replace('/(?:^|\s)@(\w+)/', ' <a href="https://www.instagram.com/$1/" target="_blank">@$1</a>', $message);
        // auto link urls
        $message = autolink($message);
        return $message;
    }

    /**
     * @param array $topics
     * @return string
     */
    public function getTopicFilterWhereStatement($topics)
    {
        $topicStatements = [];
        foreach ($topics as $topic) {
            $topicStatements[] = 'LOWER(tx_socialgrabber_domain_model_post.teaser) LIKE "%#' . strtolower($topic) . '%"';
        }
        if (count($topicStatements) === 0) {
            return '';
        }
        return ' AND (' . implode(' OR ', $topicStatements) . ')';
    }
}
