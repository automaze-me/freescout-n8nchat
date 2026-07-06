<?php

namespace Modules\N8nChat\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

if (!defined('N8NCHAT_MODULE')) {
    define('N8NCHAT_MODULE', 'n8nchat');
}

class N8nChatServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
        $this->registerSettings();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {

    }

    /**
     * Register settings section and Eventy filters.
     */
    public function registerSettings()
    {
        // Add the section to Manage → Settings.
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['n8nchat'] = ['title' => __('n8n Chat'), 'icon' => 'comment', 'order' => 650];
            return $sections;
        }, 20, 1);

        // Render our view for the section.
        \Eventy::addFilter('settings.view', function ($view, $section) {
            return $section === 'n8nchat' ? 'n8nchat::settings' : $view;
        }, 20, 2);

        // Provide the option values (and defaults) for the section.
        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section !== 'n8nchat') {
                return $settings;
            }
            return [
                'n8nchat.enabled'           => \Option::get('n8nchat.enabled', config('n8nchat.options.enabled.default')),
                'n8nchat.webhook_url'       => \Option::get('n8nchat.webhook_url', config('n8nchat.options.webhook_url.default')),
                'n8nchat.shared_secret'     => \Option::get('n8nchat.shared_secret', config('n8nchat.options.shared_secret.default')),
                'n8nchat.secret_header'     => \Option::get('n8nchat.secret_header', config('n8nchat.options.secret_header.default')),
                'n8nchat.title'             => \Option::get('n8nchat.title', config('n8nchat.options.title.default')),
                'n8nchat.greeting'          => \Option::get('n8nchat.greeting', config('n8nchat.options.greeting.default')),
                'n8nchat.input_placeholder' => \Option::get('n8nchat.input_placeholder', config('n8nchat.options.input_placeholder.default')),
            ];
        }, 20, 2);

        // Validation: webhook_url must be a URL when provided.
        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section !== 'n8nchat') {
                return $params;
            }
            $params['validator_rules'] = [
                'settings.n8nchat\\.webhook_url' => 'nullable|url',
            ];
            return $params;
        }, 20, 2);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('n8nchat.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'n8nchat'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/n8nchat');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/n8nchat';
        }, \Config::get('view.paths')), [$sourcePath]), 'n8nchat');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
