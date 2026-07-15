@extends('layouts.main')

@section('title', 'Fotos da arena — ' . $arena->name)

@section('content')
<div class="dashboard-container container-fluid py-4">
    <x-back :href="route('arenas.show', $arena->id)" />

    <div class="mb-4">
        <h1 class="dashboard-title mb-1">Fotos da arena</h1>
        <p class="dashboard-subtitle mb-0">
            {{ $arena->name }} — as fotos aparecem no carrossel do card e na página da arena.
            A <strong>primeira</strong> é a capa. Até {{ $limite }} fotos.
        </p>
    </div>

    @unless ($storageOk)
        <div class="alert alert-danger">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-octagon me-1"></i> Falta preparar esta máquina</h6>
            <p class="mb-0">
                A pasta pública de imagens não está criada — as fotos serão salvas mas
                <strong>não aparecerão no site</strong>. Na pasta do projeto, rode uma vez:
                <code class="d-inline-block mt-1">php artisan storage:link</code>
            </p>
        </div>
    @endunless

    @unless ($gdDisponivel)
        <div class="alert alert-danger">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-octagon me-1"></i> Extensão de imagens indisponível</h6>
            <p class="mb-0">
                A extensão <strong>GD</strong> do PHP não está habilitada, necessária para processar as fotos.
                O envio não vai funcionar até ativá-la (no Linux, o pacote <code>php-gd</code>).
            </p>
        </div>
    @endunless

    @if (session('msg'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('msg') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    {{-- Adicionar foto --}}
    <div class="dashboard-box mb-4">
        <h2 class="section-title mb-3">Adicionar foto</h2>

        @if ($arena->photos->count() >= $limite)
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                Limite de {{ $limite }} fotos atingido. Exclua uma foto para adicionar outra.
            </p>
        @else
            <form method="POST" action="{{ route('arenas.photos.store', $arena) }}"
                  enctype="multipart/form-data" data-comprimir-imagem>
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-8">
                        <label class="form-label" for="imagem">Imagem</label>
                        <input type="file" class="form-control" id="imagem" name="imagem"
                               accept="image/jpeg,image/png,image/webp" required>
                        <div class="form-text">
                            JPG, PNG ou WEBP · até {{ $limiteMb }} MB · no máximo 12 megapixels.
                            <strong>Prefira fotos horizontais (paisagem), 16:9</strong>
                            (ex.: 1920×1080) — preenchem a tela sem faixas.
                            A imagem é reduzida e comprimida automaticamente.
                        </div>
                    </div>
                    <div class="col-12 col-lg-4 d-flex justify-content-lg-end">
                        <button class="btn btn-success">
                            <i class="bi bi-plus-circle me-1"></i> Adicionar foto
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    {{-- Fotos cadastradas --}}
    <div class="dashboard-box">
        <h2 class="section-title mb-3">
            Fotos da arena
            <span class="badge bg-secondary fs-6 align-middle">{{ $arena->photos->count() }}/{{ $limite }}</span>
        </h2>

        @if ($arena->photos->isEmpty())
            <p class="text-muted mb-0">
                Nenhuma foto ainda. Enquanto não houver, o card da arena mostra a imagem padrão.
            </p>
        @else
            <div class="row g-3">
                @foreach ($arena->photos as $photo)
                    <div class="col-12 col-sm-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="position-relative">
                                <img src="{{ $photo->url() }}" alt="Foto da arena"
                                     class="card-img-top" style="height: 160px; width: 100%; object-fit: cover;">
                                @if ($loop->first)
                                    <span class="badge bg-primary position-absolute top-0 start-0 m-2">
                                        <i class="bi bi-star-fill me-1"></i> Capa
                                    </span>
                                @endif
                            </div>
                            <div class="card-body py-2">
                                @unless ($photo->arquivoExiste())
                                    <div class="alert alert-warning py-1 px-2 small mb-2">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Arquivo ausente nesta máquina — envie a foto de novo.
                                    </div>
                                @endunless

                                <div class="d-flex flex-wrap gap-1">
                                    @unless ($loop->first)
                                        <form method="POST" action="{{ route('arenas.photos.move', [$arena, $photo]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="direcao" value="cima">
                                            <button class="btn btn-outline-secondary btn-sm" title="Mover para a esquerda / capa">
                                                <i class="bi bi-arrow-left"></i>
                                            </button>
                                        </form>
                                    @endunless
                                    @unless ($loop->last)
                                        <form method="POST" action="{{ route('arenas.photos.move', [$arena, $photo]) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="direcao" value="baixo">
                                            <button class="btn btn-outline-secondary btn-sm" title="Mover para a direita">
                                                <i class="bi bi-arrow-right"></i>
                                            </button>
                                        </form>
                                    @endunless

                                    <button type="button" class="btn btn-danger btn-sm ms-auto"
                                            data-bs-toggle="modal" data-bs-target="#excluirFoto{{ $photo->id }}">
                                        <i class="bi bi-trash me-1"></i> Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Modais de excluir (um por foto; no máximo 15) --}}
@foreach ($arena->photos as $photo)
    <div class="modal fade" id="excluirFoto{{ $photo->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('arenas.photos.destroy', [$arena, $photo]) }}">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Excluir foto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <img src="{{ $photo->url() }}" class="img-fluid rounded mb-3"
                             style="height: 140px; width: 100%; object-fit: cover;">
                        <p class="mb-0">Tem certeza que deseja excluir esta foto da arena? O arquivo será apagado do servidor.</p>
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
    /* Encolhe a foto no navegador ANTES de enviar (conveniência; o servidor
       revalida e recomprime). Mesmo padrão da tela de aparência do admin. */
    (function () {
        const LARGURA_MAX = 1920;
        const QUALIDADE = 0.82;

        async function comprimir(arquivo) {
            if (!arquivo || !arquivo.type.startsWith('image/')) return arquivo;
            if (typeof createImageBitmap !== 'function') return arquivo;
            let bitmap;
            try { bitmap = await createImageBitmap(arquivo); } catch (e) { return arquivo; }
            const escala = Math.min(1, LARGURA_MAX / bitmap.width);
            const largura = Math.round(bitmap.width * escala);
            const altura = Math.round(bitmap.height * escala);
            const canvas = document.createElement('canvas');
            canvas.width = largura; canvas.height = altura;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, largura, altura);
            ctx.drawImage(bitmap, 0, 0, largura, altura);
            if (bitmap.close) bitmap.close();
            const blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', QUALIDADE); });
            if (!blob || blob.size >= arquivo.size) return arquivo;
            return new File([blob], 'foto.jpg', { type: 'image/jpeg' });
        }

        document.querySelectorAll('form[data-comprimir-imagem]').forEach(function (form) {
            form.addEventListener('submit', async function (evento) {
                if (form.dataset.pronto === '1') return;
                const input = form.querySelector('input[type="file"]');
                if (!input || input.files.length === 0) return;
                evento.preventDefault();
                const botao = form.querySelector('button:not([type="button"])');
                if (botao) { botao.disabled = true; botao.textContent = 'Processando imagem...'; }
                try {
                    const arquivo = await comprimir(input.files[0]);
                    const lista = new DataTransfer();
                    lista.items.add(arquivo);
                    input.files = lista.files;
                } catch (e) {}
                form.dataset.pronto = '1';
                form.submit();
            });
        });
    })();
</script>
@endsection
