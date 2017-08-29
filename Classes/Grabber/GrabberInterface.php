<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{

    /**
     * @param array $channel
     * @return array
     */
    public function grabData($channel);

}
