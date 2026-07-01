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
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

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
