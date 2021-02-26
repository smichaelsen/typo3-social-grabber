<?php

namespace Smichaelsen\SocialGrabber\Command;

use Smichaelsen\SocialGrabber\Grabber\GrabberInterface;
use Smichaelsen\SocialGrabber\Grabber\UpdatablePostsGrabberInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpdatePostsCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setDescription('Update posts from the social media streams');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updatePosts();
        return 0;
    }

    public function updatePosts()
    {
        $channels = $this->loadChannels();
        $flushCache = false;;
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

    protected function updatePostsOfChannel(array $channel): int
    {
        /** @var GrabberInterface|UpdatablePostsGrabberInterface $grabber */
        $grabber = new $channel['grabber_class'];
        $grabber->setExtensionConfiguration($this->extensionConfiguration);
        if (!$grabber instanceof UpdatablePostsGrabberInterface) {
            return 0;
        }

        $connection = $this->getConnectionForTable('tx_socialgrabber_domain_model_post');
        $posts = $connection->select(
            ['uid', 'publication_date', 'post_identifier'],
            'tx_socialgrabber_domain_model_post',
            ['channel' => $channel['uid']],
            [],
            ['publication_date' => 'DESC']
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
                $connection = $this->getConnectionForTable('tx_socialgrabber_domain_model_post');
                $connection->delete(
                    'tx_socialgrabber_domain_model_post',
                    ['post_identifier' => $connection->quoteIdentifier($postIdentifier)]
                );
            } else {
                $connection = $this->getConnectionForTable('tx_socialgrabber_domain_model_post');
                $connection->update(
                    'tx_socialgrabber_domain_model_post',
                    $updatedPost,
                    ['post_identifier' => $connection->quoteIdentifier($updatedPost['post_identifier'])]
                );
            }
        }
        return count($updatedPosts);
    }
}
