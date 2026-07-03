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

    public function deactivatedBy()
    {
        return $this->belongsTo(User::class, 'deactivated_by')->withTrashed();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function arenas()
    {
        return $this->hasMany(Arena::class);
    }
}
