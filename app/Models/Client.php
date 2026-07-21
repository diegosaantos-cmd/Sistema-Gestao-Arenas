<?php

namespace App\Models;

use App\Support\Anonimizacao;
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
     * O VÍNCULO (`client_id`) é PRESERVADO. Antes ele era anulado, e isso
     * quebrava quem seguia a reserva até a pessoa: o lançamento de um pagamento
     * pendente, por exemplo, não encontrava mais o cliente e acabava creditado
     * a quem estava lançando. Manter o vínculo não expõe ninguém — do outro
     * lado está um Client excluído cujo User já foi anonimizado, e a própria
     * reserva já carrega "Cliente excluído" no lugar do nome.
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
            'guest_name'  => Anonimizacao::CLIENTE_EXCLUIDO,
            'guest_phone' => Anonimizacao::REMOVIDO,
            'guest_email' => Anonimizacao::REMOVIDO,
            'notes'       => Anonimizacao::REMOVIDO,
        ]);
    }

    /**
     * Apaga os dados pessoais guardados no próprio cliente.
     *
     * Hoje é só a data de nascimento, e aqui ela fica NULA mesmo: é coluna
     * `date`, não comporta marcador de texto. É a exceção consciente à regra de
     * substituir em vez de esvaziar — nenhuma tela identifica alguém pela data
     * de nascimento, então não há rastro que o marcador precisasse preservar.
     *
     * Sem isto, encerrar a conta deixava a data de nascimento intacta no
     * registro: o User era anonimizado, mas o Client só levava soft delete.
     */
    public function anonimizarDadosPessoais(): void
    {
        $this->forceFill(['date_of_birth' => null])->save();
    }

    /**
     * Horários que o cliente já usou e não pagou — a dívida dele com a arena.
     *
     * A regra de "em aberto" mora em `Booking::scopeEmAberto()`, para o admin
     * poder aplicá-la à página inteira de clientes de uma vez sem repetir a
     * definição em outro lugar.
     */
    public function reservasEmAberto()
    {
        return Booking::where('client_id', $this->id)->emAberto();
    }

    /** Quanto o cliente deve, somando os horários usados e não pagos. */
    public function valorEmAberto(): float
    {
        return (float) $this->reservasEmAberto()->sum('total_amount');
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
