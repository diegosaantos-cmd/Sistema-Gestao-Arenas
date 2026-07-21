<?php

namespace App\Models;

use App\Notifications\AvisoDoSistema;
use App\Support\Anonimizacao;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id', 'arena_id', 'booking_id', 'sent_by', 'title', 'body', 'read_at',
    ];

    /**
     * Marcador que o corpo usa no lugar do nome do cliente. É resolvido na
     * exibição (corpoResolvido), nunca gravado como nome de verdade — assim o
     * texto acompanha a anonimização em vez de congelar o nome de quem saiu.
     */
    public const CLIENTE = '{cliente}';

    protected $casts = [
        'read_at' => 'datetime',
    ];

    /**
     * Toda notificação criada também vira e-mail. Fica aqui, e não em cada lugar
     * que cria a notificação, para nenhum aviso novo esquecer de mandar e-mail.
     */
    protected static function booted(): void
    {
        static::created(function (self $notificacao) {
            $notificacao->enviarPorEmail();
        });
    }

    /**
     * Envia o aviso por e-mail ao destinatário.
     *
     * Duas proteções importantes:
     *
     * 1. `DB::afterCommit`: dentro de uma transação, o e-mail só sai depois do
     *    commit. Sem isso, um rollback (ex.: falha ao cancelar a reserva) deixaria
     *    o cliente com um e-mail sobre algo que nunca aconteceu.
     *
     * 2. try/catch: um SMTP fora do ar NÃO pode derrubar a confirmação de uma
     *    reserva. A falha é registrada no log e a operação principal continua.
     */
    public function enviarPorEmail(): void
    {
        $usuario = $this->user()->first();

        if (! $usuario || ! filter_var($usuario->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $titulo = $this->title;
        // Resolvido, e não o cru: o e-mail sairia com "{cliente}" no texto.
        $corpo = $this->corpoResolvido();
        $id = $this->id;
        // Nome da arena que gerou o aviso (withTrashed: mostra mesmo se ela foi
        // excluída depois). Fica nulo em avisos sem arena.
        $arenaNome = $this->arena_id
            ? optional($this->arena()->withTrashed()->first())->name
            : null;

        DB::afterCommit(function () use ($usuario, $titulo, $corpo, $id, $arenaNome) {
            try {
                $usuario->notify(new AvisoDoSistema($titulo, $corpo, $arenaNome));
            } catch (\Throwable $e) {
                Log::warning('Falha ao enviar aviso por e-mail.', [
                    'notificacao_id' => $id,
                    'user_id' => $usuario->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * O corpo pronto para mostrar: com o marcador {cliente} trocado pelo nome
     * atual da reserva.
     *
     * O nome não fica gravado — é montado aqui, a partir do estado de agora. Se
     * o cliente encerrou a conta, Booking::nomeCliente() devolve "Cliente
     * excluído" e é isso que aparece, sem nenhum texto antigo a limpar.
     *
     * Sem marcador (a maioria dos avisos) ou sem reserva ligada, devolve o
     * corpo como está.
     */
    public function corpoResolvido(): string
    {
        if (! str_contains($this->body, self::CLIENTE)) {
            return $this->body;
        }

        $nome = $this->booking?->nomeCliente() ?? Anonimizacao::CLIENTE_EXCLUIDO;

        return str_replace(self::CLIENTE, $nome, $this->body);
    }

    public function booking()
    {
        // Booking não usa soft delete (reservas são canceladas, nunca apagadas),
        // então a reserva referenciada está sempre lá para resolver o nome.
        return $this->belongsTo(Booking::class);
    }

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
        // withTrashed: mantém QUEM enviou o aviso, mesmo após a conta ser excluída.
        return $this->belongsTo(User::class, 'sent_by')->withTrashed();
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
            'user_id'    => $userId,
            'arena_id'   => $booking->court?->arena_id,
            'booking_id' => $booking->id,
            'sent_by'    => $sentBy,
            'title'      => $title,
            'body'       => $body,
        ]);
    }

    /**
     * Avisa SÓ o dono da arena (sem os funcionários).
     *
     * Usado quando o ADMIN age sobre o negócio dele — desativar/excluir arena,
     * remover um funcionário. O dono precisa saber o que aconteceu com a
     * empresa dele, e não é ele quem executou a ação.
     *
     * Não usa paraStaffDaArena porque, nesses casos, os funcionários ou estão
     * sendo encerrados junto ou não são os responsáveis pelo negócio.
     */
    public static function paraDonoDaArena(Arena $arena, string $title, string $body, ?int $sentBy = null): void
    {
        $arena->loadMissing('owner');
        $userId = $arena->owner?->user_id;

        if (! $userId) {
            return;
        }

        static::create([
            'user_id'  => $userId,
            'arena_id' => $arena->id,
            'sent_by'  => $sentBy,
            'title'    => $title,
            'body'     => $body,
        ]);
    }

    /**
     * Avisa o STAFF de uma arena (o dono + os funcionários ativos: gerentes e
     * atendentes). Usado quando o próprio cliente age (cria ou cancela uma reserva)
     * e o staff precisa saber. `sent_by` fica nulo: o gatilho é uma ação do
     * cliente, não um envio manual.
     */
    public static function paraStaffDaArena(Arena $arena, string $title, string $body, ?Booking $booking = null): void
    {
        foreach (static::idsStaffDaArena($arena) as $userId) {
            static::create([
                'user_id'    => $userId,
                'arena_id'   => $arena->id,
                // Quando o aviso é sobre uma reserva, guarda a referência: o
                // corpo traz o marcador {cliente}, resolvido na exibição a
                // partir dela. É o que impede o nome de ficar congelado no
                // texto depois que a conta do cliente é excluída.
                'booking_id' => $booking?->id,
                'sent_by'    => null,
                'title'      => $title,
                'body'       => $body,
            ]);
        }
    }

    /**
     * IDs de usuário do staff da arena: o dono e TODOS os funcionários ativos
     * (gerentes e atendentes) — o atendente também atende reservas no balcão, então
     * precisa ser avisado. Sem repetir (caso raro de o dono também ter vínculo de
     * funcionário na própria arena).
     */
    private static function idsStaffDaArena(Arena $arena): array
    {
        $arena->loadMissing('owner');

        $ids = [];
        if ($arena->owner?->user_id) {
            $ids[] = $arena->owner->user_id;
        }

        $funcionarios = Employee::where('arena_id', $arena->id)
            ->where('active', true)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->all();

        return array_values(array_unique([...$ids, ...$funcionarios]));
    }
}
