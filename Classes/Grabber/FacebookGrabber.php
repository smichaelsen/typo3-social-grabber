<?php

namespace Smichaelsen\SocialGrabber\Grabber;

use Facebook\Facebook;

class FacebookGrabber implements GrabberInterface
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
        $data = [
            'posts' => []
        ];
        $posts = $this->getPagePosts($channel['url']);
        foreach ($posts->getDecodedBody()['data'] as $post) {
            var_dump($post);
            $postRecord = [
                'post_identifier' => $post['id'],
                'publication_date' => strtotime($post['created_time']),
                'teaser' => $this->replaceTags($post['message'], $post['message_tags']),
                'image_url' => $post['full_picture'],
                'url' => $post['permalink_url'],
            ];
            $data['posts'][] = $postRecord;
        }
        return $data;
    }

    /**
     * @param string $pageName
     * @return \Facebook\FacebookResponse
     */
    protected function getPagePosts($pageName)
    {
        $accessToken = sprintf(
            '%s|%s',
            $this->extensionConfiguration['facebook.']['app_id'],
            $this->extensionConfiguration['facebook.']['app_secret']
        );
        $fb = new Facebook([
            'app_id' => $this->extensionConfiguration['facebook.']['app_id'],
            'app_secret' => $this->extensionConfiguration['facebook.']['app_secret'],
            'default_graph_version' => 'v2.10',
            'default_access_token' => $accessToken,
        ]);

        $endpoint = sprintf(
            '/%s/posts?fields=%s',
            $pageName,
            'message,message_tags,full_picture,created_time,link,name,permalink_url'
        );
        return $fb->get($endpoint);
    }

    /**
     * @param string $message
     * @param array $messageTags
     * @return string
     */
    protected function replaceTags($message, $messageTags)
    {
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
            return ($b['start'] - $a['start']);
        });
        $message = utf8_decode($message);
        foreach ($tagReplacements as $entityReplacement) {
            $message = substr_replace($message, $entityReplacement['replacement'], $entityReplacement['start'], $entityReplacement['end'] - $entityReplacement['start']);
        }
        return utf8_encode($message);
    }
}
