<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

class SafeMarkdown
{
    /**
     * Converts markdown to clean, safe HTML without arbitrary script execution or unsafe links.
     * External links automatically receive target="_blank" and rel="nofollow noopener noreferrer".
     */
    public static function toHtml(?string $markdown): HtmlString
    {
        if (blank($markdown)) {
            return new HtmlString('');
        }

        $appUrl = (string) config('app.url', 'http://localhost');
        $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';

        $options = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'external_link' => [
                'internal_hosts' => array_values(array_unique(['localhost', '127.0.0.1', $host])),
                'open_in_new_window' => true,
                'nofollow' => 'all',
                'noreferrer' => 'all',
                'noopener' => 'all',
            ],
        ];

        $html = Str::markdown($markdown, $options, [
            new ExternalLinkExtension,
        ]);

        return new HtmlString($html);
    }
}
