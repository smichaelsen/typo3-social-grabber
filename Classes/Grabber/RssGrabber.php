<?php
namespace Smichaelsen\SocialGrabber\Grabber;

use PicoFeed\Parser\Item;
use PicoFeed\Reader\Reader;

/**
 * You can use this grabber directly if you have only one rss source to grab.
 * If you need multiple rss sources simply extend this class and overwrite the $platformIdentifier.
 */
class RssGrabber extends AbstractGrabber
{

    /**
     * @var string
     */
    protected $platformIdentifier = 'rss';

    /**
     * @param string $url
     * @param \DateTimeInterface $lastUpdate
     * @param null $feedEtag
     * @param \DateTimeInterface $feedLastUpdate
     * @return array
     * @throws \PicoFeed\Parser\MalformedXmlException
     * @throws \PicoFeed\Reader\UnsupportedFeedFormatException
     */
    public function grabData($url, \DateTimeInterface $lastUpdate, $feedEtag = null, \DateTimeInterface $feedLastUpdate = null)
    {
        $reader = new Reader();
        $resource = $reader->download($url, $feedLastUpdate, $feedEtag);

        $feed = $reader->getParser(
            $resource->getUrl(),
            $resource->getContent(),
            $resource->getEncoding()
        )->execute();

        $data = [
            'posts' => [],
            'feed_etag' => $resource->getEtag(),
            'feed_last_modified' => $resource->getLastModified(),
        ];

        foreach($feed->getItems() as $item) {
            /** @var Item $item */
            if ($item->getDate() <= $lastUpdate) {
                continue;
            }
            $data['posts'][] = [
                'post_identifier' => $item->id,
                'publication_date' => $item->getDate()->format('U'),
                'url' => $item->getUrl(),
                'title' => $item->getTitle(),
                'teaser' => $item->getContent(),
                'author' => $item->getAuthor(),
            ];
        }

        return $data;

    }
}
