<?php

namespace Tests\Feature;

use Tests\TestCase;

class NoNativeSelectTagTest extends TestCase
{
    /**
     * Architectural test ensuring no native <select> tag is used in blade templates.
     * All dropdowns must use <x-ui.select> instead.
     */
    public function test_no_native_select_tags_exist_in_views(): void
    {
        $viewsPath = resource_path('views');
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($viewsPath));
        $violatingFiles = [];

        foreach ($files as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // Exclude the custom select component itself if it were to contain a fallback
            $relativePath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            if ($relativePath === 'components'.DIRECTORY_SEPARATOR.'ui'.DIRECTORY_SEPARATOR.'select.blade.php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if (preg_match('/<select[\s>]/i', $content)) {
                $violatingFiles[] = $relativePath;
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Found native <select> tags in views. Replace them with <x-ui.select>: '.implode(', ', $violatingFiles)
        );
    }
}
