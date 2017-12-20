<?php
namespace Smichaelsen\SocialGrabber\Grabber\Traits;

trait ExtensionsConfigurationSettable
{

    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * @param array $extensionConfiguration
     * @return void
     */
    public function setExtensionConfiguration($extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

}
