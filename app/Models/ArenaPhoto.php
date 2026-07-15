<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArenaPhoto extends Model
{
    protected $table = 'arena_photos';

    protected $fillable = [
        'arena_id', 'image_path', 'ordem',
    ];

    protected $casts = [
        'ordem' => 'integer',
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

    /**
     * O arquivo é servível pelo navegador nesta máquina? Consulta public/storage
     * (o caminho da URL), não o disco — numa máquina sem `storage:link` o arquivo
     * existe no disco mas a URL dá 404. Ver mesmo padrão em HomeSlide.
     */
    public function arquivoExiste(): bool
    {
        return $this->image_path && file_exists(public_path('storage/'.$this->image_path));
    }

    /** URL pública da imagem (asset() para respeitar o host de acesso). */
    public function url(): string
    {
        return asset('storage/'.$this->image_path);
    }

    /** Próxima posição no carrossel da arena (a menor ordem é a capa). */
    public static function proximaOrdem(int $arenaId): int
    {
        return (int) static::where('arena_id', $arenaId)->max('ordem') + 1;
    }
}
