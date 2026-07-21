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

    /**
     * Exclui a quadra DESLIGANDO-A.
     *
     * Excluída não é "ativa". Antes o soft delete deixava `active = true`, e o
     * registro passava a afirmar duas coisas contraditórias: que a quadra não
     * existe mais e que está em operação. Na prática ela continuava aparecendo
     * em qualquer consulta por quadra ativa que não filtrasse o soft delete.
     *
     * Único caminho para excluir quadra — usado na exclusão avulsa e quando a
     * arena inteira é excluída —, para a regra não precisar ser repetida.
     */
    public function excluirDesativando(): void
    {
        $this->forceFill(['active' => false])->save();
        $this->delete(); // soft delete: o histórico das reservas permanece
    }

    public function arena()
    {
        // withTrashed: arena excluída (soft delete) continua aparecendo no
        // histórico de reservas com o nome. Sem isto, `$booking->court->arena`
        // voltava nulo e a reserva antiga perdia de qual arena era.
        return $this->belongsTo(Arena::class)->withTrashed();
    }

    public function sports()
    {
        return $this->hasMany(CourtSport::class);
    }
}
