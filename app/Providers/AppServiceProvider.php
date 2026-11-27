<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use App\Models\Service;
use App\Repositories\ReportRepositoryInterface;
use App\Repositories\ReportRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Nhật ký hoạt động: gom thay đổi cũ → mới trong một request (RecordActivity ghi một dòng)
        $this->app->scoped(\App\Services\Activity\ActivityRecorder::class);
        view()->composer('*',function($view) {
		    $getAllService = Service::orderBy('id','ASC')->get();
            $view->with(compact('getAllService'));
        });

        $this->app->bind(
            ReportRepositoryInterface::class,
            ReportRepository::class
        );

        // Claude API (trợ lý AI theo phòng ban). Không có key thì dùng credential mặc định của SDK.
        $this->app->singleton(\Anthropic\Client::class, fn () => new \Anthropic\Client(
            apiKey: config('claude.api_key') ?: null,
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Theo dõi thay đổi các model chính cho nhật ký hoạt động (App\Services\Activity\TrackedModels)
        foreach (array_keys(\App\Services\Activity\TrackedModels::config()) as $model) {
            $model::observe(\App\Services\Activity\ModelChangeObserver::class);
        }

        try {
            Storage::extend('google', function ($app, $config) {
                $options = [];

                if (!empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (!empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new \Google\Service\Drive($client);
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter($service, $config['folder'] ?? '/', $options);
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // return $e->getMessage();
        }
    }
}
