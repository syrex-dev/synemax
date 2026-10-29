<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class NoBrowserDialogSafetyGuardTest extends TestCase
{
    /**
     * Memastikan tidak ada panggilan native browser pop-up seperti alert(), confirm(),
     * prompt(), beforeunload, Notification.requestPermission, atau inline confirm()
     * di seluruh resources/ dan public/js.
     */
    public function test_no_forbidden_browser_dialogs_in_resources_and_public_js(): void
    {
        $directories = [
            resource_path('views'),
            resource_path('js'),
            public_path('js'),
        ];

        $forbiddenPatterns = [
            '/\balert\s*\(/i' => 'Native alert() dialog is forbidden',
            '/\bconfirm\s*\(/i' => 'Native confirm() dialog is forbidden',
            '/\bprompt\s*\(/i' => 'Native prompt() dialog is forbidden',
            '/\bwindow\.(alert|confirm|prompt)\s*\(/i' => 'Native window dialog is forbidden',
            '/onsubmit\s*=\s*["\'].*?\breturn\s+confirm\s*\(/i' => 'onsubmit return confirm() pattern is forbidden',
            '/onclick\s*=\s*["\'].*?\bconfirm\s*\(/i' => 'onclick confirm() pattern is forbidden',
            '/\bbeforeunload\b/i' => 'beforeunload native dialog prompt is forbidden',
            '/Notification\.requestPermission/i' => 'Native Notification.requestPermission() prompt is forbidden',
        ];

        $allowedFileExceptions = [
            // No exceptions currently needed
        ];

        $violations = [];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    continue;
                }

                $filename = $file->getFilename();
                $pathname = str_replace('\\', '/', $file->getPathname());

                // Skip vendor, test artifacts, or node_modules
                if (
                    str_contains($pathname, '/vendor/')
                    || str_contains($pathname, '/node_modules/')
                    || str_contains($pathname, '/.git/')
                ) {
                    continue;
                }

                // Check only view and script extensions
                $ext = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
                if (! in_array($ext, ['php', 'js', 'blade'], true) && ! str_ends_with($pathname, '.blade.php')) {
                    continue;
                }

                $content = file_get_contents($file->getPathname());
                $lines = explode("\n", $content);

                foreach ($lines as $lineNum => $line) {
                    // Check if line contains comment or in-app dialog definition itself
                    if (str_contains($line, 'confirmText') || str_contains($line, 'closeConfirm') || str_contains($line, 'data-confirm')) {
                        continue;
                    }

                    foreach ($forbiddenPatterns as $pattern => $message) {
                        if (preg_match($pattern, $line)) {
                            $violations[] = sprintf(
                                '%s:%d - %s [Line: %s]',
                                $pathname,
                                $lineNum + 1,
                                $message,
                                trim($line)
                            );
                        }
                    }
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Ditemukan pelanggaran native browser dialog:\n".implode("\n", $violations)
        );
    }
}
