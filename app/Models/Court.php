<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Court extends Model
{
    use SoftDeletes;

    protected $table = 'courts';

    // courts tem created_at e updated_at -> o Eloquent gerencia os dois.

    /**
     * Esportes disponíveis (espelha o enum de court_sports).
     * Fonte única: usada para renderizar o formulário e validar a entrada.
     * Para adicionar um esporte, inclua aqui E no enum da migration court_sports.
     */
    public const SPORTS = [
        'beach_tennis' => 'Beach Tennis',
        'beach_volleyball' => 'Vôlei de Praia',
        'indoor_volleyball' => 'Vôlei de Quadra',
        'five_a_side_football' => 'Futebol Society',
        'futsal' => 'Futsal',
        'tennis' => 'Tênis',
    ];

    protected $fillable = [
        'arena_id',
        'name',
        'description',
        'hourly_rate',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'hourly_rate' => 'decimal:2',
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

    public function sports()
    {
        return $this->hasMany(CourtSport::class);
    }
}
