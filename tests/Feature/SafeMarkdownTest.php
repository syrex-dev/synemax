<?php

namespace Tests\Feature;

use App\Support\SafeMarkdown;
use Tests\TestCase;

class SafeMarkdownTest extends TestCase
{
    public function test_null_or_empty_markdown_returns_empty_html_string(): void
    {
        $this->assertSame('', (string) SafeMarkdown::toHtml(null));
        $this->assertSame('', (string) SafeMarkdown::toHtml(''));
        $this->assertSame('', (string) SafeMarkdown::toHtml("   \n\t  "));
    }

    public function test_standard_markdown_syntax_renders_correctly(): void
    {
        $input = "## Judul Bagian\n\nIni adalah **teks tebal** dan *teks miring*.\n\n- Poin satu\n- Poin dua\n\n> Kutipan menarik";
        $html = (string) SafeMarkdown::toHtml($input);

        $this->assertStringContainsString('<h2>Judul Bagian</h2>', $html);
        $this->assertStringContainsString('<strong>teks tebal</strong>', $html);
        $this->assertStringContainsString('<em>teks miring</em>', $html);
        $this->assertStringContainsString('<li>Poin satu</li>', $html);
        $this->assertStringContainsString('<li>Poin dua</li>', $html);
        $this->assertStringContainsString('<blockquote>', $html);
        $this->assertStringContainsString('Kutipan menarik', $html);
    }

    public function test_xss_raw_html_tags_are_stripped(): void
    {
        $malicious = "<script>alert('XSS')</script>\n\nParagraf normal markdown\n\n<img src=x onerror=\"alert('XSS')\">";
        $html = (string) SafeMarkdown::toHtml($malicious);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert(', $html);
        $this->assertStringNotContainsString('onerror=', $html);
        $this->assertStringContainsString('Paragraf normal markdown', $html);
    }

    public function test_unsafe_schemes_in_markdown_links_are_neutralized(): void
    {
        $maliciousLink = "[Klik saya](javascript:alert('pwned'))";
        $html = (string) SafeMarkdown::toHtml($maliciousLink);

        $this->assertStringNotContainsString('href="javascript:', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_external_links_have_rel_and_target_attributes_without_unsafe_behavior(): void
    {
        $externalMarkdown = '[Kunjungi Web Luar](https://example.com/artikel)';
        $html = (string) SafeMarkdown::toHtml($externalMarkdown);

        $this->assertStringContainsString('href="https://example.com/artikel"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="nofollow noopener noreferrer"', $html);
    }
}
