<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    // A tabela clients só tem created_at (sem updated_at).
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
