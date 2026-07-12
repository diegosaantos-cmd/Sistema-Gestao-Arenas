<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\Booking;
use App\Models\Court;
use App\Support\ArenaAtual;

class QuadraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $courts = $arena->courts()->with('sports')->orderBy('name')->get();

        return view('courts.index', compact('arena', 'courts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            // Sem arena ativa: o dashboard resolve (escolher arena ou criar uma).
            return redirect()->route('owners.dashboard');
        }

        if (! $arena->active) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'A arena "' . $arena->name . '" está inativa. Reative-a para cadastrar quadras.');
        }

        return view('courts.create', compact('arena'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Dono (arena selecionada) ou gerente (a arena dele). Ver ArenaAtual.
        $arena = ArenaAtual::tentar();

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        if (! $arena->active) {
            return redirect()->route('owners.dashboard')
                ->with('aviso', 'A arena "' . $arena->name . '" está inativa. Reative-a para cadastrar quadras.');
        }

        $arenaId = $arena->id;

        $request->merge([
            'quadras' => ArenaController::normalizarQuadras($request->input('quadras', [])),
        ]);

        $validated = $request->validate([
            'quadras' => ['required', 'array', 'min:1', function ($attribute, $value, $fail) use ($arenaId) {
                // Sem nomes equivalentes dentro do mesmo envio.
                if (ArenaController::temNomesDeQuadraDuplicados($value)) {
                    $fail('Há quadras com nomes equivalentes (ignorando espaços e maiúsculas).');
                    return;
                }
                // Nem equivalentes a quadras que já existem nesta arena.
                foreach ($value as $dados) {
                    $nome = $dados['nome'] ?? '';
                    if ($nome === '') {
                        continue;
                    }
                    $chave = ArenaController::chaveComparacao($nome);
                    $existe = Court::where('arena_id', $arenaId)
                        ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$chave])
                        ->exists();
                    if ($existe) {
                        $fail('Já existe uma quadra com o nome "'.$nome.'" nesta arena.');
                        return;
                    }
                }
            }],
            'quadras.*.nome' => ['required', 'string', 'max:80'],
            'quadras.*.descricao' => ['nullable', 'string'],
            'quadras.*.valor_hora' => ['required', 'numeric', 'min:0'],
            'quadras.*.ativa' => ['nullable', 'boolean'],
            'quadras.*.esportes' => ['required', 'array', 'min:1'],
            'quadras.*.esportes.*' => [Rule::in(array_keys(Court::SPORTS))],
        ], [
            'quadras.required' => 'Cadastre ao menos uma quadra.',
            'quadras.min' => 'Cadastre ao menos uma quadra.',
            'quadras.*.nome.required' => 'Informe o nome da quadra.',
            'quadras.*.valor_hora.required' => 'Informe o valor por hora da quadra.',
            'quadras.*.esportes.required' => 'Selecione ao menos um esporte por quadra.',
            'quadras.*.esportes.min' => 'Selecione ao menos um esporte por quadra.',
        ]);

        ArenaController::salvarQuadras($arena, $request->input('quadras', []));

        return redirect()->route('owners.dashboard')
            ->with('msg', 'Quadra(s) cadastrada(s) com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Formulário de edição de uma quadra (dono da arena dela).
     */
    public function edit(Court $quadra)
    {
        $this->guardQuadra($quadra);

        $quadra->load('sports');
        $arena = $quadra->arena;

        return view('courts.edit', compact('arena', 'quadra'));
    }

    /**
     * Salva a edição da quadra (dados + esportes), com nome único na arena.
     */
    public function update(Request $request, Court $quadra)
    {
        $this->guardQuadra($quadra);

        $request->merge([
            'nome' => ArenaController::normalizarTexto($request->input('nome')),
        ]);

        $arenaId = $quadra->arena_id;
        $courtId = $quadra->id;

        $validated = $request->validate([
            'nome' => ['required', 'string', 'max:80', function ($attribute, $value, $fail) use ($arenaId, $courtId) {
                // Nome não pode ser equivalente ao de outra quadra da mesma arena.
                $chave = ArenaController::chaveComparacao($value);
                $existe = Court::where('arena_id', $arenaId)
                    ->where('id', '!=', $courtId)
                    ->whereRaw("REPLACE(LOWER(name), ' ', '') = ?", [$chave])
                    ->exists();
                if ($existe) {
                    $fail('Já existe uma quadra com o nome "'.$value.'" nesta arena.');
                }
            }],
            'valor_hora' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string'],
            'ativa' => ['nullable', 'boolean'],
            'esportes' => ['required', 'array', 'min:1'],
            'esportes.*' => [Rule::in(array_keys(Court::SPORTS))],
        ], [
            'nome.required' => 'Informe o nome da quadra.',
            'valor_hora.required' => 'Informe o valor por hora da quadra.',
            'esportes.required' => 'Selecione ao menos um esporte.',
            'esportes.min' => 'Selecione ao menos um esporte.',
        ]);

        DB::transaction(function () use ($quadra, $validated) {
            $quadra->update([
                'name' => $validated['nome'],
                'hourly_rate' => $validated['valor_hora'],
                'description' => $validated['descricao'] ?? null,
                'active' => ! empty($validated['ativa']),
            ]);

            // Sincroniza os esportes: apaga os atuais e recria os selecionados.
            $quadra->sports()->delete();
            foreach (array_unique($validated['esportes']) as $sport) {
                $quadra->sports()->create(['sport' => $sport]);
            }
        });

        return redirect()->route('quadras.index')
            ->with('msg', 'Quadra atualizada com sucesso!');
    }

    /**
     * Ativa/desativa a quadra. Reativar é direto; desativar só é imediato se
     * não houver reservas futuras — se houver, mostra a tela de confirmação
     * (mesma lógica da arena).
     */
    public function toggleActive(Court $quadra)
    {
        $this->guardQuadra($quadra);

        // Reativar.
        if (! $quadra->active) {
            $quadra->update(['active' => true]);

            return redirect()->route('quadras.index')->with('msg', 'Quadra reativada.');
        }

        // Desativar: verifica reservas que seriam canceladas.
        $afetados = self::agendamentosAtivosDaQuadra($quadra);

        if ($afetados->isEmpty()) {
            $quadra->update(['active' => false]);

            return redirect()->route('quadras.index')->with('msg', 'Quadra desativada.');
        }

        $arena = $quadra->arena;

        return view('courts.deactivate-confirm', compact('quadra', 'arena', 'afetados'));
    }

    /**
     * Confirma a desativação da quadra cancelando as reservas afetadas.
     * O motivo é o mesmo para todas.
     */
    public function confirmDeactivate(Request $request, Court $quadra)
    {
        $this->guardQuadra($quadra);

        $motivo = trim((string) $request->input('motivo'));

        if ($motivo === '') {
            return view('courts.deactivate-confirm', [
                'quadra' => $quadra,
                'arena' => $quadra->arena,
                'afetados' => self::agendamentosAtivosDaQuadra($quadra),
                'erroMotivo' => 'Informe o motivo do cancelamento.',
                'motivo' => $motivo,
            ]);
        }

        DB::transaction(function () use ($quadra, $motivo) {
            foreach (self::agendamentosAtivosDaQuadra($quadra) as $booking) {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_by' => auth()->id(),
                    'cancelled_at' => now(),
                    'cancellation_reason' => $motivo,
                ]);
            }

            $quadra->update(['active' => false]);
        });

        return redirect()->route('quadras.index')
            ->with('msg', 'Quadra desativada e reservas afetadas canceladas.');
    }

    /**
     * Reservas futuras (pendentes/confirmadas) desta quadra.
     */
    private static function agendamentosAtivosDaQuadra(Court $quadra)
    {
        return Booking::with('client.user')
            ->where('court_id', $quadra->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('date')->orderBy('start_time')
            ->get();
    }

    /**
     * Garante que a quadra é da arena que o usuário gerencia agora
     * (dono na arena selecionada, ou gerente na arena dele).
     */
    private function guardQuadra(Court $quadra): void
    {
        $arena = ArenaAtual::obter();

        abort_unless($quadra->arena_id === $arena->id, 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
