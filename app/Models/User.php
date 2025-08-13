<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cellphone',
        'balance',
        'bearer_apibrasil',
        'is_admin'

    ];

    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
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
            'password' => 'hashed',
            'is_admin' => 'boolean'
        ];
    }

    //transaction type credit, debit
    public function transaction(string $type, float $amount)

    {

        if (!in_array($type, ['credit', 'debit'])) {

            throw new \InvalidArgumentException("Tipo de transação inválido: $type");
        }
        // Cria a transação
        $transaction = $this->transactions()->create([
            'type' => $type,
            'amount' => $amount
        ]);
        // Atualiza o saldo
        if ($type === 'credit') {

            $this->balance += $amount;
        } elseif ($type === 'debit') {

            $this->balance -= $amount;
        }
        $this->save();
        return $transaction;
    }

    //TESTE
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (User::count() === 0) {
                $user->is_admin = true;
            } else {
                $user->is_admin = false;
            }
        });
    }
}
