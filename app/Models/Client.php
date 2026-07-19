<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'date_of_birth',
    ];

    /**
     * Desliga as reservas deste cliente e APAGA os dados pessoais delas.
     *
     * Quando a conta é encerrada, os dados pessoais não podem sobreviver em
     * outro lugar. Antes, a exclusão copiava nome, telefone e e-mail reais para
     * os campos `guest_*` da reserva — ou seja, "anonimizava" a conta e duplicava
     * o dado numa tabela onde ele continuava visível para a arena. Isso contraria
     * a minimização de dados da LGPD.
     *
     * O que a arena precisa guardar é o REGISTRO (data, quadra, valor, pagamento
     * e caixa), não a identidade de quem já saiu. A reserva passa a mostrar
     * apenas "Cliente excluído".
     *
     * `guest_name` recebe o marcador porque o banco exige `client_id` OU
     * `guest_name` preenchido (CHECK chk_bookings_cliente_ou_convidado).
     *
     * `notes` também é limpo: reservas antigas guardam ali
     * "Responsável: <nome> | Telefone: <fone>", gravado automaticamente pelo
     * site. Era uma segunda cópia do dado pessoal, fora do alcance da
     * anonimização e visível em "Observações" na tela de detalhes. O campo
     * deixou de ser preenchido (ver Client\BookingController), mas os registros
     * antigos precisam ser limpos aqui.
     *
     * @return int quantas reservas foram anonimizadas
     */
    public function desligarReservasAnonimizando(): int
    {
        // Os dados reais são SUBSTITUÍDOS por genéricos aqui, na hora da
        // exclusão — não são apenas apagados. Assim o registro guarda o que a
        // tela mostra, e nenhuma tela precisa decidir o que exibir no lugar de
        // um campo vazio (que seria ambíguo entre "não havia" e "foi apagado").
        return Booking::where('client_id', $this->id)->update([
            'guest_name'  => Booking::CLIENTE_EXCLUIDO,
            'guest_phone' => Booking::REMOVIDO,
            'guest_email' => Booking::REMOVIDO,
            'notes'       => Booking::REMOVIDO,
            'client_id'   => null,
        ]);
    }

    public function user()
    {
        // withTrashed: quando o cliente encerra a conta (soft delete do User),
        // as reservas dele continuam aparecendo no histórico da arena COM o nome.
        // Espelha o que Booking::client() e Booking::court() já fazem.
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Arenas que o cliente marcou como favoritas, das mais recentes para as
     * mais antigas. Arenas excluídas (soft delete) não aparecem: o escopo
     * global do model Arena as filtra.
     */
    public function favoritas()
    {
        return $this->belongsToMany(Arena::class, 'arena_favorites', 'client_id', 'arena_id')
            ->withPivot('created_at')
            ->orderByPivot('created_at', 'desc');
    }
}
