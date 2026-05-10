<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Casts\Attribute;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens;

    /**
     * Get the user's avatar URL.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */

   public function getAvatarAttribute($value)
    {
        return $value ? asset($value) : null;
    }
    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'bio',
        'email_verified_at',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'        => 'datetime',
            'password'                 => 'hashed',
            'two_factor_secret'        => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:json',
            'two_factor_confirmed_at'  => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Check if 2FA is enabled and confirmed.
     *
     * @return bool
     */
    public function hasTwoFactorEnabled()
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favoriteProducts()
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }

    public function likedBlogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_likes');
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }
}
