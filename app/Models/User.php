<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\Features;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        $tipo = match ($this->type) {
            'client' => 'Cliente',
            'owner' => 'Proprietário',
            'admin' => 'Administrador',
            default => null,
        };

        if ($this->type === 'employee') {
            $emp = Employee::where('user_id', $this->id)->first();
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
