<?php

namespace App\Core\Foundation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Base provider every module extends. Auto-loads the module's routes,
 * migrations, views and translations by convention, so a new module
 * only has to add its files in the expected sub-folders - nothing in
 * app/Core or bootstrap/ needs to change.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;

    protected string $modulePath;

    public function __construct($app)
    {
        parent::__construct($app);

        $reflector = new ReflectionClass($this);
        $this->modulePath = dirname($reflector->getFileName(), 2);
        $this->moduleName = basename($this->modulePath);
    }

    public function boot(): void
    {
        $this->loadModuleMigrations();
        $this->loadModuleRoutes();
        $this->loadModuleViews();
        $this->loadModuleTranslations();
    }

    protected function loadModuleMigrations(): void
    {
        $path = $this->modulePath.'/Database/Migrations';

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    /**
     * web.php is loaded as-is (each route declares its own middleware).
     * api.php is always mounted under `api/v1` with the `api` middleware
     * group - Laravel 11+'s minimal skeleton no longer does this
     * automatically the way the old RouteServiceProvider did, and a
     * module forgetting the prefix fails silently with 404s rather than
     * a routing error, so it's enforced once here instead of being
     * left to every module to remember.
     */
    protected function loadModuleRoutes(): void
    {
        $web = $this->modulePath.'/Routes/web.php';
        $api = $this->modulePath.'/Routes/api.php';

        if (is_file($web)) {
            $this->loadRoutesFrom($web);
        }

        if (is_file($api)) {
            Route::prefix('api/v1')->middleware('api')->group($api);
        }
    }

    protected function loadModuleViews(): void
    {
        $path = $this->modulePath.'/Resources/Views';

        if (is_dir($path)) {
            $this->loadViewsFrom($path, Str::kebab($this->moduleName));
        }
    }

    protected function loadModuleTranslations(): void
    {
        $path = $this->modulePath.'/Resources/Lang';

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, Str::kebab($this->moduleName));
        }
    }
}
