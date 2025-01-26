<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'email',
        'password',
        'role',
        'status',
        'available',
    ];

    protected $date = ['deleted_at'];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // 'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function isAdmin()
    {
        $check =  User::where('id', auth()->user()->id)->where('role', 1)->exists();;
        if($check) {
            return true;
        } else {
            return false;
        }
    }
    public static function users()
    {
        return User::where('role', '!=', 1)->get();
    }

    public function user_data()
    {
        return $this->hasOne(UserData::class, 'user_id');
    }

    public function update_history()
    {
        return $this->hasMany(UserManageHistory::class, 'user_id', 'id');
    }
    public function dakou()
    {
        return $this->hasMany(DakouData::class, 'dp_user', 'id');
    }

    public function scopeSyukin()
    {
        return DakouData::where('target_date', date('Y-m-d'))->where('dp_user', auth()->user()->id)->exists();
    }

    public function scopeTaikin()
    {
        return DakouData::where('target_date', date('Y-m-d'))->where('dp_user', auth()->user()->id)->where('dp_status', 2)->exists();
    }
}
