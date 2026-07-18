<?php

namespace App\Models;

use App\Notifications\AvisoDoSistema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserNotification extends Model
{
    protected $table = 'user_notifications';

    protected $fillable = [
        'user_id', 'arena_id', 'sent_by', 'title', 'body', 'read_at',
    ];

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
        $corpo = $this->body;
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

    /**
     * Avisa o STAFF de uma arena (o dono + os funcionários ativos: gerentes e
     * atendentes). Usado quando o próprio cliente age (cria ou cancela uma reserva)
     * e o staff precisa saber. `sent_by` fica nulo: o gatilho é uma ação do
     * cliente, não um envio manual.
     */
    public static function paraStaffDaArena(Arena $arena, string $title, string $body): void
    {
        foreach (static::idsStaffDaArena($arena) as $userId) {
            static::create([
                'user_id'  => $userId,
                'arena_id' => $arena->id,
                'sent_by'  => null,
                'title'    => $title,
                'body'     => $body,
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
