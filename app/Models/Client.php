<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'date_of_birth',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
