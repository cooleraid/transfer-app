<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Library\Services\TransactionService;
class TransactionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Library\Services\TransactionService', function ($app) {
            return new TransactionService();
          });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
