<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSlide extends Model
{
    /**
     * Imagem versionada no repositório, usada quando o arquivo enviado não
     * existe nesta máquina. As fotos ficam em storage/ (fora do Git), então um
     * clone novo tem as linhas no banco mas não os arquivos — sem este padrão,
     * a home mostraria imagens quebradas.
     *
     * É a img1.jpg já reduzida para 1920px e comprimida (5,8 MB → 245 KB).
     */
    public const IMAGEM_PADRAO = 'img/home-default.jpg';

    protected $table = 'home_slides';

    /** Cor do texto quando nada foi escolhido (ou veio lixo no banco). */
    public const COR_PADRAO = '#FFFFFF';

    protected $fillable = [
        'image_path', 'titulo', 'subtitulo', 'cor_texto', 'ordem', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'ordem' => 'integer',
    ];

    /**
     * Slides que a tela inicial deve exibir, já na ordem definida pelo admin.
     */
    public static function paraHome()
    {
        return static::where('active', true)->orderBy('ordem')->orderBy('id')->get();
    }

    /**
     * O arquivo é realmente servível pelo navegador nesta máquina?
     *
     * Consulta public/storage — o caminho que a URL usa — e não o disco
     * storage/app/public. A diferença importa: numa máquina sem o
     * `php artisan storage:link`, o arquivo está no disco mas a URL dá 404.
     * Perguntando ao disco, o fallback nunca seria acionado e a home mostraria
     * uma imagem quebrada. Assim, arquivo ausente OU link ausente caem no padrão.
     */
    public function arquivoExiste(): bool
    {
        return $this->image_path && file_exists(public_path('storage/'.$this->image_path));
    }

    /**
     * URL pública da imagem, com queda para a imagem padrão se o arquivo não
     * estiver nesta máquina.
     *
     * Usa asset() e não Storage::url(): este último monta a URL a partir do
     * APP_URL do .env, o que apontaria para o host errado quando o sistema é
     * acessado por outro endereço (ex.: 127.0.0.1:8000).
     */
    public function url(): string
    {
        return $this->arquivoExiste()
            ? asset('storage/'.$this->image_path)
            : asset(self::IMAGEM_PADRAO);
    }

    /**
     * Cor do texto em #RRGGBB. Defensivo: se vier qualquer coisa fora do formato
     * (registro antigo, lixo, edição manual no banco), cai no padrão em vez de
     * injetar um valor inválido no atributo style da página.
     */
    public function corTexto(): string
    {
        $cor = (string) $this->cor_texto;

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) ? $cor : self::COR_PADRAO;
    }

    /**
     * Faixa semitransparente atrás do texto, escolhida pela LUMINOSIDADE da cor.
     *
     * É o que garante a legibilidade sem depender do acerto humano: texto claro
     * ganha faixa escura, texto escuro ganha faixa clara. Assim, trocar a foto
     * depois não faz o texto sumir.
     *
     * Usa a fórmula de luminosidade percebida (o olho enxerga o verde muito mais
     * que o azul), e não a média simples dos canais.
     */
    public function fundoLegenda(): string
    {
        $hex = ltrim($this->corTexto(), '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminosidade = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        return $luminosidade > 140
            ? 'rgba(0, 0, 0, .45)'      // texto claro  -> faixa escura
            : 'rgba(255, 255, 255, .72)'; // texto escuro -> faixa clara
    }

    /**
     * Próxima posição livre no fim da lista.
     */
    public static function proximaOrdem(): int
    {
        return (int) static::max('ordem') + 1;
    }
}
