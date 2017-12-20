<?php
namespace Smichaelsen\SocialGrabber\Service\Instagram;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AccessTokenService implements SingletonInterface
{

    protected static $accessToken = null;

    /**
     * @return bool
     */
    public function isAccesstokenAvailable()
    {
        return $this->getAccessToken() !== null;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        if (self::$accessToken === null) {
            self::$accessToken = $this->getRegistry()->get(__CLASS__, 'accessToken');
        }
        return self::$accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken($accessToken)
    {
        self::$accessToken = $accessToken;
        $this->getRegistry()->set(__CLASS__, 'accessToken', $accessToken);
    }

    /**
     * @return Registry
     */
    protected function getRegistry()
    {
        static $registry;
        if (!$registry instanceof Registry) {
            $registry = GeneralUtility::makeInstance(Registry::class);
        }
        return $registry;
    }

}
