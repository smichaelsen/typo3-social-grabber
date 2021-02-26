<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$lll = 'LLL:EXT:social_grabber/Resources/Private/Language/locallang_db.xlf:tx_socialgrabber_channel';

return [
    'ctrl' => [
        'title' => $lll,
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'searchFields' => 'title',
        'iconfile' => 'EXT:social_grabber/Resources/Public/Icons/tx_socialgrabber_channel.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'title, grabber_class, url, filter_topics'],
    ],
    'columns' => [
        'filter_topics' => [
            'label' => $lll . '.filter_topics',
            'config' => [
                'type' => 'input',
            ],
        ],
        'grabber_class' => [
            'label' => $lll . '.grabber_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['Instagram', \Smichaelsen\SocialGrabber\Grabber\InstagramGrabber::class],
                    ['Facebook', \Smichaelsen\SocialGrabber\Grabber\FacebookGrabber::class],
                    ['RSS', \Smichaelsen\SocialGrabber\Grabber\RssGrabber::class],
                    ['Twitter', \Smichaelsen\SocialGrabber\Grabber\TwitterGrabber::class],
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
