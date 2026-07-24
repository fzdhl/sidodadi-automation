<?php

namespace App\Providers;

use App\Documents\DocumentGenerator;
use App\Documents\DocumentTemplateRegistry;
use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
