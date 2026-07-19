<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\Features;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Arena;

class User extends Authenticatable implements MustVerifyEmail
{
    use SoftDeletes;

    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;

    /**
     * Encerra a conta: apaga os dados pessoais, LIBERA o e-mail para um novo
     * cadastro, derruba o acesso e desativa a conta (soft delete).
     *
     * Este é o "o que significa excluir uma conta" do sistema inteiro — cliente,
     * funcionário, gerente, admin e proprietário passam por aqui. Ficar num
     * lugar só evita o que já aconteceu: uma tela de exclusão esquecer um passo.
     *
     * Quem chama continua responsável pelo que é DELE: autorização, validações
     * (ex.: reservas pendentes), efeitos de domínio (encerrar vínculo, cancelar
     * reservas, anonimizar as reservas) e o logout de quem se autoexclui.
     *
     * NOME: também é dado pessoal, então some para TODOS os papéis. No lugar
     * dele entra um rótulo com a FUNÇÃO — que é o que a auditoria precisa saber
     * ("um gerente lançou isto"), sem identificar a pessoa. Ver
     * nomeGenericoParaHistorico().
     */
    public function encerrarConta(): void
    {
        $this->deleteProfilePhoto();

        $this->forceFill([
            'name' => $this->nomeGenericoParaHistorico(),
            // O e-mail vira placeholder porque `users.email` tem índice unique:
            // sem isso a linha em soft delete continuaria "segurando" o e-mail
            // original, e a pessoa nunca mais poderia se cadastrar com ele.
            'email' => 'removido_'.$this->id.'_'.Str::lower(Str::random(8)).'@conta.invalid',
            'phone' => null,
        ])->save();

        // Derruba o acesso na hora, sem depender do próximo request.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $this->id)->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            $this->tokens()->delete();
        }

        $this->delete();
    }

    /**
     * Rótulo que substitui o NOME quando a conta é encerrada.
     *
     * O histórico precisa saber a FUNÇÃO de quem agiu ("um gerente lançou
     * isto"), não a identidade — nome é dado pessoal e some por LGPD.
     *
     * NÃO leva o id do banco: as telas da arena não expõem id global (mesma
     * regra da numeração amigável das reservas). A distinção entre duas pessoas
     * removidas do mesmo cargo continua existindo no dado — as colunas de
     * autoria (`created_by`, `cancelled_by`) seguem apontando para linhas
     * diferentes —, só não é exibida. Quem precisa desse nível, o administrador,
     * chega nele pelas telas de sistema.
     */
    private function nomeGenericoParaHistorico(): string
    {
        $papel = match ($this->type) {
            'client' => 'Cliente',
            'owner' => 'Proprietário',
            'admin' => 'Administrador',
            'employee' => Employee::withTrashed()->where('user_id', $this->id)->first()?->access_level === 'managerial'
                ? 'Gerente'
                : 'Atendente',
            default => 'Usuário',
        };

        return $papel.' removido';
    }

    public function arenas()
    {
        // arenas não tem user_id; o vínculo é User -> Owner -> Arenas.
        return $this->hasManyThrough(Arena::class, Owner::class);
    }

    public function owner()
    {
        return $this->hasOne(Owner::class);
    }

    public function client()
    {
        return $this->hasOne(Client::class);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function systemAdmin()
    {
        return $this->hasOne(SystemAdmin::class);
    }

    /**
     * "Tipo: Nome" do usuário (Cliente / Proprietário / Gerente / Atendente /
     * Administrador). Para funcionário, distingue gerente de atendente pelo nível
     * de acesso. Usado onde é útil saber o papel de quem fez a ação (ex.: quem
     * lançou algo no caixa).
     */
    public function descricaoComTipo(): string
    {
        // Conta encerrada: o nome já virou "Gerente removido #12", que carrega a
        // função. Repetir o prefixo daria "Gerente: Gerente removido #12".
        if ($this->trashed()) {
            return $this->name;
        }

        $tipo = match ($this->type) {
            'client' => 'Cliente',
            'owner' => 'Proprietário',
            'admin' => 'Administrador',
            default => null,
        };

        if ($this->type === 'employee') {
            // withTrashed: funcionário excluído mantém o cargo correto no
            // histórico. Sem isto, um GERENTE excluído apareceria como "Atendente".
            $emp = Employee::withTrashed()->where('user_id', $this->id)->first();
            $tipo = ($emp && $emp->access_level === 'managerial') ? 'Gerente' : 'Atendente';
        }

        return ($tipo ? $tipo . ': ' : '') . $this->name;
    }

    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password_hash',
        'terms_accepted_at',
        'active',
        'type', // Adiciona 'type' à lista de atributos preenchíveis
        'email_verified_at', // funcionário nasce verificado (ver EmployeeController)
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password_hash' => 'hashed',
        ];
    }

    public function getAuthPassword()
   {
    return $this->password_hash;
   }

    /**
     * O usuário conta como verificado?
     *
     * A classe implementa MustVerifyEmail para o recurso ficar PRONTO, mas ele só
     * passa a valer quando `EMAIL_VERIFICATION_ENABLED=true` no .env (que liga o
     * Features::emailVerification() do Fortify).
     *
     * Sem esta checagem, ligar apenas o `implements` já faria o middleware
     * `verified` das rotas bloquear todo mundo que se cadastrasse — pois o
     * Fortify não estaria enviando o e-mail de verificação.
     */
    public function hasVerifiedEmail(): bool
    {
        if (! Features::enabled(Features::emailVerification())) {
            return true;
        }

        return ! is_null($this->email_verified_at);
    }
}
