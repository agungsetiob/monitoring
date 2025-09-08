<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogKirimResepDetil extends Model
{
    protected $connection = 'apol';
    protected $table = 'log_kirim_resep_detil';
    protected $primaryKey = 'ID';

    public $timestamps = false;

    protected $guarded = [];

    public function resep()
    {
        return $this->belongsTo(LogKirimResep::class, 'KUNJUNGAN', 'KUNJUNGAN');
    }
}
