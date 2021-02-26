<?php
namespace Smichaelsen\SocialGrabber\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Post extends AbstractEntity
{

    protected string $author;

    protected \DateTime $publicationDate;

    /**
     * @var string
     */
    protected string $teaser;

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var string
     */
    protected string $url;

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getPublicationDate(): \DateTime
    {
        return $this->publicationDate;
    }

    public function getTeaser(): string
    {
        return $this->teaser;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

}
