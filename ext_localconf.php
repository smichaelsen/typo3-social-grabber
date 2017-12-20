<?php

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Smichaelsen\SocialGrabber\Command\GrabberCommandController::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Smichaelsen\SocialGrabber\Command\UpdatePostsCommandController::class;

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_socialgrabber_instagramoauth'] = \Smichaelsen\SocialGrabber\Eid\InstagramOAuth::class . '::processRequest';
