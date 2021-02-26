<?php
namespace Smichaelsen\SocialGrabber\Grabber;

use PicoFeed\Parser\Item;
use PicoFeed\Reader\Reader;
use PicoFeed\Reader\UnsupportedFeedFormatException;
use Smichaelsen\SocialGrabber\Grabber\Traits\ExtensionsConfigurationSettable;

/**
 * You can use this grabber directly if you have only one rss source to grab.
 * If you need multiple rss sources simply extend this class and overwrite the $platformIdentifier.
 */
class RssGrabber implements GrabberInterface, HttpCachableGrabberInterface
{

    use ExtensionsConfigurationSettable;

    /**
     * @var string
     */
    protected $etag;

    /**
     * @var string
     */
    protected $lastModified;

    public function grabData(array $channel): array
    {
        $reader = new Reader();
        try {
            $resource = $reader->download($channel['url'], $this->lastModified, $this->etag);
        } catch (UnsupportedFeedFormatException $e) {
            // Picofeed doesn't handle 304 (Not Modified) answers correctly and throws this exception when the feed hasn't changed
            return [];
        }

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

        $lastUpdate = empty($channel['last_post_date']) ? null : \DateTime::createFromFormat('U', $channel['last_post_date']);

        foreach($feed->getItems() as $item) {
            /** @var Item $item */
            if ($lastUpdate instanceof \DateTimeInterface && $item->getDate() <= $lastUpdate) {
                continue;
            }
            $data['posts'][$item->id] = [
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

    /**
     * @param string $etag
     * @return void
     */
    public function setEtag($etag)
    {
        $this->etag = $etag;
    }

    /**
     * @param string $lastModified
     * @return void
     */
    public function setLastModified($lastModified)
    {
        $this->lastModified = $lastModified;
    }
}
