<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Arena;
use App\Models\ArenaPhoto;
use App\Models\Owner;
use App\Services\SlideImageService;
use App\Support\ArenaAtual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Fotos da arena (galeria/carrossel). O dono ou o gerente da arena adiciona,
 * exclui e reordena até 15 fotos. Reaproveita o pipeline seguro de imagem do
 * SlideImageService (re-codifica, remove EXIF, redimensiona) — as fotos ficam
 * em storage/public/arenas e o carrossel aparece no card e nos detalhes.
 */
class ArenaPhotoController extends Controller
{
    private const LIMITE = 15;

    public function index(Arena $arena)
    {
        $this->autorizar($arena);
        $arena->load('photos');

        return view('arenas.photos', [
            'arena' => $arena,
            'limite' => self::LIMITE,
            'limiteMb' => round(SlideImageService::limiteUploadKb() / 1024, 1),
            'gdDisponivel' => SlideImageService::disponivel(),
            'storageOk' => file_exists(public_path('storage')),
        ]);
    }

    public function store(Request $request, Arena $arena)
    {
        $this->autorizar($arena);

        if ($arena->photos()->count() >= self::LIMITE) {
            return back()->withErrors([
                'imagem' => 'Limite de ' . self::LIMITE . ' fotos por arena atingido. Exclua uma para adicionar outra.',
            ]);
        }

        $request->validate(
            ['imagem' => SlideImageService::regrasImagem(true)],
            SlideImageService::mensagensImagem()
        );

        $arena->photos()->create([
            'image_path' => SlideImageService::processarEGuardar($request->file('imagem'), 'arenas'),
            'ordem' => ArenaPhoto::proximaOrdem($arena->id),
        ]);

        return back()->with('msg', 'Foto adicionada à arena.');
    }

    public function destroy(Arena $arena, ArenaPhoto $photo)
    {
        $this->autorizar($arena);
        abort_unless($photo->arena_id === $arena->id, 404);

        if ($photo->image_path) {
            Storage::disk('public')->delete($photo->image_path);
        }
        $photo->delete();

        return back()->with('msg', 'Foto removida.');
    }

    /**
     * Reordena: troca a posição desta foto com a vizinha na direção pedida
     * ('cima' ou 'baixo'). A menor ordem é a capa do carrossel.
     */
    public function move(Request $request, Arena $arena, ArenaPhoto $photo)
    {
        $this->autorizar($arena);
        abort_unless($photo->arena_id === $arena->id, 404);

        $validated = $request->validate([
            'direcao' => ['required', Rule::in(['cima', 'baixo'])],
        ]);

        $vizinho = $arena->photos()
            ->where('id', '!=', $photo->id)
            ->when(
                $validated['direcao'] === 'cima',
                fn ($q) => $q->where('ordem', '<=', $photo->ordem)->orderByDesc('ordem')->orderByDesc('id'),
                fn ($q) => $q->where('ordem', '>=', $photo->ordem)->orderBy('ordem')->orderBy('id'),
            )
            ->first();

        if ($vizinho) {
            $ordemPhoto = $photo->ordem;
            $photo->update(['ordem' => $vizinho->ordem]);
            $vizinho->update(['ordem' => $ordemPhoto]);

            // Empate de 'ordem' (registros antigos): normaliza para não travar.
            if ($vizinho->ordem === $ordemPhoto) {
                $this->normalizarOrdem($arena);
            }
        }

        return back();
    }

    /** Reescreve a ordem em 1,2,3... conforme a ordem atual (desempata). */
    private function normalizarOrdem(Arena $arena): void
    {
        $arena->photos()->get()->values()->each(
            fn ($p, $i) => $p->update(['ordem' => $i + 1])
        );
    }

    /**
     * Só o DONO da arena ou o GERENTE dela. Mesmo critério do
     * ArenaController::arenaEditavel — o atendente não gerencia (rota pode.gerir).
     */
    private function autorizar(Arena $arena): void
    {
        if (ArenaAtual::ehDono()) {
            $owner = Owner::where('user_id', auth()->id())->first();
            abort_unless($owner && $arena->owner_id === $owner->id, 403);

            return;
        }

        if (ArenaAtual::ehGerente()) {
            abort_unless((string) ArenaAtual::obter()->id === (string) $arena->id, 403);

            return;
        }

        abort(403);
    }
}
