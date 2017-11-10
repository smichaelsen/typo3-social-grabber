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
        $limit = (int) $this->getFlexFormValue($processedData['data']['pi_flexform'], 'limit');
        $filterTopics = $this->topicListToArray($this->getFlexFormValue($processedData['data']['pi_flexform'], 'filter_topics'));
        if (empty($channelList)) {
            return $processedData;
        }
        $channelIds = GeneralUtility::intExplode(',', $channelList);
        if (count($channelIds) > 0) {
            $processedData['posts'] = $this->loadPosts($channelIds, $limit, $filterTopics);
        }
        return $processedData;
    }

    protected function loadPosts(array $channelIds, int $limit, array $filterTopics): array
    {
        $posts = [];
        $channels = $this->getDatabaseConnection()->exec_SELECTgetRows(
            'uid, grabber_class, filter_topics',
            'tx_socialgrabber_channel',
            sprintf(
                'tx_socialgrabber_channel.uid IN (%s)',
                join(',', $channelIds)
            )
        );
        $res = $this->getDatabaseConnection()->exec_SELECTquery(
            'tx_socialgrabber_domain_model_post.*, tx_socialgrabber_channel.grabber_class as type',
            'tx_socialgrabber_domain_model_post JOIN tx_socialgrabber_channel ON (tx_socialgrabber_channel.uid = tx_socialgrabber_domain_model_post.channel)',
            sprintf(
                'tx_socialgrabber_domain_model_post.channel IN (%s)%s%s',
                join(',', $channelIds),
                $this->getFilterTopicsWhereStatement($filterTopics, $channels),
                $this->getTypoScriptFrontendController()->sys_page->enableFields('tx_socialgrabber_domain_model_post')
            ),
            '',
            'publication_date DESC',
            $limit === 0 ? '' : $limit
        );
        while ($post = $this->getDatabaseConnection()->sql_fetch_assoc($res)) {
            if ($post['reactions']) {
                $post['reactions'] = json_decode($post['reactions'], true);
            }
            $posts[] = $post;
        }
        return $posts;
    }


    /**
     * @param array $filterTopics
     * @param array $channels
     * @return string
     */
    protected function getFilterTopicsWhereStatement($filterTopics, $channels)
    {
        if (count($channels) === 0) {
            return '';
        }
        $conditions = [];
        foreach ($channels as $channel) {
            if (!in_array(TopicFilterableGrabberInterface::class, class_implements($channel['grabber_class']))) {
                continue;
            }
            /** @var TopicFilterableGrabberInterface $grabber */
            $grabber = new $channel['grabber_class'];
            if (!empty($channel['filter_topics'])) {
                $filterTopics = array_merge($filterTopics, $this->topicListToArray($channel['filter_topics']));
            }
            if (count($filterTopics) === 0) {
                continue;
            }
            $grabberCondition = sprintf(
                'tx_socialgrabber_channel.grabber_class = "%s"%s',
                str_replace('\\', '\\\\', $channel['grabber_class']),
                $grabber->getTopicFilterWhereStatement($filterTopics)
            );
            $conditions[] = $grabberCondition;
        }
        if (count($conditions) === 0) {
            return '';
        }
        $where = ' AND ((' . join(') OR (', $conditions) . '))';
        return $where;
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