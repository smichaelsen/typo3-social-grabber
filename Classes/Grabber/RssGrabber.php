<?php
namespace Smichaelsen\SocialGrabber\Grabber;

/**
 * You can use this grabber directly if you have only one rss source to grab.
 * If you need multiple rss sources simply extend this class and overwrite the $platformIdentifier.
 */
class RssGrabber extends AbstractGrabber
{

    /**
     * @var string
     */
    protected $platformIdentifier = 'rss';

    /**
     * @param \DateTimeInterface $lastUpdate
     * @return array
     */
    public function grabData(\DateTimeInterface $lastUpdate)
    {

    }
}
