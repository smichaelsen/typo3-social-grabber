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
                'scope' => ['public_content'],
            ];
        }
        parent::__construct($config);
    }

    /**
     * @param string $username
     * @return int
     */
    public function getIdForUsername($username)
    {
        $url = sprintf(
            'https://www.instagram.com/%s/?__a=1',
            $username
        );
        $headerData = ['Accept: application/json'];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $data = json_decode(curl_exec($ch));
        return (int) $data->user->id;
    }

    /**
     * @todo: Seems like this is not working. The min_id parameter is ignored by instagram. I filed a bug report. Probably we need to build a workaround on our side.
     * @param $userId
     * @param $sincePostIdentifier
     * @return mixed
     * @throws \Andreyco\Instagram\Exception\AuthException
     * @throws \Andreyco\Instagram\Exception\CurlException
     * @throws \Andreyco\Instagram\Exception\InvalidParameterException
     */
    public function getUserMediaSince($userId, $sincePostIdentifier = null)
    {
        $options = [];
        if ($sincePostIdentifier !== null) {
            $options['min_id'] = $sincePostIdentifier;
        }
        return $this->_makeCall('users/' . $userId . '/media/recent', $options);
    }

}
