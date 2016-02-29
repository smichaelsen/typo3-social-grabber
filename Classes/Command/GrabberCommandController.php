<?php
namespace Smichaelsen\SocialGrabber\Command;

use Smichaelsen\SocialGrabber\Grabber\GrabberInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class GrabberCommandController extends CommandController
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
     *
     */
    public function grabCommand()
    {
        $this->initialize();
        $channels = $this->getDatabaseConnection()->exec_SELECTgetRows('uid, pid, grabber_class, url, feed_etag, feed_last_modified', 'tx_socialgrabber_channel', 'deleted = 0 AND hidden = 0');
        $error = $this->getDatabaseConnection()->sql_error();
        if ($error) {
            throw new \Exception('Couldn\'t load channels: ' . $error);
        }
        foreach ($channels as $channel) {
            if (!class_exists($channel['grabber_class'])) {
                throw new \Exception('Grabber class "' . $channel['grabber_class'] . '" could not be loaded.', 1456736073);
            }
            /** @var GrabberInterface $grabber */
            $grabber = new $channel['grabber_class'];
            if (!$grabber instanceof GrabberInterface) {
                throw new \Exception('Grabber class "' . $channel['grabber_class'] . '" doesn\'t implement the GrabberInterface.', 1456736051);
            }
            $lastPostDate = 1436736214; //todo: get last post date via join

            $data = $grabber->grabData(
                $channel['url'],
                \DateTime::createFromFormat('U', $lastPostDate),
                $channel['feed_etag'],
                empty($channel['feed_last_modified']) ? null : \DateTime::createFromFormat('U', $channel['feed_last_modified'])
            );

            // update channel
            if (!empty($data['feed_etag']) || !empty($data['feed_last_modified'])) {
                $channel['feed_etag'] = $data['feed_etag'];
                $channel['feed_last_modified'] = $data['feed_last_modified'];
                $this->getDatabaseConnection()->exec_UPDATEquery('tx_socialgrabber_channel', 'uid = ' . (int)$channel['uid'], $channel);
            }

            // insert posts
            $inserts = [];
            foreach ($data['posts'] as $post) {
                $post['pid'] = $channel['pid'];
                $post['channel'] = $channel['uid'];
                $inserts[] = $post;
            }
            if (count($inserts)) {
                $this->getDatabaseConnection()->exec_INSERTmultipleRows('tx_socialgrabber_post', array_keys($inserts[0]), $inserts);
            }
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
