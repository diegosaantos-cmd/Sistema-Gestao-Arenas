<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterEntry extends Model
{
    protected $table = 'cash_register_entries';
    // A tabela só tem created_at (sem updated_at).
    public $timestamps = false;

    protected $fillable = [
        'cash_register_id',
        'booking_id',
        'type',
        'amount',
        'description',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Números dos lançamentos de uma arena ([id => nº]), na ordem de criação.
     *
     * O lançamento era identificado pelo próprio id da tabela, que é global:
     * numa arena os ids saíam com buracos (ex.: 1–11, depois 16–19), porque
     * outros ids foram consumidos por outras arenas ou por transações
     * descartadas. Aqui vira uma sequência limpa e contínua por arena (1, 2,
     * 3…). Espelha Booking::numerosNaArena.
     */
    public static function numerosDaArena(int $arenaId): array
    {
        $registros = CashRegister::where('arena_id', $arenaId)->pluck('id');

        return static::whereIn('cash_register_id', $registros)
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->flip()
            ->map(fn ($pos) => $pos + 1)
            ->all();
    }

    public function cashRegister()
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy()
    {
        // withTrashed: o lançamento do caixa continua mostrando QUEM lançou,
        // mesmo depois que o funcionário é excluído. É a trilha de auditoria
        // do dinheiro — sem isso o histórico financeiro perde o responsável.
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}