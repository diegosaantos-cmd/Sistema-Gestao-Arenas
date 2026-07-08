<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientNotification extends Model
{
    protected $table = 'client_notifications';

    protected $fillable = [
        'user_id', 'arena_id', 'sent_by', 'title', 'body', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Cria uma notificação para o cliente dono de uma reserva.
     */
    public static function paraReserva(Booking $booking, string $title, string $body, ?int $sentBy = null): void
    {
        $booking->loadMissing('client', 'court');
        $userId = $booking->client?->user_id;

        if (! $userId) {
            return;
        }

        static::create([
            'user_id'  => $userId,
            'arena_id' => $booking->court?->arena_id,
            'sent_by'  => $sentBy,
            'title'    => $title,
            'body'     => $body,
        ]);
    }
}
