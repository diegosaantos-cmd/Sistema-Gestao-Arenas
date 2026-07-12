@extends('layouts.main')

@section('title', 'Aparência da tela inicial')

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('admin.dashboard')" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Aparência da tela inicial</h1>
        <p class="dashboard-subtitle mb-0">
            Gerencie as fotos e os textos do cabeçalho — sem precisar mexer no código.
        </p>
    </div>

    @unless ($linkStorageOk)
        <div class="alert alert-danger">
            <h6 class="fw-bold mb-2">
                <i class="bi bi-exclamation-octagon me-1"></i> Falta preparar esta máquina
            </h6>
            <p class="mb-2">
                A pasta pública de imagens não está criada. As fotos enviadas serão salvas,
                mas <strong>não aparecerão no site</strong> (o site mostra a imagem padrão no lugar).
            </p>
            <p class="mb-0">
                Na pasta do projeto, execute uma vez:
                <code class="d-inline-block mt-1">php artisan storage:link</code>
            </p>
        </div>
    @endunless

    @unless ($gdOk)
        <div class="alert alert-danger">
            <h6 class="fw-bold mb-2">
                <i class="bi bi-exclamation-octagon me-1"></i> Extensão de imagens indisponível
            </h6>
            <p class="mb-0">
                A extensão <strong>GD</strong> do PHP não está habilitada neste servidor, e ela é
                necessária para redimensionar e comprimir as fotos.
                <strong>O envio de fotos não vai funcionar</strong> até que ela seja ativada
                (no Linux, o pacote <code>php-gd</code>).
            </p>
        </div>
    @endunless

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach ($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Cores sugeridas: aparecem como atalho dentro do seletor de cores do
         navegador. Não limitam a escolha — o admin pode usar qualquer cor. --}}
    <datalist id="paletaCores">
        <option value="#FFFFFF">Branco</option>
        <option value="#F3F4F6">Cinza claro</option>
        <option value="#FFC107">Amarelo</option>
        <option value="#0D6EFD">Azul</option>
        <option value="#198754">Verde</option>
        <option value="#DC3545">Vermelho</option>
        <option value="#021B35">Azul-marinho</option>
        <option value="#000000">Preto</option>
    </datalist>

    {{-- Adicionar nova foto --}}
    <div class="dashboard-box mb-4">
        <h2 class="section-title mb-3">Adicionar foto</h2>

        <form method="POST" action="{{ route('admin.aparencia.store') }}"
              enctype="multipart/form-data" data-comprimir-imagem>
            @csrf
            <div class="row g-3">
                <div class="col-12 col-lg-4">
                    <label class="form-label" for="imagem">Imagem</label>
                    <input type="file" class="form-control" id="imagem" name="imagem"
                           accept="image/jpeg,image/png,image/webp" required>
                    <div class="form-text">
                        JPG, PNG ou WEBP · até {{ $limiteMb }} MB · no máximo 12 megapixels.
                        A imagem é reduzida para 1920px e comprimida automaticamente.
                    </div>
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="titulo">
                        Título <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text" class="form-control" id="titulo" name="titulo"
                           maxlength="120" value="{{ old('titulo') }}"
                           placeholder="Ex.: Bem-vindo à ArenaPlay">
                </div>
                <div class="col-12 col-lg-3">
                    <label class="form-label" for="subtitulo">
                        Subtítulo <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <input type="text" class="form-control" id="subtitulo" name="subtitulo"
                           maxlength="255" value="{{ old('subtitulo') }}"
                           placeholder="Ex.: Os melhores jogos e campeonatos.">
                </div>
                <div class="col-12 col-lg-2">
                    <label class="form-label" for="cor_texto">Cor do texto</label>
                    <input type="color" class="form-control form-control-color w-100"
                           id="cor_texto" name="cor_texto" list="paletaCores"
                           value="{{ old('cor_texto', '#FFFFFF') }}"
                           title="Escolha a cor do título e do subtítulo">
                    <div class="form-text">A faixa de contraste é aplicada sozinha.</div>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Adicionar
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Fotos cadastradas --}}
    <div class="dashboard-box">
        <h2 class="section-title mb-3">
            Fotos do cabeçalho
            <span class="badge bg-secondary fs-6 align-middle">{{ $slides->count() }}</span>
        </h2>

        @if ($slides->isEmpty())
            <p class="text-muted mb-0">
                Nenhuma foto cadastrada. Enquanto não houver, a tela inicial mostra a imagem padrão do sistema.
            </p>
        @else
            <div class="row g-3">
                @foreach ($slides as $slide)
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="row g-0 align-items-center">
                                <div class="col-12 col-md-3">
                                    <img src="{{ $slide->url() }}" alt="{{ $slide->titulo }}"
                                         class="img-fluid rounded-start"
                                         style="height: 120px; width: 100%; object-fit: cover;">
                                </div>

                                <div class="col-12 col-md-5">
                                    <div class="card-body py-2">
                                        <div class="fw-bold">{{ $slide->titulo ?: '— sem título —' }}</div>
                                        <div class="text-muted small">{{ $slide->subtitulo ?: '— sem subtítulo —' }}</div>
                                        <span class="badge {{ $slide->active ? 'bg-success' : 'bg-secondary' }} mt-2">
                                            {{ $slide->active ? 'Aparecendo no site' : 'Oculta' }}
                                        </span>
                                        @if ($slide->titulo || $slide->subtitulo)
                                            <span class="badge border mt-2 d-inline-flex align-items-center gap-1"
                                                  style="color: {{ $slide->corTexto() }}; background-color: {{ $slide->fundoLegenda() }};">
                                                <i class="bi bi-fonts"></i> {{ $slide->corTexto() }}
                                            </span>
                                        @endif

                                        @unless ($slide->arquivoExiste())
                                            <div class="alert alert-warning py-1 px-2 small mt-2 mb-0">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                O arquivo desta foto não existe nesta máquina — o site está
                                                mostrando a imagem padrão. Envie a foto novamente.
                                            </div>
                                        @endunless
                                    </div>
                                </div>

                                <div class="col-12 col-md-4">
                                    <div class="card-body py-2 d-flex flex-wrap gap-1 justify-content-md-end">
                                        {{-- Reordenar --}}
                                        @if (! $loop->first)
                                            <form method="POST" action="{{ route('admin.aparencia.move', [$slide, 'subir']) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-outline-secondary btn-sm" title="Subir">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if (! $loop->last)
                                            <form method="POST" action="{{ route('admin.aparencia.move', [$slide, 'descer']) }}">
                                                @csrf @method('PATCH')
                                                <button class="btn btn-outline-secondary btn-sm" title="Descer">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button" class="btn btn-warning btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#editarSlide{{ $slide->id }}">
                                            <i class="bi bi-pencil me-1"></i> Editar
                                        </button>

                                        <form method="POST" action="{{ route('admin.aparencia.toggle', $slide) }}">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm {{ $slide->active ? 'btn-outline-secondary' : 'btn-success' }}">
                                                {{ $slide->active ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-danger btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#excluirSlide{{ $slide->id }}">
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Modais de editar e excluir (um por foto; a quantidade de slides é pequena) --}}
@foreach ($slides as $slide)
    <div class="modal fade" id="editarSlide{{ $slide->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.aparencia.update', $slide) }}"
                      enctype="multipart/form-data" data-comprimir-imagem>
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar foto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <img src="{{ $slide->url() }}" class="img-fluid rounded mb-3"
                             style="height: 140px; width: 100%; object-fit: cover;">

                        <div class="mb-3">
                            <label class="form-label">Trocar imagem <span class="text-muted fw-normal">(opcional)</span></label>
                            <input type="file" class="form-control" name="imagem"
                                   accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Deixe vazio para manter a imagem atual.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Título</label>
                            <input type="text" class="form-control" name="titulo"
                                   maxlength="120" value="{{ $slide->titulo }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subtítulo</label>
                            <input type="text" class="form-control" name="subtitulo"
                                   maxlength="255" value="{{ $slide->subtitulo }}">
                        </div>
                        <div>
                            <label class="form-label" for="cor{{ $slide->id }}">Cor do texto</label>
                            <input type="color" class="form-control form-control-color"
                                   id="cor{{ $slide->id }}" name="cor_texto" list="paletaCores"
                                   value="{{ $slide->corTexto() }}">
                            <div class="form-text">
                                Escolha uma cor que contraste com a foto. A faixa atrás do texto
                                é ajustada sozinha (escura para cor clara, clara para cor escura).
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-success">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="excluirSlide{{ $slide->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.aparencia.destroy', $slide) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Excluir foto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p>Tem certeza que deseja excluir esta foto do cabeçalho?</p>
                        <div class="alert alert-danger mb-0">
                            O arquivo da imagem será apagado do servidor. Esta ação não pode ser desfeita.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-danger">Sim, excluir</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    /*
     * Encolhe a foto no navegador ANTES de enviar: o upload fica rápido e a
     * imagem cabe folgada no limite do servidor.
     *
     * Isto é uma CONVENIÊNCIA, nunca uma defesa. O servidor continua validando
     * o conteúdo do arquivo e recomprimindo — se o JavaScript estiver desligado
     * ou falhar, a foto original sobe e o servidor decide o que fazer com ela.
     */
    (function () {
        const LARGURA_MAX = 1920;
        const QUALIDADE = 0.82;

        async function comprimir(arquivo) {
            if (!arquivo || !arquivo.type.startsWith('image/')) return arquivo;
            if (typeof createImageBitmap !== 'function') return arquivo;

            let bitmap;
            try {
                bitmap = await createImageBitmap(arquivo);
            } catch (e) {
                return arquivo; // formato que o navegador não decodifica (ex.: HEIC)
            }

            const escala = Math.min(1, LARGURA_MAX / bitmap.width);
            const largura = Math.round(bitmap.width * escala);
            const altura = Math.round(bitmap.height * escala);

            const canvas = document.createElement('canvas');
            canvas.width = largura;
            canvas.height = altura;

            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; // achata transparência: PNG viraria preto em JPEG
            ctx.fillRect(0, 0, largura, altura);
            ctx.drawImage(bitmap, 0, 0, largura, altura);
            if (bitmap.close) bitmap.close();

            const blob = await new Promise(function (resolve) {
                canvas.toBlob(resolve, 'image/jpeg', QUALIDADE);
            });

            // Se por acaso ficar maior, mantém o original.
            if (!blob || blob.size >= arquivo.size) return arquivo;

            return new File([blob], 'foto.jpg', { type: 'image/jpeg' });
        }

        document.querySelectorAll('form[data-comprimir-imagem]').forEach(function (form) {
            form.addEventListener('submit', async function (evento) {
                if (form.dataset.pronto === '1') return; // já processado, deixa seguir

                const input = form.querySelector('input[type="file"]');
                if (!input || input.files.length === 0) return; // sem foto nova

                evento.preventDefault();

                const botao = form.querySelector('button:not([type="button"])');
                if (botao) {
                    botao.disabled = true;
                    botao.textContent = 'Processando imagem...';
                }

                try {
                    const arquivo = await comprimir(input.files[0]);
                    const lista = new DataTransfer();
                    lista.items.add(arquivo);
                    input.files = lista.files;
                } catch (e) {
                    // Segue com o arquivo original: o servidor valida e comprime.
                }

                form.dataset.pronto = '1';
                form.submit();
            });
        });
    })();
</script>
@endsection
