<?php
namespace Smichaelsen\SocialGrabber\Domain\Repository;

use Smichaelsen\SocialGrabber\Domain\Model\Post;
use TYPO3\CMS\Extbase\Persistence\Generic\Query;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class PostRepository extends Repository
{

    /**
     *
     */
    public function initializeObject()
    {
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @return array|QueryResultInterface|Post[]
     */
    public function findLatestPosts()
    {
        $query = $this->createQuery();
        return $query->setOrderings(['publicationDate' => Query::ORDER_DESCENDING])->setLimit(3)->execute();
    }

}
