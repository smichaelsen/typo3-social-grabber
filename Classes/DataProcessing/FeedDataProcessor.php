<?php

namespace Smichaelsen\SocialGrabber\DataProcessing;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Service\FlexFormService;
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
        $excludeSharedPosts = (bool) $this->getFlexFormValue($processedData['data']['pi_flexform'], 'exclude_shared_posts');
        if (empty($channelList)) {
            return $processedData;
        }
        $channelIds = GeneralUtility::intExplode(',', $channelList);
        if (count($channelIds) > 0) {
            $processedData['posts'] = $this->loadPosts($channelIds, $limit, $excludeSharedPosts);
        }
        return $processedData;
    }

    protected function loadPosts(array $channelIds, int $limit, bool $excludeSharedPosts): array
    {
        $andWhere = [];

        $query = $this->getQueryBuilderForTable('tx_socialgrabber_domain_model_post');

        $andWhere[] = $query->expr()->in('channel', $channelIds);

        if ($excludeSharedPosts) {
            $andWhere[] = $query->expr()->eq('is_shared_post', $query->createNamedParameter(0, \PDO::PARAM_INT));
        }

        // add FilterQuery
        $results = $query
            ->select('tx_socialgrabber_domain_model_post.*', 'c.grabber_class as type')
            ->from('tx_socialgrabber_domain_model_post')
            ->join(
                'tx_socialgrabber_domain_model_post',
                'tx_socialgrabber_channel',
                'c',
                'c.uid = tx_socialgrabber_domain_model_post.channel'
            )
            ->where(...$andWhere)
            ->orderBy('publication_date', 'DESC')
            ->setMaxResults($limit === 0 ? '' : $limit)
            ->execute();

        return array_map(function ($post) {
            if (!empty($post['reactions'])) {
                $post['reactions'] = json_decode($post['reactions'], true);
            }

            return $post;
        }, $results->fetchAllAssociative());
    }

    protected function getFilterTopicsWhereStatement(array $filterTopics, array $channels, $query): ?array
    {
        if (count($channels) === 0) {
            return null;
        }
        $conditions = [];
        $channels = [1, 2];
        foreach ($channels as $channel) {
            //if (!in_array(TopicFilterableGrabberInterface::class, class_implements($channel['grabber_class']))) {
            //    continue;
            //}
            ///** @var TopicFilterableGrabberInterface $grabber */
            //$grabber = new $channel['grabber_class'];
            //if (!empty($channel['filter_topics'])) {
            //    $filterTopics = $this->arrayNotEmptyIntersect($filterTopics, $this->topicListToArray($channel['filter_topics']));
            //}
            //if (count($filterTopics) === 0) {
            //    continue;
            //}
            //$grabberCondition = sprintf(
            //    'c.grabber_class = "%s"%s',
            //    str_replace('\\', '\\\\', $channel['grabber_class']),
            //    $grabber->getTopicFilterWhereStatement($filterTopics, $query)
            //);
            //$conditions[] = $grabberCondition;
            $conditions[] = $query->expr()->eq('Foo', $channel);
        }

        if (count($conditions) === 0) {
            return null;
        }

        return $conditions;

        //$query->orWhere($conditions);
        //$where = '((' . join(') OR (', $conditions) . '))';
        //return $query;
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    protected function arrayNotEmptyIntersect($array1, $array2)
    {
        if (empty($array1) && empty($array2)) {
            return [];
        }
        if (empty($array1)) {
            return $array2;
        }
        if (empty($array2)) {
            return $array1;
        }
        return array_intersect($array1, $array2);
    }

    protected function topicListToArray(string $topicList): array
    {
        if (empty($topicList)) {
            return [];
        }
        $topics = array_filter(
            array_map(
                function ($topic) {
                    return ltrim($topic, '#');
                },
                GeneralUtility::trimExplode(',', $topicList)
            ), function ($topic) {
            return !empty($topic);
        }
        );
        return $topics;
    }

    /**
     * @param string $flexFormContent
     * @param string $fieldName
     * @return string|null
     */
    protected function getFlexFormValue($flexFormContent, $fieldName)
    {
        $data = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexFormContent);
        return isset($data[$fieldName]) ? $data[$fieldName] : null;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    protected function getConnectionForTable(string $table): Connection
    {
        return $this->getConnectionPool()->getConnectionForTable($table);
    }

    protected function getQueryBuilderForTable(string $table): QueryBuilder
    {
        return $this->getConnectionPool()->getQueryBuilderForTable($table);
    }

    protected function loadChannels(array $channelIds): array
    {
        $query = $this->getQueryBuilderForTable('tx_socialgrabber_channel');

        return $query
            ->select('uid', 'grabber_class', 'filter_topics')
            ->from('tx_socialgrabber_channel')
            ->where($query->expr()->in('uid', $channelIds))
            ->execute()
            ->fetchAllAssociative();
    }
}
