<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{

    /**
     * @param string $url
     * @param \DateTimeInterface|null $lastPostDate
     * @return array
     */
    public function grabData($url, $lastPostDate);

}
