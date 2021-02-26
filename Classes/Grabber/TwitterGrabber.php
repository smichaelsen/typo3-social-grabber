<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Abraham\TwitterOAuth\TwitterOAuth;
use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;
use Smichaelsen\SocialGrabber\Service\Twitter\TwitterEntityReplacer;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TwitterGrabber implements GrabberInterface, TopicFilterableGrabberInterface, UpdatablePostsGrabberInterface
{

    use ExtensionsConfigurationSettable;

    // Twitter allows lookup for 100 tweets in one request
    const TWITTER_STATUS_LOOKUP_LIMIT = 100;

    public function grabData(array $channel): array
    {
        $fields = [
            'screen_name' => $channel['url'],
            'tweet_mode' => 'extended',
            'include_rts' => true,
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
                $post = $this->createPostRecordFromTweet($tweet);
                $isRetweet = !empty($tweet->retweeted_status);
                if ($isRetweet) {
                    $retweetedPost = $this->createPostRecordFromTweet($tweet->retweeted_status);
                    $retweetedPost['teaser'] = 'Julius Baer retweeted: ' . $retweetedPost['teaser'];
                    $retweetedPost['is_shared_post'] = '1';
                    $data['posts'][] = $retweetedPost;
                    $post['shared_post_identifier'] = $tweet->retweeted_status->id;
                } else {
                    $data['posts'][] = $post;
                }
            }

            $data = $this->removeExistingTweets($data);
        }

        return $data;
    }

    /**
     * @param \stdClass $tweet
     * @return array
     */
    protected function createPostRecordFromTweet($tweet) {
        $post = [
            'post_identifier' => $tweet->id,
            'publication_date' => strtotime($tweet->created_at),
            'teaser' => TwitterEntityReplacer::replaceEntities($tweet->full_text, $tweet->entities),
            'author' => $tweet->user->name,
            'author_url' => $tweet->user->url,
            'author_image_url' => $tweet->user->profile_image_url_https,
            'url' => sprintf(
                'https://twitter.com/%s/status/%s',
                $tweet->user->screen_name,
                $tweet->id
            ),
            'image_url' => '',
            'is_shared_post' => '0',
            'shared_post_identifier' => '',
        ];
        if ($tweet->extended_entities) {
            $imageUrls = [];
            foreach ($tweet->extended_entities->media as $entity) {
                $imageUrls[] = $entity->media_url_https;
            }
            $post['image_url'] = json_encode($imageUrls);
        }
        return $post;
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
                $this->extensionConfiguration['oauth_access_token_secret'],
            );

            if (isset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']) && $GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy'] !== '') {
                $urlParts = parse_url($GLOBALS['TYPO3_CONF_VARS']['HTTP']['proxy']);
                $twitter->setProxy([
                    'CURLOPT_PROXY' => $urlParts['scheme'] . '://' . $urlParts['host'],
                    'CURLOPT_PROXYUSERPWD' => '',
                    'CURLOPT_PROXYPORT' => $urlParts['port'],
                ]);
            }
        }
        return $twitter;
    }

    /**
     * @param array $posts
     * @return array
     */
    public function updatePosts($posts)
    {
        $updatedPosts = [];
        foreach (array_chunk($posts, self::TWITTER_STATUS_LOOKUP_LIMIT) as $postsChunk) {
            $ids = array_reduce($postsChunk, function ($ids, $post) {
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

    public function getTopicFilterWhereStatement(array $topics, QueryBuilder $query)
    {
        $topicStatements = [];
        if (count($topics) === 0) {
            return null;
        }

        foreach ($topics as $topic) {
            $topicStatements[] = $query->where($query->expr()->like('teaser', '>#' . strtolower($topic) . '<'));
        }

        $query = $query->orWhere($topicStatements);

        return $query;
        //return ' AND (' . join(' OR ', $topicStatements) . ')';
    }

    private function flatten(array $array): array
    {
        return iterator_to_array(
             new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array))
        );
    }

    protected function removeExistingTweets(array $data): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_socialgrabber_domain_model_post');
        $existingTweets = $this->flatten($connection->select(['post_identifier'], 'tx_socialgrabber_domain_model_post')->fetchFirstColumn());
        $data['posts'] = array_filter($data['posts'], function ($post) use ($existingTweets) {
            return in_array($post['post_identifier'], $existingTweets) === false;
        });

        return $data;
    }
}
