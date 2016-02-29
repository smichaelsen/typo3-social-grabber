<?php
namespace Smichaelsen\SocialGrabber\Grabber;

interface HttpCachableGrabberInterface
{

    /**
     * @param string $etag
     * @return void
     */
    public function setEtag($etag);

    /**
     * @param string $lastModified
     * @return void
     */
    public function setLastModified($lastModified);

}
