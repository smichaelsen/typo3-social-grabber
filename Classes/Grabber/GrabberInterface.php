<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface GrabberInterface
{
    public function setExtensionConfiguration(array $extensionConfiguration): void;

    public function grabData(array $channel): array;
}
