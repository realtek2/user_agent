<?php

namespace App\Providers;

use App\Observers\TelegramUserObserver;
use App\TelegramUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Jenssegers\Date\Date;
use TCG\Voyager\Facades\Voyager;
use Validator;
use App\Validators\RestValidator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::resolver(function($translator, $data, $rules, $messages){
            return new RestValidator($translator, $data, $rules, $messages);
        });

        Voyager::addAction(\App\Actions\Mailing::class);
        Date::setlocale( config('app.locale') );
        //Schema::defaultStringLength(191);
        TelegramUser::observe(TelegramUserObserver::class);
    }
}
