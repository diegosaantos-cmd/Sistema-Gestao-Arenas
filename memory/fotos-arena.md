---
name: fotos-arena
description: "Galeria de fotos por arena (até 15): dono/gerente gerencia; carrossel no card e nos detalhes; reaproveita SlideImageService"
metadata:
  type: project
---

Arena tem uma **galeria de fotos** (carrossel). Dono OU gerente da arena adiciona,
exclui e **reordena** (a 1ª = capa). **Limite 15/arena**.

**Dados:** tabela `arena_photos` (arena_id, image_path, ordem) + model `ArenaPhoto`
(`arquivoExiste()`/`url()` no mesmo padrão do [[instalacao-maquina-nova]]/HomeSlide) +
`Arena::photos()` (hasMany ordenado). Arquivos em `storage/app/public/arenas`.

**Upload:** reaproveita `App\Services\SlideImageService` (agora com parâmetro de pasta
e helpers estáticos `regrasImagem/mensagensImagem/limiteUploadKb`) — valida de verdade,
re-codifica (remove EXIF/código escondido), redimensiona 1920px, salva JPEG.

**Gestão:** `Owner\ArenaPhotoController` (index/store/destroy/move) nas rotas
`arenas.photos.*` dentro do grupo **pode.gerir** (atendente NÃO gerencia). Guard
próprio `autorizar()` = dono dela ou gerente dela. Tela `resources/views/arenas/photos.blade`
(reaproveita o script de compressão no cliente do admin/aparencia). Botão "Fotos da
arena" no topo de `arenas/show` (edição do dono).

**Cadastro:** `ArenaController::store` aceita `fotos[]` opcional (input em
`arenas/create`, form com enctype multipart) — processa defensivo (try/catch, foto
inválida/GD ausente é ignorada, não bloqueia o cadastro).

**Exibição:** no CARD (`client/arenas/_gallery-card`) é carrossel Bootstrap 1-a-1
(substitui o placeholder do emoji 🏟️ quando há foto). Na TELA DE DETALHES
(`client/arenas/show`) é uma GALERIA de miniaturas 16:9 num carrossel horizontal
(`.h-scroller`, CSS em style.css) que mostra **3 por vez** e desliza de lado (setas
`.scroller-arrow`, script `_scroller-script`); clicar amplia no lightbox na foto certa
(`data-lightbox-index`). As QUADRAS na mesma tela também usam `.h-scroller` (3 por vez).
Card e detalhes têm botão/lightbox de tela cheia (`_photo-lightbox`). Só mostra fotos com
`arquivoExiste()` (some se o arquivo faltar). **Eager load `photos`** em TODAS as
telas que mostram card: Client\ArenaController::index, Client\FavoriteController::index,
Client\DashboardController::index E a **rota `/` (welcome) em routes/web.php** (a inicial
usa `_gallery-card`; sem o `photos` o card cai no placeholder). Sem N+1.

**Instalação:** NÃO muda (storage:link e GD já eram requisitos; a pasta 'arenas' é
criada sozinha). Ver [[manter-manual-instalacao]] — não precisou atualizar o manual.
