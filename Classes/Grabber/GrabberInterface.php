<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{

    /**
     * @return string
     */
    public function getPlatformIdentifier();

    /**
     * @param \DateTimeInterface $lastUpdate
     * @return array
     */
    public function grabData(\DateTimeInterface $lastUpdate);

}
