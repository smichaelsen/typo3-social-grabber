<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Abraham\TwitterOAuth\TwitterOAuth;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TwitterGrabber implements GrabberInterface, UpdatablePostsGrabberInterface
{

    // Twitter allows lookup for 100 tweets in one request
    const TWITTER_STATUS_LOOKUP_LIMIT = 100;

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * @param array $channel
     * @return array
     */
    public function grabData($channel)
    {
        $fields = [
            'screen_name' => $channel['url'],
        ];
        if ($channel['last_post_identifier']) {
            $fields['since_id'] = $channel['last_post_identifier'];
        }
        $response = $this->getTwitterConnection()->get('statuses/user_timeline', $fields);

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
                $post = [
                    'post_identifier' => $tweet->id,
                    'publication_date' => strtotime($tweet->created_at),
                    'teaser' => $this->replaceEntities($tweet->text, $tweet->entities),
                    'author' => $tweet->user->name,
                    'author_url' => $tweet->user->url,
                    'author_image_url' => $tweet->user->profile_image_url_https,
                    'url' => sprintf(
                        'https://twitter.com/%s/status/%s',
                        $tweet->user->screen_name,
                        $tweet->id
                    ),
                    'image_url' => '',
                ];
                if ($tweet->extended_entities) {
                    $imageUrls = [];
                    foreach ($tweet->extended_entities->media as $entity) {
                        $imageUrls[] = $entity->media_url_https;
                    }
                    $post['image_url'] = json_encode($imageUrls);
                }
                $data['posts'][] = $post;
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
     * @return TwitterOAuth
     */
    protected function getTwitterConnection()
    {
        static $twitter;
        if (!$twitter instanceof TwitterOAuth) {
            $twitter = new TwitterOAuth(
                $this->extensionConfiguration['consumer_key'],
                $this->extensionConfiguration['consumer_secret'],
                $this->extensionConfiguration['oauth_access_token'],
                $this->extensionConfiguration['oauth_access_token_secret']
            );
        }
        return $twitter;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param string $text
     * @param \stdClass $entities
     * @return string
     */
    protected function replaceEntities($text, $entities)
    {
        $entityReplacements = [];
        if (is_array($entities->urls)) {
            foreach ($entities->urls as $url) {
                $entityReplacements[] = [
                    'start' => $url->indices[0],
                    'end' => $url->indices[1],
                    'replacement' => sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        $url->expanded_url,
                        $url->display_url
                    ),
                ];
            }
        }
        if (is_array($entities->media)) {
            foreach ($entities->media as $mediaEntity) {
                $entityReplacements[] = [
                    'start' => $mediaEntity->indices[0],
                    'end' => $mediaEntity->indices[1],
                    'replacement' => '',
                ];
            }
        }
        if (is_array($entities->user_mentions)) {
            foreach ($entities->user_mentions as $mention) {
                $entityReplacements[] = [
                    'start' => $mention->indices[0],
                    'end' => $mention->indices[1],
                    'replacement' => sprintf(
                        '<a href="https://twitter.com/%1$s" target="_blank">@%1$s</a>',
                        $mention->screen_name
                    ),
                ];
            }
        }
        if (is_array($entities->hashtags)) {
            foreach ($entities->hashtags as $hashtag) {
                $entityReplacements[] = [
                    'start' => $hashtag->indices[0],
                    'end' => $hashtag->indices[1],
                    'replacement' => sprintf(
                        '<a href="https://twitter.com/hashtag/%1$s" target="_blank">#%1$s</a>',
                        $hashtag->text
                    ),
                ];
            }
        }
        usort($entityReplacements, function ($a, $b) {
            return ($b['start'] - $a['start']);
        });
        $text = utf8_decode($text);
        foreach ($entityReplacements as $entityReplacement) {
            $text = substr_replace($text, $entityReplacement['replacement'], $entityReplacement['start'], $entityReplacement['end'] - $entityReplacement['start']);
        }
        return utf8_encode($text);
    }

    /**
     * @param array $extensionConfiguration
     * @return void
     */
    public function setExtensionConfiguration($extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * @param array $posts
     * @return array
     */
    public function updatePosts($posts)
    {
        $updatedPosts = [];
        foreach (array_chunk($posts, self::TWITTER_STATUS_LOOKUP_LIMIT) as $postsChunk) {
            $ids = array_reduce($postsChunk, function($ids, $post) {
                if (!is_array($ids)) {
                    $ids = [];
                }
                $ids[] = $post['post_identifier'];
                return $ids;
            });
            $parameters = [
                'id' => join(',', $ids),
                'include_entities' => false,
                'trim_user' => true,
                'map' => true,
            ];
            $response = $this->getTwitterConnection()->get('statuses/lookup', $parameters);
            foreach ($response->id as $id => $tweet) {
                if ($tweet === null) {
                    $updatedPosts[$id] = '__DELETED__';
                } else {
                    $updatedPosts[$id] = [
                        'reactions' => json_encode([
                            'retweet_count' => $tweet->retweet_count,
                            'favorite_count' => $tweet->favorite_count,
                        ]),
                        'post_identifier' => $tweet->id,
                    ];
                }
            }
        }
        return $updatedPosts;
    }
}
