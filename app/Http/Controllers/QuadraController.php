<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Court;
use App\Models\Owner;

class QuadraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem ver as quadras.');
        }

        $arena = $owner->arenas()->find(session('selected_arena_id'));

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
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem cadastrar quadras.');
        }

        // Usa a arena que o dono está gerenciando (selecionada no dashboard).
        $arena = $owner->arenas()->find(session('selected_arena_id'));

        if (! $arena) {
            // Sem arena ativa: o dashboard resolve (escolher arena ou criar uma).
            return redirect()->route('owners.dashboard');
        }

        return view('courts.create', compact('arena'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $owner = Owner::where('user_id', auth()->id())->first();

        if (! $owner) {
            abort(403, 'Apenas proprietários podem cadastrar quadras.');
        }

        // Arena vem da sessão (a que ele está gerenciando), não do formulário.
        $arena = $owner->arenas()->find(session('selected_arena_id'));

        if (! $arena) {
            return redirect()->route('owners.dashboard');
        }

        $arenaId = $arena->id;

        $request->merge([
            'quadras' => ArenaController::normalizarNomesQuadras($request->input('quadras', [])),
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
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
