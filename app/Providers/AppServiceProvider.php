<?php

namespace App\Providers;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use App\Residents\ResidentLookupService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentTemplateRegistry::class, fn () => new DocumentTemplateRegistry(
            templatesPath: config('documents.templates_path'),
            templates: config('documents.types', []),
        ));

        $this->app->singleton(DocumentGenerator::class);
        $this->app->singleton(ResidentLookupService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Force HTTPS in production (needed for Railway reverse proxy)
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
