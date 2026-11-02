<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class CleanDesignArchitectureTest extends TestCase
{
    /**
     * Architectural test ensuring no UI emojis exist in Blade templates.
     * All icons must use functional 1.5px stroke SVG icons.
     */
    public function test_no_ui_emojis_in_blade_views(): void
    {
        $viewsPath = resource_path('views');
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $violatingFiles = [];

        // Comprehensive pictographic, emoji, and dingbat pattern
        $emojiPattern = '/[\p{Extended_Pictographic}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

        foreach ($files as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());

            // Skip ticket during construction if needed, but Bagian 10 will overhaul it cleanly
            $content = file_get_contents($file->getPathname());

            // Remove blade comments to avoid false alarms in developer notes
            $cleanContent = preg_replace('/{{--.*?--}}/s', '', $content);

            if (preg_match_all($emojiPattern, $cleanContent, $matches)) {
                $violatingFiles[$relativePath] = array_unique($matches[0]);
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Found UI emojis in blade views. Replace them with 1.5px stroke SVG icons: '.json_encode($violatingFiles)
        );
    }

    /**
     * Proves that the architectural emoji detector strictly catches newly introduced emojis or pictograms.
     */
    public function test_architectural_detector_catches_newly_introduced_emojis(): void
    {
        $emojiPattern = '/[\p{Extended_Pictographic}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u';

        $testCasesWithEmojis = [
            '<button>⚡ Pasangkan Semua</button>',
            '<span>✨ Berhasil disimpan</span>',
            '<p>🎉 Selamat datang!</p>',
            '<button>✕</button>',
        ];

        foreach ($testCasesWithEmojis as $snippet) {
            $matched = preg_match_all($emojiPattern, $snippet, $matches);
            $this->assertGreaterThan(0, $matched, "Expected detector to catch emoji in snippet: {$snippet}");
        }

        $cleanSnippet = '<button><svg class="size-4"></svg> Pasangkan Semua</button>';
        $this->assertSame(0, preg_match_all($emojiPattern, $cleanSnippet, $matches), 'Expected no match on clean SVG button');
    }

    /**
     * Architectural test ensuring no colored glow shadows exist in Blade templates.
     * Only neutral standard shadows (shadow-sm, shadow-md, shadow-lg, shadow-xl) or 1px borders are allowed.
     */
    public function test_no_colored_glow_shadows_in_views(): void
    {
        $viewsPath = resource_path('views');
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsPath));
        $violatingFiles = [];

        // Matches colored shadows such as shadow-primary, shadow-amber-500/50, shadow-emerald-500
        $coloredShadowPattern = '/\bshadow-(?:primary|amber|red|green|blue|purple|emerald|rose|yellow|indigo|cyan)-[a-z0-9\/]+/i';

        foreach ($files as $file) {
            if ($file->isDir() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $relativePath = str_replace($viewsPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
            $content = file_get_contents($file->getPathname());
            $cleanContent = preg_replace('/{{--.*?--}}/s', '', $content);

            if (preg_match_all($coloredShadowPattern, $cleanContent, $matches)) {
                $violatingFiles[$relativePath] = array_unique($matches[0]);
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Found colored glow shadows in views. Clean design forbids colored glow shadows: '.json_encode($violatingFiles)
        );
    }

    /**
     * Architectural test ensuring mobile drawer and overlay backdrops do not use backdrop-blur
     * which causes containing block traps and mobile GPU compositing lag.
     */
    public function test_mobile_overlays_do_not_use_backdrop_blur(): void
    {
        $viewsPath = resource_path('views');
        $checkFiles = [
            'components/site/header.blade.php',
            'components/in-app-dialogs.blade.php',
            'layouts/public.blade.php',
        ];

        $violatingFiles = [];

        foreach ($checkFiles as $file) {
            $path = $viewsPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $file);
            if (! file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);

            // Drawer backdrop or overlay containing backdrop-blur without desktop breakpoint
            if (preg_match('/(?:drawer|mobile-menu|fixed\s+inset-0)[^>]*?\bbackdrop-blur\b/i', $content)) {
                $violatingFiles[] = $file;
            }
        }

        $this->assertEmpty(
            $violatingFiles,
            'Mobile drawer or overlay found using backdrop-blur without desktop constraint: '.implode(', ', $violatingFiles)
        );
    }
}
