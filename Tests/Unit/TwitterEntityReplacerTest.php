<?php

namespace Smichaelsen\SocialGrabber\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Smichaelsen\SocialGrabber\Service\Twitter\TwitterEntityReplacer;

class TwitterEntityReplacerTest extends TestCase
{

    /**
     * @test
     */
    public function returnsOriginalTextIfThereAreNoEntitiesToReplace()
    {
        $tweet = 'LIMONADA 🗿';
        $this->assertEquals($tweet, TwitterEntityReplacer::replaceEntities($tweet, new \stdClass()));
    }

    /**
     * @dataProvider provideTweets
     * @param \stdClass $tweet
     * @param string $expected
     */
    public function testTweets($tweet, $expected)
    {
        $this->assertEquals($expected, TwitterEntityReplacer::replaceEntities($tweet->text, $tweet->entities));
    }

    /**
     * @return array
     */
    public function provideTweets()
    {
        return [
            [
                json_decode(file_get_contents(__DIR__ . '/../Tweets/919893647172210689.json')),
                'Wir haben die <a href="https://twitter.com/hashtag/LizenzzumSchützen" target="_blank">#LizenzzumSchützen</a> 🙂 Ab morgen finden Sie uns auf der <a href="https://twitter.com/AplusATradeFair" title="A+A" target="_blank">@AplusATradeFair</a> in Halle 10 an einem garantier… <a href="https://twitter.com/i/web/status/919893647172210689" target="_blank">twitter.com/i/web/status/9…</a>',
            ],
            [
                json_decode(file_get_contents(__DIR__ . '/../Tweets/918059958108860416.json')),
                'Wie kann Betriebliches Gesundheitsmanagement aussehen? : <a href="http://bit.ly/2xyBzyo" target="_blank">bit.ly/2xyBzyo</a> <a href="https://twitter.com/DraegerNews" title="Dräger" target="_blank">@DraegerNews</a> <a href="https://twitter.com/hashtag/Prävention" target="_blank">#Prävention</a>',
            ],
        ];
    }

}
