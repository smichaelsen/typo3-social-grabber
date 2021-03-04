<?php

namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{

    /**
     * @param array $extensionConfiguration
     */
    public function setExtensionConfiguration($extensionConfiguration);

    /**
     * @param array $channel
     * @return array
     */
    public function grabData($channel);
}
