<?php

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    ['Social Grabber: Feed', 'tx_socialgrabber_feed'],
    'list_type',
    'social_grabber'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'tx_socialgrabber_feed',
    'FILE:EXT:social_grabber/Configuration/Flexform/FeedPlugin.xml'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['tx_socialgrabber_feed'] = 'recursive,select_key,pages';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['tx_socialgrabber_feed'] = 'pi_flexform';