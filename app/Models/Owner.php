<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_name',
        'tax_id',
        'active',
        'deactivated_by',
        'deactivation_source',
        'deactivated_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deactivated_at' => 'datetime',
    ];

    /**
     * Excluída não é "ativa": ao dar soft delete na empresa, zera o `active` na
     * hora. É evento — e não uma linha em cada exclusão — porque a empresa é
     * excluída de vários pontos (excluir a última arena, excluir empresa, ação
     * do admin), e um deles sempre acabava esquecendo, deixando "excluída porém
     * ativa". Mesma regra do User::encerrarConta (RN10).
     */
    protected static function booted(): void
    {
        static::deleting(function (self $owner) {
            if (! $owner->isForceDeleting() && $owner->active) {
                $owner->forceFill(['active' => false])->saveQuietly();
            }
        });
    }

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by')->withTrashed();
    }
    public function user()
    {
        // withTrashed: empresa excluída (soft delete) continua identificada no
        // histórico — o nome do dono vem anonimizado ("Proprietário removido").
        // Sem isto, owner->user voltava null e o admin via um traço no lugar.
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function arenas()
    {
        return $this->hasMany(Arena::class);
    }
}
