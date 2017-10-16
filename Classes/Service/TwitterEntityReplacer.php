<?php

namespace Smichaelsen\SocialGrabber\Service;

class TwitterEntityReplacer
{

    /**
     * @param string $text
     * @param \stdClass $entities
     * @return string
     */
    public static function replaceEntities($text, $entities)
    {
        $entityReplacements = [];
        if (isset($entities->urls) && is_array($entities->urls)) {
            foreach ($entities->urls as $url) {
                $entityReplacements[] = [
                    'start' => $url->indices[0],
                    'end' => $url->indices[1],
                    'replacement' => sprintf(
                        '<a href="%s" target="_blank">%s</a>',
                        $url->expanded_url,
                        $url->display_url
                    ),
                ];
            }
        }
        if (isset($entities->media) && is_array($entities->media)) {
            foreach ($entities->media as $mediaEntity) {
                $entityReplacements[] = [
                    'start' => $mediaEntity->indices[0],
                    'end' => $mediaEntity->indices[1],
                    'replacement' => '',
                ];
            }
        }
        if (isset($entities->user_mentions) && is_array($entities->user_mentions)) {
            foreach ($entities->user_mentions as $mention) {
                $entityReplacements[] = [
                    'start' => $mention->indices[0],
                    'end' => $mention->indices[1],
                    'replacement' => sprintf(
                        '<a href="https://twitter.com/%1$s" title="%2$s" target="_blank">@%1$s</a>',
                        $mention->screen_name,
                        $mention->name
                    ),
                ];
            }
        }
        if (isset($entities->hashtags) && is_array($entities->hashtags)) {
            foreach ($entities->hashtags as $hashtag) {
                $entityReplacements[] = [
                    'start' => $hashtag->indices[0],
                    'end' => $hashtag->indices[1],
                    'replacement' => sprintf(
                        '<a href="https://twitter.com/hashtag/%1$s" target="_blank">#%1$s</a>',
                        $hashtag->text
                    ),
                ];
            }
        }
        // reverse replacements because they have to be executed from end to beginning
        usort($entityReplacements, function ($a, $b) {
            return ($b['start'] - $a['start']);
        });
        foreach ($entityReplacements as $entityReplacement) {
            $text = self::mb_substr_replace($text, $entityReplacement['replacement'], $entityReplacement['start'], $entityReplacement['end'] - 1);
        }
        return $text;
    }

    /**
     * See http://php.net/manual/de/ref.mbstring.php#94220
     *
     * @param string $input
     * @param string $replace
     * @param int $posOpen
     * @param int $posClose
     * @return string
     */
    protected static function mb_substr_replace($input, $replace, $posOpen, $posClose)
    {
        return mb_substr($input, 0, $posOpen) . $replace . mb_substr($input, $posClose + 1);
    }
}
