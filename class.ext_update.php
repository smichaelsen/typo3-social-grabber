<?php

namespace Smichaelsen\SocialGrabber;

use Smichaelsen\SocialGrabber\Service\Instagram\AccessTokenService;
use Smichaelsen\SocialGrabber\Service\Instagram\InstagramApiClient;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class ext_update
{

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * @return string
     * @throws \Exception
     */
    public function main()
    {
        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['social_grabber']);
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:social_grabber/Resources/Private/Templates/Backend/UpdateScript.html')
        );
        $view->assign('accesstokenService', GeneralUtility::makeInstance(AccessTokenService::class));
        $view->assign('instagramConfiguration', isset($this->extensionConfiguration['instagram.']) ? $this->extensionConfiguration['instagram.'] : null);
        $view->assign('loginUrl', GeneralUtility::makeInstance(InstagramApiClient::class)->getLoginUrl());
        return $view->render();
    }

    public function access()
    {
        return true;
    }
}
