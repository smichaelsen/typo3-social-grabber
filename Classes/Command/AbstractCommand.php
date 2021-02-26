<?php
namespace Smichaelsen\SocialGrabber\Command;

use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCommand extends Command
{
    protected array $extensionConfiguration;

    public function __construct(string $name = null)
    {
        parent::__construct($name);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('social_grabber');
    }

    protected function determineUpdateProbability(int $publication_date): float
    {
        $timeStamp = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp');
        $daysSincePublication = max(1, ($timeStamp - $publication_date) / (24*60*60));
        $exponent = 0.9;
        // The probability decreases with each day the publication day has passed.
        // For $exponent 0.9 that means the following update probabilities:
        // 1 day: 100%
        // 2 days: 54%
        // 5 days: 23%
        // 10 days: 13%
        // 100 days: 2%
        // 365 days: 0.5%
        // 730 days: 0.3%
        // I.e. a post that is one year old has a 1/200 chance to get updated within one run of the updatePostsCommand.
        // see the graph for this function: https://www.desmos.com/calculator/n0ua1nioff
        return (float) 1 / pow($daysSincePublication, $exponent);
    }

    protected function addFlashMessage(string $title, string $message, int $severity): void
    {
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $flashMessageService->getMessageQueueByIdentifier()->enqueue(new FlashMessage($message, $title, $severity));
    }

    protected function loadChannels(): ?array
    {
        $query = $this->getQueryBuilderForTable('tx_socialgrabber_channel');
        return $query
            ->selectLiteral('channel.uid',  'channel.pid', 'channel.grabber_class', 'channel.url', 'channel.feed_etag', 'channel.feed_last_modified', 'MAX(post.publication_date) as last_post_date', 'MAX(post.post_identifier) as last_post_identifier')
            ->from('tx_socialgrabber_channel', 'channel')
            ->leftJoin(
                'channel',
                'tx_socialgrabber_domain_model_post',
                'post',
                $query->expr()->eq('post.channel', $query->quoteIdentifier('channel.uid'))
            )
            ->execute()
            ->fetchAllAssociative();
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
}
