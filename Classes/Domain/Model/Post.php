<?php
namespace Smichaelsen\SocialGrabber\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Post extends AbstractEntity
{

    /**
     * @var string
     */
    protected $author;

    /**
     * @var \DateTime
     */
    protected $publicationDate;

    /**
     * @var string
     */
    protected $teaser;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $url;

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return \DateTime
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * @return string
     */
    public function getTeaser()
    {
        return $this->teaser;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

}
