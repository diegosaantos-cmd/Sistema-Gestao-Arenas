<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
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
        return $this->belongsTo(User::class, 'created_by');
    }
}
