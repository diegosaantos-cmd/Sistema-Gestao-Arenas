<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'date_of_birth',
    ];

    public function user()
    {
        // withTrashed: quando o cliente encerra a conta (soft delete do User),
        // as reservas dele continuam aparecendo no histórico da arena COM o nome.
        // Espelha o que Booking::client() e Booking::court() já fazem.
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Arenas que o cliente marcou como favoritas, das mais recentes para as
     * mais antigas. Arenas excluídas (soft delete) não aparecem: o escopo
     * global do model Arena as filtra.
     */
    public function favoritas()
    {
        return $this->belongsToMany(Arena::class, 'arena_favorites', 'client_id', 'arena_id')
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc');
    }
}
