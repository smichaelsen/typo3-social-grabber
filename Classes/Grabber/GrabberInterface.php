<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{

    /**
     * @param string $url
     * @param \DateTimeInterface $lastPostDate
     * @return array
     */
    public function grabData($url, \DateTimeInterface $lastPostDate);

}
