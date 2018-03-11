<?php
/**
 * Registering our GitHub API class
 * 
 * @author test99555672 <test99555672@gmail.com>
 * @version 1.0
 */

namespace App\Providers;

use Auth;
use App\Models\Git\Git;
use Illuminate\Support\ServiceProvider;

class GitApiProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('App\Models\Git\Git', function($app) {
            $api = new Git(Auth::user(), true);
            
            return $api;
        });
    }
}
