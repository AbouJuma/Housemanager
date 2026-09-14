<?php

namespace App\Core\Foundation;

use Illuminate\Contracts\Foundation\Application;

/**
 * Discovers every app/Modules/{Name}/Providers/{Name}ServiceProvider.php
 * and registers it with the container. Adding a module is dropping its
 * folder in place - nothing here or in bootstrap/ needs editing.
 */
final class ModuleManager
{
    public static function register(Application $app): void
    {
        foreach (static::discover(app_path('Modules')) as $providerClass) {
            if (class_exists($providerClass)) {
                $app->register($providerClass);
            }
        }
    }

    /**
     * @return array<int, string> fully-qualified module ServiceProvider class names
     */
    public static function discover(string $modulesPath): array
    {
        if (! is_dir($modulesPath)) {
            return [];
        }

        $providers = [];

        foreach (scandir($modulesPath) as $moduleName) {
            if ($moduleName === '.' || $moduleName === '..') {
                continue;
            }

            $providerFile = $modulesPath.DIRECTORY_SEPARATOR.$moduleName
                .DIRECTORY_SEPARATOR.'Providers'
                .DIRECTORY_SEPARATOR.$moduleName.'ServiceProvider.php';

            if (is_file($providerFile)) {
                $providers[] = "App\\Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider";
            }
        }

        return $providers;
    }
}
