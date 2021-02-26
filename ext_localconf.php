<?php

defined('TYPO3_MODE') || die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Smichaelsen\SocialGrabber\Command\GrabberCommand::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Smichaelsen\SocialGrabber\Command\UpdatePostsCommand::class;

$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_socialgrabber_instagramoauth'] = \Smichaelsen\SocialGrabber\Eid\InstagramOAuth::class . '::processRequest';
