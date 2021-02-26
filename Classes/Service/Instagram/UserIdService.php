<?php

namespace Smichaelsen\SocialGrabber\Service\Instagram;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserIdService implements SingletonInterface
{
    protected static $usernamesToIds = [];

    public static function getIdForUsername($username)
    {
        if (!isset(self::$usernamesToIds[$username])) {
            self::$usernamesToIds[$username] = self::getRegistry()->get(__CLASS__, $username);
            if (empty(self::$usernamesToIds[$username])) {
                self::$usernamesToIds[$username] = self::getIdForUsernameFromRemote($username);
                self::getRegistry()->set(__CLASS__, $username, self::$usernamesToIds[$username]);
            }
        }
        return self::$usernamesToIds[$username];
    }

    /**
     * @param string $username
     * @return int
     */
    protected static function getIdForUsernameFromRemote($username)
    {
        return GeneralUtility::makeInstance(InstagramApiClient::class)->getIdForUsername($username);
    }

    /**
     * @return Registry
     */
    protected static function getRegistry()
    {
        static $registry;
        if (!$registry instanceof Registry) {
            $registry = GeneralUtility::makeInstance(Registry::class);
        }
        return $registry;
    }
}
