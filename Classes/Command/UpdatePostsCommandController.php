<?php

namespace Smichaelsen\SocialGrabber\Command;

use Smichaelsen\SocialGrabber\Grabber\GrabberInterface;
use Smichaelsen\SocialGrabber\Grabber\UpdatablePostsGrabberInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdatePostsCommandController extends AbstractCommandController
{
    public function updatePostsCommand()
    {
        $this->initialize();
        $channels = $this->loadChannels();
        $flushCache = false;
        foreach ($channels as $channel) {
            $updatedPosts = $this->updatePostsOfChannel($channel);
            if ($updatedPosts > 0) {
                $flushCache = true;
            }
        }
        if ($flushCache) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            if ($cacheManager->hasCache('vhs_main')) {
                $cacheManager->getCache('vhs_main')->remove('tx_socialgrabber_feed');
            }
        }
    }

    /**
     * @param array $channel
     * @return int Number of updated posts
     */
    protected function updatePostsOfChannel(array $channel)
    {
        /** @var GrabberInterface|UpdatablePostsGrabberInterface $grabber */
        $grabber = new $channel['grabber_class']();
        $grabber->setExtensionConfiguration($this->extensionConfiguration);
        if (!$grabber instanceof UpdatablePostsGrabberInterface) {
            return 0;
        }
        $posts = $this->getDatabaseConnection()->exec_SELECTquery(
            'uid, publication_date, post_identifier',
            'tx_socialgrabber_domain_model_post',
            'deleted = 0 AND channel = ' . $channel['uid'],
            '',
            'publication_date DESC'
        );
        $postsToUpdate = [];
        foreach ($posts as $post) {
            $updateProbability = $this->determineUpdateProbability($post['publication_date']);
            $published = 'Published: ' . (new \DateTime('@' . $post['publication_date'], new \DateTimeZone('Europe/Berlin')))->format('d.m.Y - H:i') . '. Probability: ' . $updateProbability;
            if ($updateProbability === 1.0 || (mt_rand() / mt_getrandmax()) <= $updateProbability) {
                $this->addFlashMessage('Update post ' . $post['uid'], $published, FlashMessage::OK);
                $postsToUpdate[] = $post;
            } else {
                $this->addFlashMessage('Don\'t update post ' . $post['uid'], $published, FlashMessage::NOTICE);
            }
        }
        $updatedPosts = $grabber->updatePosts($postsToUpdate);
        foreach ($updatedPosts as $postIdentifier => $updatedPost) {
            if ($updatedPost === '__DELETED__') {
                $this->getDatabaseConnection()->DELETEquery(
                    'tx_socialgrabber_domain_model_post',
                    'post_identifier = ' . $this->getDatabaseConnection()->fullQuoteStr($postIdentifier, '')
                );
            } else {
                $this->getDatabaseConnection()->exec_UPDATEquery(
                    'tx_socialgrabber_domain_model_post',
                    'post_identifier = ' . $this->getDatabaseConnection()->fullQuoteStr($updatedPost['post_identifier'], ''),
                    $updatedPost
                );
            }
        }
        return count($updatedPosts);
    }
}
