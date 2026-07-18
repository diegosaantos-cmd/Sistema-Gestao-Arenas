<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Arena extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address_rua',
        'address_bairro',
        'address_numero',
        'phone',
        'contact_email',
        'active',
        'deactivated_by_admin',
        'charges_cancellation_fee',
        'cancellation_fee_type',
        'cancellation_fee_value',
        'cancellation_fee_mode',
        'cancellation_fee_window_hours',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deactivated_by_admin' => 'boolean',
        'charges_cancellation_fee' => 'boolean',
        'cancellation_fee_value' => 'decimal:2',
        'cancellation_fee_window_hours' => 'integer',
    ];

    /**
     * Valor da taxa de cancelamento para uma reserva de determinado valor.
     * Fixo (R$) ou percentual sobre o valor da reserva, conforme a config.
     */
    public function taxaCancelamentoPara(float $valorReserva): float
    {
        if (! $this->charges_cancellation_fee) {
            return 0.0;
        }

        if ($this->cancellation_fee_type === 'percent') {
            return round($valorReserva * (float) $this->cancellation_fee_value / 100, 2);
        }

        // fixo
        return round((float) $this->cancellation_fee_value, 2);
    }

    public function scopePesquisar($query, ?string $busca)
    {
        $chave = preg_replace('/\s+/u', '', mb_strtolower(trim((string) $busca)));

        if ($chave === '') {
            return $query;
        }

        $termo = $chave . '%';

        return $query->where(function ($filtro) use ($termo) {
            $filtro->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                ->orWhereHas('owner', function ($owner) use ($termo) {
                    $owner->whereRaw("REPLACE(LOWER(company_name), ' ', '') LIKE ?", [$termo])
                        ->orWhereHas('user', fn ($user) =>
                            $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        );
                });
        });
    }

    /**
     * Apaga os dados de CONTATO da arena ao encerrá-la.
     *
     * Telefone e e-mail de contato costumam ser o telefone e o e-mail pessoais
     * do dono — e, com a arena encerrada, não servem mais a ninguém: ninguém
     * pode reservar nela. Somem por minimização de dados (LGPD).
     *
     * O que É registro do negócio permanece: nome da arena, endereço, quadras,
     * reservas, pagamentos e caixa. Some só o dado pessoal.
     *
     * Só na EXCLUSÃO. Desativar é reversível — apagar o contato ali impediria
     * a arena de voltar ao ar com os mesmos dados.
     */
    public function anonimizarContato(): void
    {
        $this->forceFill([
            'phone' => null,
            'contact_email' => null,
        ])->save();
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function businessHours()
    {
        return $this->hasMany(ArenaBusinessHour::class);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'arena_payment_methods');
    }

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    /** Fotos da arena (carrossel), na ordem definida pelo dono — a 1ª é a capa. */
    public function photos()
    {
        return $this->hasMany(ArenaPhoto::class)->orderBy('ordem')->orderBy('id');
    }

    /**
     * Clientes que marcaram esta arena como favorita.
     * Permite, por exemplo, contar quantos favoritaram (withCount).
     */
    public function favoritadaPor()
    {
        return $this->belongsToMany(Client::class, 'arena_favorites', 'arena_id', 'client_id')
            ->withPivot('created_at');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
