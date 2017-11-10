<?php

namespace Smichaelsen\SocialGrabber\Command;

use Smichaelsen\SocialGrabber\Grabber\GrabberInterface;
use Smichaelsen\SocialGrabber\Grabber\HttpCachableGrabberInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GrabberCommandController extends AbstractCommandController
{

    /**
     *
     */
    public function grabCommand()
    {
        $this->initialize();
        $channels = $this->loadChannels();
        $error = $this->getDatabaseConnection()->sql_error();
        if ($error) {
            throw new \Exception('Couldn\'t load channels: ' . $error);
        }
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
                $this->getDatabaseConnection()->exec_UPDATEquery('tx_socialgrabber_channel', 'uid = ' . (int) $channel['uid'], $channel);
            }

            // insert posts
            $inserts = [];
            foreach ($data['posts'] as $post) {
                $post['pid'] = $channel['pid'];
                $post['channel'] = $channel['uid'];
                $inserts[] = $post;
            }
            if (count($inserts)) {
                $this->getDatabaseConnection()->exec_INSERTmultipleRows('tx_socialgrabber_domain_model_post', array_keys($inserts[0]), $inserts);
                $error = $this->getDatabaseConnection()->sql_error();
                if ($error) {
                    throw new \Exception('Error while inserting new posts: ' . $error, 1467270735);
                }
                $flushCache = true;
            }
            if (count($inserts) > 0) {
                $this->addFlashMessage(get_class($grabber), 'Grabbed ' . count($inserts) . ' posts.', FlashMessage::OK);
            } else {
                $this->addFlashMessage(get_class($grabber), 'No new posts.', FlashMessage::INFO);
            }
        }
        if ($flushCache) {
            $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
            if ($cacheManager->hasCache('vhs_main')) {
                $cacheManager->getCache('vhs_main')->remove('tx_socialgrabber_feed');
            }
        }
    }
}
