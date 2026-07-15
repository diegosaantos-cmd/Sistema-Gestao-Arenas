<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Recebe a foto enviada pelo admin, valida de verdade, reduz e comprime.
 *
 * Segurança: o arquivo enviado NUNCA é movido para a pasta pública. Ele é
 * decodificado em memória e uma imagem NOVA é gravada. Isso descarta metadados
 * (EXIF) e qualquer conteúdo escondido no arquivo — o truque clássico de anexar
 * código PHP ao fim de um JPEG não sobrevive à re-codificação. O nome e a
 * extensão do arquivo final também não vêm do usuário.
 */
class SlideImageService
{
    /** Largura máxima do cabeçalho; acima disso é desperdício de banda. */
    private const LARGURA_MAX = 1920;

    private const QUALIDADE = 82;

    /**
     * Teto de resolução. Uma imagem decodificada ocupa ~4 bytes por pixel;
     * 12 MP ≈ 48 MB de RAM, seguro dentro do memory_limit de 128 MB.
     */
    private const PIXELS_MAX = 12_000_000;

    private const PASTA = 'slides';

    private const TIPOS_ACEITOS = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    /**
     * Processa e grava a imagem. Devolve o caminho relativo no disco 'public'.
     */
    /**
     * A extensão GD está habilitada? Sem ela nada aqui funciona.
     * Costuma vir ligada no WAMP, mas no Linux o pacote php-gd é instalado à parte.
     */
    public static function disponivel(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    }

    public static function processarEGuardar(UploadedFile $arquivo, ?string $pasta = null): string
    {
        $pasta = $pasta ?: self::PASTA;

        // Sem esta checagem o PHP lançaria "Call to undefined function" e o admin
        // veria um erro 500 sem explicação nenhuma.
        if (! self::disponivel()) {
            self::falhar('O servidor não tem a extensão GD do PHP habilitada, necessária para processar imagens. Peça ao responsável para ativá-la (php-gd).');
        }

        $caminhoTemp = $arquivo->getRealPath();

        // Lê o cabeçalho binário do arquivo. Não confia na extensão nem no
        // Content-Type informado pelo navegador (ambos são falsificáveis).
        $info = @getimagesize($caminhoTemp);

        if ($info === false) {
            self::falhar('O arquivo enviado não é uma imagem válida.');
        }

        [$largura, $altura] = $info;
        $tipo = $info[2];

        if (! in_array($tipo, self::TIPOS_ACEITOS, true)) {
            self::falhar('Use uma imagem JPG, PNG ou WEBP.');
        }

        if ($largura * $altura > self::PIXELS_MAX) {
            self::falhar('A imagem tem resolução alta demais (máximo 12 megapixels).');
        }

        $origem = match ($tipo) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($caminhoTemp),
            IMAGETYPE_PNG => @imagecreatefrompng($caminhoTemp),
            IMAGETYPE_WEBP => @imagecreatefromwebp($caminhoTemp),
        };

        if (! $origem) {
            self::falhar('Não foi possível ler a imagem enviada.');
        }

        $destino = null;

        try {
            $destino = self::redimensionar($origem, $largura, $altura);

            Storage::disk('public')->makeDirectory($pasta);

            // Nome e extensão definidos por nós, nunca pelo usuário.
            $relativo = $pasta.'/'.Str::random(40).'.jpg';
            $absoluto = Storage::disk('public')->path($relativo);

            if (! imagejpeg($destino, $absoluto, self::QUALIDADE)) {
                self::falhar('Não foi possível salvar a imagem.');
            }

            return $relativo;
        } finally {
            imagedestroy($origem);

            if ($destino) {
                imagedestroy($destino);
            }
        }
    }

    /**
     * Sempre desenha numa tela nova (mesmo sem reduzir): isso normaliza o
     * formato e achata a transparência, que viraria preto ao salvar em JPEG.
     */
    private static function redimensionar(\GdImage $origem, int $largura, int $altura): \GdImage
    {
        $novaLargura = min($largura, self::LARGURA_MAX);
        $novaAltura = max(1, (int) round($altura * ($novaLargura / $largura)));

        $destino = imagecreatetruecolor($novaLargura, $novaAltura);

        $branco = imagecolorallocate($destino, 255, 255, 255);
        imagefilledrectangle($destino, 0, 0, $novaLargura, $novaAltura, $branco);

        imagecopyresampled(
            $destino, $origem,
            0, 0, 0, 0,
            $novaLargura, $novaAltura,
            $largura, $altura
        );

        return $destino;
    }

    private static function falhar(string $mensagem): never
    {
        throw ValidationException::withMessages(['imagem' => $mensagem]);
    }

    /** Regras de validação do campo 'imagem' (compartilhadas entre uploads). */
    public static function regrasImagem(bool $obrigatoria = true): array
    {
        return array_merge(
            [$obrigatoria ? 'required' : 'nullable'],
            ['image', 'mimes:jpeg,jpg,png,webp', 'max:'.self::limiteUploadKb()]
        );
    }

    /** Mensagens amigáveis para o campo 'imagem'. */
    public static function mensagensImagem(): array
    {
        $limite = round(self::limiteUploadKb() / 1024, 1);

        return [
            'imagem.required' => 'Escolha uma imagem.',
            'imagem.image' => 'O arquivo precisa ser uma imagem.',
            'imagem.mimes' => 'Use uma imagem JPG, PNG ou WEBP.',
            'imagem.max' => "A imagem deve ter no máximo {$limite} MB.",
            'imagem.uploaded' => "Não foi possível enviar a imagem. Ela provavelmente passa do limite de {$limite} MB do servidor. Use uma foto menor.",
        ];
    }

    /** Menor limite de upload do servidor (upload_max_filesize x post_max_size), em KB. */
    public static function limiteUploadKb(): int
    {
        return min(
            self::iniParaKb((string) ini_get('upload_max_filesize')),
            self::iniParaKb((string) ini_get('post_max_size'))
        );
    }

    private static function iniParaKb(string $valor): int
    {
        $numero = (int) $valor;

        return match (strtolower(substr(trim($valor), -1))) {
            'g' => $numero * 1024 * 1024,
            'm' => $numero * 1024,
            'k' => $numero,
            default => (int) ($numero / 1024),
        };
    }
}
