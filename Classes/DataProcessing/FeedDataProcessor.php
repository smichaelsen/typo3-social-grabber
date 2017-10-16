<?php

namespace Smichaelsen\SocialGrabber\DataProcessing;

use Smichaelsen\SocialGrabber\Grabber\TopicFilterableGrabberInterface;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class FeedDataProcessor implements DataProcessorInterface
{

    /**
     * Process content object data
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array $contentObjectConfiguration The configuration of Content Object
     * @param array $processorConfiguration The configuration of this processor
     * @param array $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $channelList = $this->getFlexFormValue($processedData['data']['pi_flexform'], 'channel');
        $limit = $this->getFlexFormValue($processedData['data']['pi_flexform'], 'limit');
        $filterTopics = $this->topicListToArray($this->getFlexFormValue($processedData['data']['pi_flexform'], 'filter_topics'));
        if (empty($channelList)) {
            return $processedData;
        }
        $channelIds = GeneralUtility::intExplode(',', $channelList);
        if (count($channelIds) > 0) {
            $posts = [];
            $res = $this->getDatabaseConnection()->exec_SELECTquery(
                'tx_socialgrabber_domain_model_post.*, tx_socialgrabber_channel.grabber_class as type',
                'tx_socialgrabber_domain_model_post JOIN tx_socialgrabber_channel ON (tx_socialgrabber_channel.uid = tx_socialgrabber_domain_model_post.channel)',
                sprintf(
                    'tx_socialgrabber_domain_model_post.channel IN (%s)%s%s',
                    join(',', $channelIds),
                    $this->getFilterTopicsWhereStatement($filterTopics),
                    $this->getTypoScriptFrontendController()->sys_page->enableFields('tx_socialgrabber_domain_model_post')
                ),
                '',
                'publication_date DESC',
                $limit
            );
            while ($post = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
                if ($post['reactions']) {
                    $post['reactions'] = json_decode($post['reactions'], true);
                }
                $posts[] = $post;
            }
            $processedData['posts'] = $posts;
        }
        return $processedData;
    }

    /**
     * @param array $filterTopics
     * @return string
     */
    protected function getFilterTopicsWhereStatement($filterTopics)
    {
        if (count($filterTopics) === 0) {
            return '';
        }
        $availableGrabberClasses = array_map(function ($pair) {
            return $pair[1];
        }, $GLOBALS['TCA']['tx_socialgrabber_channel']['columns']['grabber_class']['config']['items']);
        $conditions = [];
        foreach ($availableGrabberClasses as $grabberClass) {
            $grabberCondition = 'tx_socialgrabber_channel.grabber_class = "' . str_replace('\\', '\\\\', $grabberClass) . '"';
            if (in_array(TopicFilterableGrabberInterface::class, class_implements($grabberClass))) {
                /** @var TopicFilterableGrabberInterface $grabber */
                $grabber = new $grabberClass();
                $grabberCondition .= $grabber->getTopicFilterWhereStatement($filterTopics);
            }
            $conditions[] = $grabberCondition;
        }
        if (count($conditions) === 0) {
            return '';
        }
        return ' AND ((' . join(') OR (', $conditions) . '))';
    }

    /**
     * @param string $toplicList
     * @return array
     */
    protected function topicListToArray($toplicList)
    {
        if (empty($toplicList)) {
            return [];
        }
        $topics = array_filter(
            array_map(
                function ($topic) {
                    return ltrim($topic, '#');
                },
                GeneralUtility::trimExplode(',', $toplicList)
            ), function ($topic) {
            return !empty($topic);
        }
        );
        return $topics;
    }

    protected function getFlexFormValue($flexFormContent, $fieldName)
    {
        $data = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexFormContent);
        return isset($data[$fieldName]) ? $data[$fieldName] : null;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}