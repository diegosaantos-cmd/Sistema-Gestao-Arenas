<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'employees';

    // employees tem created_at e updated_at -> o Eloquent gerencia os dois.

    protected $fillable = [
        'user_id',
        'arena_id',
        'created_by',
        'position',
        'access_level',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Excluído não é "ativo": ao dar soft delete no vínculo, zera o `active` na
     * hora. É evento — e não uma linha em cada exclusão — porque o funcionário é
     * excluído de vários pontos (demitir, excluir arena, excluir empresa, ação
     * do admin), e um deles sempre acabava esquecendo, deixando "excluído porém
     * ativo". Mesma regra do User::encerrarConta (RN10).
     */
    protected static function booted(): void
    {
        static::deleting(function (self $employee) {
            if (! $employee->isForceDeleting() && $employee->active) {
                $employee->forceFill(['active' => false])->saveQuietly();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

    public function createdBy()
    {
        // withTrashed: mantém QUEM cadastrou o funcionário no histórico.
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
