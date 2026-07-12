<?php

namespace App\Http\Controllers;

use App\Models\Arena;
use App\Models\Booking;
use App\Models\Client;
use App\Models\UserNotification;
use App\Support\ArenaAtual;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Lista os clientes da arena atual: quem tem ao menos uma reserva nas
     * quadras dela (qualquer status). Mostra também quantas reservas cada um fez.
     */
    public function index()
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();

        // Mantém os status coerentes para as contagens (realizadas etc.).
        Booking::autoConfirmarExpiradas($courtIds);
        Booking::autoCompletarRealizadas($courtIds);

        // Clientes distintos com reserva nesta arena.
        $clientIds = Booking::whereIn('court_id', $courtIds)
            ->distinct()
            ->pluck('client_id');

        $q = trim((string) request('q'));

        $clients = Client::with('user')
            ->whereIn('id', $clientIds)
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('user', function ($u) use ($q) {
                    $u->where('name', 'like', "%{$q}%")        // nome: contém
                      ->orWhere('email', 'like', "{$q}%");      // e-mail: começa com (ignora o domínio)
                });
            })
            ->get();

        // Estatísticas básicas por cliente (realizadas / pagamentos pendentes /
        // pagamentos atrasados) — uma leitura, agrupada em memória (sem N+1).
        $stats = Booking::with('payments')
            ->whereIn('court_id', $courtIds)
            ->whereIn('client_id', $clients->pluck('id'))
            ->get()
            ->groupBy('client_id')
            ->map(fn ($bs) => [
                'realizadas' => $bs->where('status', 'completed')->count(),
                'pendentes'  => $bs->filter(fn ($b) => $b->situacaoPagamento() === 'a_pagar')->count(),
                'atrasados'  => $bs->filter(fn ($b) => $b->situacaoPagamento() === 'atrasado')->count(),
            ]);

        // Filtro de situação de pagamento: só com atrasados ou só com pendentes.
        $filtro = request('filtro');
        if ($filtro === 'atrasados') {
            $clients = $clients->filter(fn ($c) => ($stats[$c->id]['atrasados'] ?? 0) > 0)->values();
        } elseif ($filtro === 'pendentes') {
            $clients = $clients->filter(fn ($c) => ($stats[$c->id]['pendentes'] ?? 0) > 0)->values();
        }

        return view('clients.index', compact('arena', 'clients', 'stats', 'filtro'));
    }

    /**
     * Detalhes de um cliente para o dono: dados de contato, reservas a realizar,
     * realizadas, o que está devendo (não pagou) e envio de mensagem.
     */
    public function show(Client $client)
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();
        $this->garantirClienteDaArena($client, $courtIds);

        $client->load('user');

        $reservas = $this->separarReservas($this->reservasDoCliente($client, $courtIds));

        return view('clients.show', array_merge(
            ['arena' => $arena, 'client' => $client],
            $reservas
        ));
    }

    /**
     * Lista, em tabela, as reservas do cliente de um tipo: 'a-realizar',
     * 'realizadas' ou 'nao-pagas'. Cada card na tela de detalhes leva aqui.
     */
    public function bookings(Client $client, string $tipo)
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();
        $this->garantirClienteDaArena($client, $courtIds);
        $client->load('user');

        $r = $this->separarReservas($this->reservasDoCliente($client, $courtIds));

        [$titulo, $reservas] = match ($tipo) {
            'a-realizar' => ['Reservas a realizar', $r['proximas']],
            'realizadas' => ['Reservas realizadas', $r['realizadas']],
            'nao-pagas'  => ['Reservas não pagas', $r['naoPagas']],
            default      => abort(404),
        };

        return view('clients.bookings', compact('arena', 'client', 'tipo', 'titulo', 'reservas'));
    }

    /**
     * Tela dedicada de disparo em massa: lista os clientes da arena para o
     * dono selecionar (todos ou específicos) e escrever a mensagem.
     */
    public function broadcastForm()
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();

        $clientIds = Booking::whereIn('court_id', $courtIds)->distinct()->pluck('client_id');
        $clients = Client::with('user')->whereIn('id', $clientIds)->get();

        return view('clients.broadcast', compact('arena', 'clients'));
    }

    /**
     * Dispara uma mensagem (notificação interna) para vários clientes de uma
     * vez. O dono seleciona os destinatários na lista (todos ou específicos).
     */
    public function broadcast(Request $request)
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();

        // Clientes que realmente pertencem a esta arena (segurança).
        $clientIdsArena = Booking::whereIn('court_id', $courtIds)
            ->distinct()->pluck('client_id')->all();

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:120'],
            'body'         => ['required', 'string', 'max:2000'],
            'client_ids'   => ['required', 'array', 'min:1'],
            'client_ids.*' => ['integer'],
        ], [
            'title.required'      => 'Informe o assunto da mensagem.',
            'body.required'       => 'Escreva a mensagem.',
            'client_ids.required' => 'Selecione ao menos um cliente.',
            'client_ids.min'      => 'Selecione ao menos um cliente.',
        ]);

        $alvo = array_values(array_intersect(
            array_map('intval', $validated['client_ids']),
            $clientIdsArena
        ));

        if (empty($alvo)) {
            return back()->withErrors(['client_ids' => 'Nenhum cliente válido selecionado.'])->withInput();
        }

        $clients = Client::whereIn('id', $alvo)->whereNotNull('user_id')->get();

        foreach ($clients as $client) {
            UserNotification::create([
                'user_id'  => $client->user_id,
                'arena_id' => $arena->id,
                'sent_by'  => auth()->id(),
                'title'    => $validated['title'],
                'body'     => $validated['body'],
            ]);
        }

        return redirect()->route('clients.index')
            ->with('status', 'Mensagem enviada para ' . $clients->count() . ' cliente(s).');
    }

    /**
     * Tela dedicada para o dono escrever uma mensagem ao cliente.
     */
    public function messageForm(Client $client)
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();
        $this->garantirClienteDaArena($client, $courtIds);
        $client->load('user');

        return view('clients.message', compact('arena', 'client'));
    }

    /**
     * Envia uma mensagem (notificação interna) ao cliente. A tela onde o cliente
     * vê as notificações será configurada depois.
     */
    public function sendMessage(Request $request, Client $client)
    {
        $arena = $this->arenaAtual();
        if (! $arena instanceof Arena) {
            return $arena;
        }

        $courtIds = $arena->courts()->pluck('id')->all();
        $this->garantirClienteDaArena($client, $courtIds);

        $client->load('user');
        abort_unless($client->user, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body'  => ['required', 'string', 'max:2000'],
        ], [
            'title.required' => 'Informe o assunto da mensagem.',
            'body.required'  => 'Escreva a mensagem.',
        ]);

        UserNotification::create([
            'user_id'  => $client->user_id,
            'arena_id' => $arena->id,
            'sent_by'  => auth()->id(),
            'title'    => $validated['title'],
            'body'     => $validated['body'],
        ]);

        return redirect()->route('clients.show', $client)
            ->with('status', 'Mensagem enviada para ' . $client->user->name . '.');
    }

    /**
     * Reservas do cliente nesta arena, com quadra e pagamentos carregados.
     * Mantém os status coerentes antes (confirma expiradas / conclui realizadas).
     */
    private function reservasDoCliente(Client $client, array $courtIds)
    {
        Booking::autoConfirmarExpiradas($courtIds);
        Booking::autoCompletarRealizadas($courtIds);

        return Booking::with(['court', 'payments'])
            ->whereIn('court_id', $courtIds)
            ->where('client_id', $client->id)
            ->get();
    }

    /**
     * Separa as reservas em: a realizar, realizadas, não pagas (devendo) e
     * a contagem de canceladas.
     */
    private function separarReservas($bookings): array
    {
        // A realizar: confirmadas/pendentes cujo horário ainda não terminou.
        $proximas = $bookings
            ->filter(fn ($b) => in_array($b->status, ['pending', 'confirmed'])
                && Carbon::parse($b->date->toDateString() . ' ' . $b->end_time)->greaterThanOrEqualTo(now()))
            ->sortBy(fn ($b) => $b->date->toDateString() . ' ' . $b->start_time)
            ->values();

        // Realizadas: já concluídas (horário passou).
        $realizadas = $bookings->where('status', 'completed')
            ->sortByDesc(fn ($b) => $b->date->toDateString() . ' ' . $b->start_time)
            ->values();

        // Devendo: realizadas e não pagas.
        $naoPagas = $realizadas->filter(fn ($b) => ! $b->isPaga())->values();

        $canceladasCount = $bookings->where('status', 'cancelled')->count();

        return compact('proximas', 'realizadas', 'naoPagas', 'canceladasCount');
    }

    /**
     * Arena que o usuário está gerenciando (dono na arena selecionada, ou gerente
     * na arena dele). Retorna a Arena, ou um redirect (sem arena). Ver ArenaAtual.
     */
    private function arenaAtual()
    {
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        return $arena;
    }

    /**
     * Garante que o cliente tem ao menos uma reserva nas quadras desta arena.
     */
    private function garantirClienteDaArena(Client $client, array $courtIds): void
    {
        $temReserva = Booking::whereIn('court_id', $courtIds)
            ->where('client_id', $client->id)
            ->exists();

        abort_unless($temReserva, 404, 'Cliente não encontrado nesta arena.');
    }
}
