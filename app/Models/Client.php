<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    // A tabela clients só tem created_at (sem updated_at).
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'date_of_birth',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
