<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;

class ClientNotificationController extends Controller
{
    /**
     * Página com todas as notificações do cliente logado.
     */
    public function index()
    {
        $notifications = UserNotification::with('arena')
            ->where('user_id', auth()->id())
            ->latest()
            ->paginate(20);

        return view('client.notifications.index', compact('notifications'));
    }

    /**
     * Abre uma notificação (e marca como lida).
     */
    public function show(UserNotification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        $notification->load('arena');

        return view('client.notifications.show', compact('notification'));
    }

    /**
     * Marca todas as notificações não lidas do cliente logado como lidas.
     */
    public function readAll()
    {
        UserNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
