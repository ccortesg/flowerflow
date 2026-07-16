<?php

namespace Tests\Unit;

use App\Services\SubmissionContentSanitizer;
use PHPUnit\Framework\TestCase;

class SubmissionContentSanitizerTest extends TestCase
{
    public function test_it_keeps_safe_formatting_and_drops_active_content(): void
    {
        $result = (new SubmissionContentSanitizer)->sanitize(
            '<h2>Título</h2><p onclick="evil()">Texto <strong>útil</strong></p><img src="data:text/html;base64,aaa"><script>alert(1)</script>'
        );

        $this->assertStringContainsString('<h2>Título</h2>', $result);
        $this->assertStringContainsString('<strong>útil</strong>', $result);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringNotContainsString('data:', $result);
    }
}
