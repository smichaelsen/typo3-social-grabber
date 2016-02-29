<?php
namespace Smichaelsen\SocialGrabber\Grabber;

abstract class AbstractGrabber implements GrabberInterface
{

    /**
     * @var string
     */
    protected $platformIdentifier = 'OVERWRITE_THIS';

    /**
     * @return string
     */
    public function getPlatformIdentifier(){
        return $this->platformIdentifier;
    }

}
