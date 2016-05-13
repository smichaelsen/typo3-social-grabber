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
        'default_sortby' => 'ORDER BY publication_date',
        'delete' => 'deleted',
        'searchFields' => 'title',
        'iconfile' => 'EXT:social_grabber/Resources/Public/Icons/tx_socialgrabber_domain_model_post.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'title, grabber_class, url'],
    ],
    'columns' => [
        'grabber_class' => [
            'label' => $lll . '.grabber_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['RSS', \Smichaelsen\SocialGrabber\Grabber\RssGrabber::class],
                ]
            ],
        ],
        'title' => [
            'label' => $lll . '.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],
        'url' => [
            'label' => $lll . '.url',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'eval' => 'trim'
            ],
        ],
    ],
];
