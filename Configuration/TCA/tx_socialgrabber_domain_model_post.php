<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['tripshop']);

$lll = 'LLL:EXT:social_grabber/Resources/Private/Language/locallang_db.xlf:tx_socialgrabber_domain_model_post';

return [
    'ctrl' => [
        'title' => $lll,
        'label' => 'title',
        'label_alt' => 'teaser',
        'default_sortby' => 'ORDER BY publication_date DESC',
        'delete' => 'deleted',
        'searchFields' => 'title',
        'iconfile' => 'EXT:social_grabber/Resources/Public/Icons/tx_socialgrabber_domain_model_post.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'title, teaser, url, channel'],
    ],
    'columns' => [
        'channel' => [
            'label' => $lll . '.channel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_socialgrabber_channel',
                'foreign_table_where' => 'ORDER BY tx_socialgrabber_channel.title',
                'maxitems' => 1,
            ],
        ],
        'teaser' => [
            'label' => $lll . '.teaser',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
            ],
        ],
        'title' => [
            'label' => $lll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'url' => [
            'label' => $lll . '.url',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'eval' => 'trim',
            ],
        ],
    ],
];
