<?php
namespace Smichaelsen\SocialGrabber\Grabber\Traits;

trait ExtensionsConfigurationSettable
{
    protected array $extensionConfiguration;

    public function setExtensionConfiguration(array $extensionConfiguration): void
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }
}
