<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TwinControlDetectorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that no Blade view contains consecutive duplicate inputs with identical placeholders.
     */
    public function test_no_blade_views_contain_duplicate_consecutive_inputs(): void
    {
        $viewsPath = resource_path('views');
        $bladeFiles = File::allFiles($viewsPath);

        $violations = [];

        foreach ($bladeFiles as $file) {
            if ($file->getExtension() !== 'php' || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $content = File::get($file->getRealPath());
            $lines = explode("\n", $content);

            // Check for adjacent or near-adjacent inputs with identical non-empty placeholders
            // without conditional directives in between
            preg_match_all('/<input[^>]+placeholder=["\']([^"\']+)["\'][^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE);

            if (! empty($matches[1]) && count($matches[1]) > 1) {
                for ($i = 0; $i < count($matches[1]) - 1; $i++) {
                    $currPlaceholder = trim($matches[1][$i][0]);
                    $nextPlaceholder = trim($matches[1][$i + 1][0]);

                    if ($currPlaceholder !== '' && $currPlaceholder === $nextPlaceholder) {
                        $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
                        $end = $matches[0][$i + 1][1];
                        $between = substr($content, $start, $end - $start);

                        // If there is no blade conditional or alpine conditional separating them, flag as duplicate
                        if (! preg_match('/(@if|@else|@elseif|@switch|@case|x-if|x-show|v-if)/i', $between)) {
                            // Also check distance between them (within 600 characters)
                            if (strlen($between) < 600) {
                                $relPath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getRealPath());
                                $violations[] = "{$relPath}: duplicate placeholder '{$currPlaceholder}'";
                            }
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Found twin control inputs across blade files:\n".implode("\n", $violations)
        );
    }

    /**
     * Test that the booking wizard and public pages do not render duplicate film-search inputs.
     */
    public function test_public_pages_do_not_render_duplicate_search_inputs(): void
    {
        $routes = ['/', '/film', '/jadwal'];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertOk();
            $html = $response->getContent();
            $this->assertNotEmpty($html);

            // Assert at most 1 film-search input exists in the rendered HTML
            $filmSearchCount = preg_match_all('/id=["\']film-search["\']/i', $html);
            $this->assertLessThanOrEqual(
                1,
                $filmSearchCount,
                "Route '{$route}' rendered {$filmSearchCount} occurrences of id='film-search'."
            );

            // Assert no duplicate adjacent inputs with placeholder 'Cari judul, genre, atau sinopsis...'
            $placeholderCount = preg_match_all('/placeholder=["\']Cari judul, genre, atau sinopsis\.\.\.["\']/i', $html);
            $this->assertLessThanOrEqual(
                1,
                $placeholderCount,
                "Route '{$route}' rendered {$placeholderCount} occurrences of the search placeholder."
            );
        }
    }
}
