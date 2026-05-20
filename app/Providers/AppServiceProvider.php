<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
        * Register any application services.
        */
    public function register(): void
    {
        //
    }

    /**
        * Bootstrap any application services.
        */
    public function boot(): void
    {
        Gate::define('system_admin', function ($user) {
            return $user->role === 'system_admin';
        });
        // ユーザーが「ログイン」した瞬間に、この中の処理が自動で走ります
        Event::listen(function (Login $event) {
            activity()
                ->causedBy($event->user) // 「誰が（ログインしたユーザー）」
                ->log('システムにログインしました'); // 「何をしたか」
        });
        // 権限設定
        Gate::define('admin', function ($user) {
            return in_array($user->role, ['system_admin', 'admin']);
        });
    }
}