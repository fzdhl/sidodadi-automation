<?php

namespace App\Providers;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use App\Residents\ResidentLookupService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

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
    }
}
