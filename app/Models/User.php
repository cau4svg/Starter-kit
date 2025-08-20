<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }

    public function transaction(string $type, float $amount)
    {
        if (! in_array($type, ['credit', 'debit'])) {
            throw new \InvalidArgumentException("Tipo de transação inválido: $type");
        }

        // Cria a transação
        $transaction = $this->transactions()->create([
            'type' => $type,
            'amount' => $amount,
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

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key ID.
     */
    protected $keyType = 'string';

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
        'bearer_apibrasil',
        'is_admin',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
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
            'is_admin' => 'boolean',
        ];
    }

    // TESTE
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });

        parent::boot();

        static::creating(function ($user) {
            if (User::count() === 0) {
                $user->is_admin = true; // Primeiro usuário
            } else {
                $user->is_admin = false; // Todos os outros
            }
        });
    }
}
