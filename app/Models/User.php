<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = true;

    protected $fillable = [
        'login_id',
        'password',
        'is_admin',
        'email',
        'first_name',
        'last_name',
        'credit_balance',
        'blocked_allergen_numbers',
        'push_enabled',
        'push_locale',
        'is_first_login',
        'role_id',
    ];

    protected $casts = [
        'blocked_allergen_numbers' => 'array',
        'push_enabled' => 'boolean',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class, 'users_id', 'id');
    }
}
