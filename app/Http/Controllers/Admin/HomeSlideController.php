<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSlide;
use App\Services\SlideImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Aparência da tela inicial: o admin gerencia as fotos e os textos do
 * carrossel sem precisar mexer no código.
 */
class HomeSlideController extends Controller
{
    /**
     * Teto de upload lido do próprio PHP (o menor entre upload_max_filesize e
     * post_max_size). Fixar um número no código faz a mensagem mentir: acima do
     * limite do php.ini o arquivo é descartado ANTES do Laravel ver, e a regra
     * `max` nem chega a rodar — quem dispara é a regra `uploaded`.
     */
    private function limiteUploadKb(): int
    {
        return min(
            $this->iniParaKb((string) ini_get('upload_max_filesize')),
            $this->iniParaKb((string) ini_get('post_max_size'))
        );
    }

    /** Converte "2M", "8M", "1G" do php.ini para kilobytes. */
    private function iniParaKb(string $valor): int
    {
        $numero = (int) $valor;

        return match (strtolower(substr(trim($valor), -1))) {
            'g' => $numero * 1024 * 1024,
            'm' => $numero * 1024,
            'k' => $numero,
            default => (int) ($numero / 1024),
        };
    }

    /**
     * Primeira linha de defesa (barata). A checagem real do conteúdo do arquivo
     * é feita pelo SlideImageService, que lê o cabeçalho binário e re-codifica.
     */
    private function regrasImagem(bool $obrigatoria): array
    {
        return array_merge(
            [$obrigatoria ? 'required' : 'nullable'],
            ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.$this->limiteUploadKb()]
        );
    }

    private function mensagensImagem(): array
    {
        $limite = round($this->limiteUploadKb() / 1024, 1);

        return [
            'imagem.required' => 'Escolha uma imagem.',
            'imagem.image' => 'O arquivo precisa ser uma imagem.',
            'imagem.mimes' => 'Use uma imagem JPG, PNG ou WEBP.',
            'imagem.max' => "A imagem deve ter no máximo {$limite} MB.",
            // Dispara quando o próprio PHP recusa o arquivo (tamanho acima do
            // limite do servidor, envio interrompido, sem permissão de escrita).
            'imagem.uploaded' => "Não foi possível enviar a imagem. Ela provavelmente passa do limite de {$limite} MB do servidor. Use uma foto menor.",
        ];
    }

    public function index()
    {
        $slides = HomeSlide::orderBy('ordem')->orderBy('id')->get();
        $limiteMb = round($this->limiteUploadKb() / 1024, 1);

        // Sem o link, as fotos enviadas ficam no disco mas dão 404 no navegador.
        // É o passo mais esquecido ao instalar o projeto numa máquina nova.
        $linkStorageOk = file_exists(public_path('storage'));

        // Sem GD não há como redimensionar/recomprimir: avisa antes de o admin tentar.
        $gdOk = SlideImageService::disponivel();

        return view('admin.aparencia.index', compact('slides', 'limiteMb', 'linkStorageOk', 'gdOk'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'imagem' => $this->regrasImagem(true),
            'titulo' => ['nullable', 'string', 'max:120'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            // Regex estrito: esta cor vai para dentro de um atributo style na home.
            'cor_texto' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], $this->mensagensImagem() + [
            'cor_texto.required' => 'Escolha a cor do texto.',
            'cor_texto.regex' => 'Cor inválida. Use o seletor de cores.',
        ]);

        HomeSlide::create([
            'image_path' => SlideImageService::processarEGuardar($request->file('imagem')),
            'titulo' => $dados['titulo'] ?? null,
            'subtitulo' => $dados['subtitulo'] ?? null,
            'cor_texto' => $dados['cor_texto'],
            'ordem' => HomeSlide::proximaOrdem(),
            'active' => true,
        ]);

        return back()->with('msg', 'Foto adicionada ao cabeçalho.');
    }

    public function update(Request $request, HomeSlide $slide)
    {
        $dados = $request->validate([
            'imagem' => $this->regrasImagem(false),
            'titulo' => ['nullable', 'string', 'max:120'],
            'subtitulo' => ['nullable', 'string', 'max:255'],
            // Regex estrito: esta cor vai para dentro de um atributo style na home.
            'cor_texto' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], $this->mensagensImagem() + [
            'cor_texto.required' => 'Escolha a cor do texto.',
            'cor_texto.regex' => 'Cor inválida. Use o seletor de cores.',
        ]);

        $antigo = $slide->image_path;
        $novoCaminho = $request->hasFile('imagem')
            ? SlideImageService::processarEGuardar($request->file('imagem'))
            : null;

        $slide->update([
            'titulo' => $dados['titulo'] ?? null,
            'subtitulo' => $dados['subtitulo'] ?? null,
            'cor_texto' => $dados['cor_texto'],
            'image_path' => $novoCaminho ?? $antigo,
        ]);

        // O arquivo antigo só é apagado DEPOIS que o banco confirmou a troca.
        // Arquivo não participa de transação: se apagássemos antes e o update
        // falhasse, a linha ficaria apontando para um arquivo inexistente.
        if ($novoCaminho) {
            Storage::disk('public')->delete($antigo);
        }

        return back()->with('msg', 'Foto atualizada.');
    }

    /**
     * Liga/desliga a foto sem apagá-la (some do site, continua aqui).
     */
    public function toggle(HomeSlide $slide)
    {
        $slide->update(['active' => ! $slide->active]);

        return back()->with('msg', $slide->active ? 'Foto ativada.' : 'Foto desativada.');
    }

    public function destroy(HomeSlide $slide)
    {
        $caminho = $slide->image_path;

        // Remove a linha primeiro: se o delete falhar, o arquivo continua lá e a
        // foto segue funcionando. Na ordem inversa, uma falha deixaria a linha
        // apontando para um arquivo já apagado (imagem quebrada no site).
        $slide->delete();

        Storage::disk('public')->delete($caminho);

        return back()->with('msg', 'Foto removida do cabeçalho.');
    }

    /**
     * Sobe/desce a foto trocando a ordem com a vizinha. A troca é feita numa
     * transação para as duas linhas nunca ficarem com a mesma posição.
     */
    public function move(HomeSlide $slide, string $direcao)
    {
        abort_unless(in_array($direcao, ['subir', 'descer'], true), 404);

        $vizinha = HomeSlide::where('ordem', $direcao === 'subir' ? '<' : '>', $slide->ordem)
            ->orderBy('ordem', $direcao === 'subir' ? 'desc' : 'asc')
            ->first();

        if (! $vizinha) {
            return back();
        }

        DB::transaction(function () use ($slide, $vizinha) {
            $posicaoSlide = $slide->ordem;
            $slide->update(['ordem' => $vizinha->ordem]);
            $vizinha->update(['ordem' => $posicaoSlide]);
        });

        return back();
    }
}
