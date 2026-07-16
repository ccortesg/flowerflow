<?php

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class SubmissionContentSanitizer
{
    public function sanitize(string $html): string
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(['https', 'mailto'])
            ->allowRelativeLinks(false)
            ->allowMediaSchemes([])
            ->allowRelativeMedias(false)
            ->withMaxInputLength(20000);

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
