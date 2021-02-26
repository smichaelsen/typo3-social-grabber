<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Facebook\Facebook;
use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;

class FacebookGrabber implements GrabberInterface, TopicFilterableGrabberInterface, UpdatablePostsGrabberInterface
{
    use ExtensionsConfigurationSettable;

    /**
     * @param array $channel
     * @return array
     */
    public function grabData($channel)
    {
        $data = [
            'posts' => []
        ];
        $pageSlug = rtrim(str_replace('https://www.facebook.com/', '', $channel['url']), '/');
        $posts = $this->getPagePosts($pageSlug, $channel['last_post_date']);
        foreach ($posts->getDecodedBody()['data'] as $post) {
            $postRecord = [
                'post_identifier' => $post['id'],
                'publication_date' => strtotime($post['created_time']),
                'teaser' => $this->replaceTags($post['message'], $post['message_tags']),
                'image_url' => json_encode([$post['full_picture']]),
                'url' => $post['permalink_url'],
            ];
            $data['posts'][] = $postRecord;
        }
        return $data;
    }

    /**
     * @param string $message
     * @return string
     */
    protected function replaceHashtags($message)
    {
        preg_match_all('/#[^ ]*/', $message, $result, PREG_PATTERN_ORDER);

        foreach ($result[0] as $item) {
            if (strpos($item, '</a>') !== false || strpos($item, '"') !== false) {
                continue;
            }
            $link = '<a href="https://www.facebook.com/hashtag/' . str_replace('#', '', $item) . '" target="_blank">' . $item . '</a>';
            $message = preg_replace('/' . $item . '/', $link, $message, 1);
        }

        return $message;
    }

    /**
     * @param string $pageName
     * @param int $since
     * @return \Facebook\FacebookResponse
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    protected function getPagePosts($pageName, $since)
    {
        $endpoint = sprintf(
            '/%s/posts?since=%d&fields=%s',
            $pageName,
            $since,
            'message,message_tags,full_picture,created_time,link,name,permalink_url'
        );
        return $this->getFacebookConnection()->get($endpoint);
    }

    /**
     * @return Facebook
     */
    protected function getFacebookConnection()
    {
        static $facebook;
        if (!$facebook instanceof Facebook) {
            $accessToken = sprintf(
                '%s|%s',
                $this->extensionConfiguration['facebook.']['app_id'],
                $this->extensionConfiguration['facebook.']['app_secret']
            );
            $facebook = new Facebook([
                'app_id' => $this->extensionConfiguration['facebook.']['app_id'],
                'app_secret' => $this->extensionConfiguration['facebook.']['app_secret'],
                'default_graph_version' => 'v2.10',
                'default_access_token' => $accessToken,
            ]);
        }
        return $facebook;
    }

    /**
     * @param string $message
     * @param array $messageTags
     * @return string
     */
    protected function replaceTags($message, $messageTags)
    {
        if (!is_array($messageTags)) {
            return $message;
        }
        $tagReplacements = [];
        foreach ($messageTags as $messageTag) {
            $tagReplacement = [
                'start' => $messageTag['offset'],
                'end' => $messageTag['offset'] + $messageTag['length'],
            ];
            switch ($messageTag['type']) {
                case 'user':
                case 'page':
                    $tagReplacement['replacement'] = sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        'https://www.facebook.com/' . $messageTag['id'],
                        $messageTag['name']
                    );
                    break;
                default:
                    continue;
            }
            $tagReplacements[] = $tagReplacement;
        }

        usort($tagReplacements, function ($a, $b) {
            return $b['start'] - $a['start'];
        });
        foreach ($tagReplacements as $entityReplacement) {
            $message = self::mb_substr_replace($message, $entityReplacement['replacement'], $entityReplacement['start'], $entityReplacement['end'] - 1);
        }
        $message = $this->replaceHashtags($message);
        $message = autolink($message);
        return $message;
    }

    /**
     * @param array $posts
     * @return array
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    public function updatePosts($posts)
    {
        $updatedPosts = [];
        foreach ($posts as $post) {
            $endpoint = sprintf(
                '/%s/reactions?summary=total_count',
                $post['post_identifier']
            );
            $response = $this->getFacebookConnection()->get($endpoint);
            $likeCount = $response->getDecodedBody()['summary']['total_count'];
            $endpoint = sprintf(
                '/%s/comments?summary=1',
                $post['post_identifier']
            );
            $response = $this->getFacebookConnection()->get($endpoint);
            $commentCount = $response->getDecodedBody()['summary']['total_count'];
            $updatedPosts[] = [
                'post_identifier' => $post['post_identifier'],
                'reactions' => json_encode([
                    'comment_count' => $commentCount,
                    'like_count' => $likeCount,
                ]),
            ];
        }
        return $updatedPosts;
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

    /**
     * See http://php.net/manual/de/ref.mbstring.php#94220
     *
     * @param string $input
     * @param string $replace
     * @param int $posOpen
     * @param int $posClose
     * @return string
     */
    protected static function mb_substr_replace($input, $replace, $posOpen, $posClose)
    {
        return mb_substr($input, 0, $posOpen) . $replace . mb_substr($input, $posClose + 1);
    }
}
