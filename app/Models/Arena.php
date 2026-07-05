<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Arena extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'address_rua',
        'address_bairro',
        'address_numero',
        'phone',
        'contact_email',
        'active',
        'deactivated_by_admin',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deactivated_by_admin' => 'boolean',
    ];

    public function scopePesquisar($query, ?string $busca)
    {
        $chave = preg_replace('/\s+/u', '', mb_strtolower(trim((string) $busca)));

        if ($chave === '') {
            return $query;
        }

        $termo = $chave . '%';

        return $query->where(function ($filtro) use ($termo) {
            $filtro->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                ->orWhereHas('owner', function ($owner) use ($termo) {
                    $owner->whereRaw("REPLACE(LOWER(company_name), ' ', '') LIKE ?", [$termo])
                        ->orWhereHas('user', fn ($user) =>
                            $user->whereRaw("REPLACE(LOWER(name), ' ', '') LIKE ?", [$termo])
                        );
                });
        });
    }

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }

    public function businessHours()
    {
        return $this->hasMany(ArenaBusinessHour::class);
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'arena_payment_methods');
    }

    public function courts()
    {
        return $this->hasMany(Court::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
