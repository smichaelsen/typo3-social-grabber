<?php

namespace Smichaelsen\SocialGrabber\Grabber;

interface TopicFilterableGrabberInterface
{

    /**
     * @param array $topics
     * @return string
     */
    public function getTopicFilterWhereStatement($topics);
}
