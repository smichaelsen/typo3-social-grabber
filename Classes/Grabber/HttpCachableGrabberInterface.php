<?php

namespace Smichaelsen\SocialGrabber\Grabber;

interface HttpCachableGrabberInterface
{

    /**
     * @param string $etag
     */
    public function setEtag($etag);

    /**
     * @param string $lastModified
     */
    public function setLastModified($lastModified);
}
