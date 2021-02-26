<?php

namespace Smichaelsen\SocialGrabber\Command;

use Smichaelsen\SocialGrabber\Grabber\GrabberInterface;
use Smichaelsen\SocialGrabber\Grabber\HttpCachableGrabberInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GrabberCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setDescription('Grab things from the social media streams');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->grab();
        return 0;
    }

    public function grab()
    {
        $channels = $this->loadChannels();

        $flushCache = false;
        foreach ($channels as $channel) {
            if (!class_exists($channel['grabber_class'])) {
                throw new \Exception('Grabber class "' . $channel['grabber_class'] . '" could not be loaded.', 1456736073);
            }
            /** @var GrabberInterface $grabber */
            $grabber = new $channel['grabber_class'];
            if (!$grabber instanceof GrabberInterface) {
                throw new \Exception('Grabber class "' . $channel['grabber_class'] . '" doesn\'t implement the GrabberInterface.', 1456736051);
            }
            $grabber->setExtensionConfiguration($this->extensionConfiguration);

            if ($grabber instanceof HttpCachableGrabberInterface) {
                /** @var HttpCachableGrabberInterface $grabber */
                $grabber->setEtag($channel['feed_etag']);
                $grabber->setLastModified($channel['feed_last_modified']);
            }

            $data = $grabber->grabData($channel);

            // update channel
            if (!empty($data['feed_etag']) || !empty($data['feed_last_modified'])) {
                $channel['feed_etag'] = $data['feed_etag'];
                $channel['feed_last_modified'] = $data['feed_last_modified'];
                $this->getConnectionForTable('tx_socialgrabber_channel')
                    ->update('tx_socialgrabber_channel', $channel, ['uid' => (int)$channel['uid']]);
            }

            // insert posts
            $inserts = [];
            foreach ($data['posts'] as $post) {
                $post['pid'] = $channel['pid'];
                $post['channel'] = $channel['uid'];
                $inserts[] = $post;
            }
            if (count($inserts)) {
                try {
                    $this->getConnectionForTable('tx_socialgrabber_domain_model_post')
                        ->bulkInsert('tx_socialgrabber_domain_model_post', $inserts, array_keys($inserts[0]));
                } catch (\Exception $exception) {
                    throw new \Exception('Error while inserting new posts: ' . $exception->getMessage(), 1467270735);
                }

                $flushCache = true;
            }
            if (count($inserts) > 0) {
                $this->addFlashMessage(get_class($grabber), $channel['url'] . ': Grabbed ' . count($inserts) . ' posts.', FlashMessage::OK);
            } else {
                $this->addFlashMessage(get_class($grabber), $channel['url'] . ': No new posts.', FlashMessage::INFO);
            }
        }
        if ($flushCache) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            if ($cacheManager->hasCache('vhs_main')) {
                $cacheManager->getCache('vhs_main')->flush();
            }
        }
    }
}
