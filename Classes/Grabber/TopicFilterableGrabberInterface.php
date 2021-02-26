<?php
namespace Smichaelsen\SocialGrabber\Grabber;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

interface TopicFilterableGrabberInterface
{
    public function getTopicFilterWhereStatement(array $topics, QueryBuilder $query): ?QueryBuilder;
}
