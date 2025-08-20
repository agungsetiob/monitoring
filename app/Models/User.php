<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Tambahkan konstanta untuk setiap peran
    public const ROLE_IGD = 'igd';
    public const ROLE_APOTEK = 'apotek';
    public const ROLE_ADMIN = 'admin';

    // Tambahkan array untuk mendapatkan semua peran yang tersedia
    public static function getRoles(): array
    {
        return [
            self::ROLE_IGD,
            self::ROLE_APOTEK,
            self::ROLE_ADMIN,
        ];
    }

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];
}