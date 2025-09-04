<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogKirimResep extends Model
{
    protected $connection = 'apol';
    protected $table = 'log_kirim_resep';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];

    public function detil()
    {
        return $this->hasMany(LogKirimResepDetil::class, 'KUNJUNGAN', 'KUNJUNGAN');
    }
}
