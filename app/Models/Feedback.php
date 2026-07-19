<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'user_id', 'tipo', 'assunto', 'mensagem', 'status', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        // withTrashed: quem enviou a sugestão continua identificado depois de
        // encerrar a conta — o nome já vem anonimizado ("Cliente removido").
        // Sem isto o admin veria um traço e não saberia de onde veio o relato.
        return $this->belongsTo(User::class)->withTrashed();
    }
}
