<?php

declare(strict_types=1);

namespace MzeAhmed\WpToolKit\Utils;

class Utils
{
    /**
     * Returns a list of files in the specified directory using the RecursiveIteratorIterator object.
     *
     * @param string $directory Path of the directory to list.
     * @param array $extensions File extensions to include.
     * @param array $excludeDirectories Directories to exclude from the listing.
     *
     * @return array|null List of files found in the directory.
     * @since 1.0.5.8
     */
    public static function listFilesWithRecursiveIteratorIterator(
        string $directory,
        array $extensions = ['php'],
        array $excludeDirectories = []
    ): ?array {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                function ($current) use ($excludeDirectories) {
                    foreach ($excludeDirectories as $excluded) {
                        if (strpos($current->getPathname(), DIRECTORY_SEPARATOR . $excluded . DIRECTORY_SEPARATOR) !== false) {
                            return false; // Exclude this directory or file
                        }
                    }

                    return true; // Keep the file or directory
                }
            )
        );

        foreach ($iterator as $file) {
            if (in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getRealPath();
            }
        }

        return $files;
    }

    /**
     * Check if the current environment is development.
     *
     * @return bool
     */
    public static function isDevEnvironment(): bool
    {
        return \defined('WP_ENV') && 'development' === WP_ENV;
    }
}
