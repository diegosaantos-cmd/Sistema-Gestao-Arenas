<?php
namespace App\Models;
use App\Support\Anonimizacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Arena extends Model
{
    use SoftDeletes;

    /** Quantas arenas por página nas listagens públicas. */
    public const POR_PAGINA = 12;

    /** Maior semente de embaralhamento da vitrine — curta porque viaja na URL. */
    private const SEMENTE_MAX = 999999;

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

    /**
     * Semente que embaralha a vitrine, resolvida a partir da requisição.
     *
     * Um carregamento novo da home (sem `ordem` na URL) sorteia uma semente, e
     * os pedidos seguintes de "carregar mais" devolvem a mesma. Assim cada
     * visita embaralha de um jeito — como era antes —, mas dentro da visita a
     * ordem não muda, que é o que impede a página 2 de repetir arenas da 1.
     *
     * Viaja na URL, e não na sessão, para duas abas abertas não disputarem a
     * mesma semente. Valor fora da faixa (ou ausente) vira sorteio novo, então
     * mexer na URL na mão não quebra nada.
     */
    public static function sementeDaVitrine(mixed $informada): int
    {
        $semente = (int) $informada;

        return $semente >= 1 && $semente <= self::SEMENTE_MAX
            ? $semente
            : random_int(1, self::SEMENTE_MAX);
    }

    /**
     * Ordem em que as arenas aparecem para o público.
     *
     * Com pesquisa: alfabética — quem procurou por nome espera ordem previsível.
     * Sem pesquisa: aleatória, para nenhuma arena levar vantagem por ter sido
     * cadastrada antes.
     *
     * O sorteio usa `RAND(semente)` em vez de `inRandomOrder()` porque este
     * re-sorteia a cada consulta: como a listagem é paginada, a página 2 vinha
     * de um embaralhamento diferente do da página 1 — repetindo umas arenas e
     * escondendo outras.
     */
    public function scopeEmOrdemDeVitrine($query, ?string $busca, int $semente)
    {
        if (trim((string) $busca) !== '') {
            return $query->orderBy('name');
        }

        return $query->orderByRaw('RAND(?)', [$semente]);
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
        // Marcador, e não nulo: o contato existia e foi retirado, e é isso que
        // as telas precisam poder dizer. Ver App\Support\Anonimizacao.
        //
        // Não atrapalha a checagem de e-mail já em uso por outra arena
        // (ArenaController::emailDeArenaEmUsoPorOutroDono): o escopo de soft
        // delete do model já tira as arenas excluídas daquela busca.
        $this->forceFill([
            'phone' => Anonimizacao::REMOVIDO,
            'contact_email' => Anonimizacao::REMOVIDO,
        ])->save();
    }

    public function owner()
    {
        // withTrashed: empresa excluída (soft delete) continua identificada no
        // histórico — a razão social e o CPF/CNPJ permanecem (RN10). Sem isto,
        // a arena de uma empresa excluída perdia o vínculo com o dono.
        return $this->belongsTo(Owner::class)->withTrashed();
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
