<?php

namespace Smichaelsen\SocialGrabber\Grabber;

interface UpdatablePostsGrabberInterface
{

    /**
     * @param array $posts
     * @return array
     */
    public function updatePosts($posts);
}
