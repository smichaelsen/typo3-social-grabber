<?php
namespace Smichaelsen\SocialGrabber\Command;

use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

abstract class AbstractCommandController extends CommandController
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
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param int $publication_date
     * @return float
     */
    protected function determineUpdateProbability($publication_date)
    {
        $daysSincePublication = max(1, ($GLOBALS['EXEC_TIME'] - $publication_date) / (24*60*60));
        return (float) 1 / pow($daysSincePublication, 0.99);
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
     * @return array|NULL
     */
    protected function loadChannels()
    {
        $channels = $this->getDatabaseConnection()->exec_SELECTgetRows(
            '
                channel.uid, channel.pid, channel.grabber_class, channel.url, channel.feed_etag, channel.feed_last_modified,
                MAX(post.publication_date) as last_post_date, MAX(post.post_identifier) as last_post_identifier
            ',
            'tx_socialgrabber_channel channel LEFT JOIN tx_socialgrabber_domain_model_post post ON (post.channel = channel.uid) ',
            'channel.deleted = 0 AND channel.hidden = 0',
            'channel.uid',
            '',
            '',
            'uid'
        );
        return $channels;
    }
}
