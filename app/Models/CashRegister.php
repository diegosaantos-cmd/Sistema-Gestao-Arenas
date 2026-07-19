<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    // A tabela é 'cash_register' (singular) e só tem created_at (sem updated_at).
    protected $table = 'cash_register';
    public $timestamps = false;

    protected $fillable = [
        'arena_id',
        'user_id',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'notes',
        'status',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'created_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

    public function user()
    {
        // withTrashed: quem ABRIU o caixa continua identificado depois de a
        // conta ser encerrada — é trilha de auditoria do dinheiro. O nome já
        // vem anonimizado ("Gerente removido"), então não expõe a pessoa.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function entries()
    {
        return $this->hasMany(CashRegisterEntry::class);
    }

    /**
     * Total das entradas (income) lançadas neste caixa.
     */
    public function totalEntradas(): float
    {
        return (float) $this->entries->where('type', 'income')->sum('amount');
    }

    /**
     * Total das saídas (expense) lançadas neste caixa.
     */
    public function totalSaidas(): float
    {
        return (float) $this->entries->where('type', 'expense')->sum('amount');
    }

    /**
     * Saldo atual = troco inicial + entradas − saídas.
     */
    public function saldoAtual(): float
    {
        return (float) $this->opening_balance + $this->totalEntradas() - $this->totalSaidas();
    }
}
