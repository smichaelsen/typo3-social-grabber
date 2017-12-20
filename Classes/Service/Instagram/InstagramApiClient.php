<?php
namespace Smichaelsen\SocialGrabber\Service\Instagram;

use Andreyco\Instagram\Client;
use Smichaelsen\SocialGrabber\Eid\InstagramOAuth;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wraps the 3rd party instagram client with our configuration
 */
class InstagramApiClient extends Client implements SingletonInterface
{

    /**
     * @param array $config
     * @throws \Andreyco\Instagram\Exception\InvalidParameterException
     * @throws \Exception
     */
    public function __construct($config = null)
    {
        $extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['social_grabber']);
        if ($config === null) {
            $config = [
                'apiKey' => $extensionConfiguration['instagram.']['client_id'],
                'apiSecret' => $extensionConfiguration['instagram.']['client_secret'],
                'apiCallback' => GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST') . '?eID=tx_socialgrabber_instagramoauth&requestToken=' . InstagramOAuth::getRequestToken(),
            ];
        }
        parent::__construct($config);
    }

}
