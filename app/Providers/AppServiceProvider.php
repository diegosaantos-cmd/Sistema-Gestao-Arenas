<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\UserNotification;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        // Contador de notificações não lidas do cliente logado, para o sino na navbar.
        View::composer('layouts.main', function ($view) {
            $notifUnreadCount = 0;

            $user = auth()->user();
            if ($user) {
                $notifUnreadCount = UserNotification::where('user_id', $user->id)
                    ->whereNull('read_at')->count();
            }

            $view->with('notifUnreadCount', $notifUnreadCount);
        });
    }
}
