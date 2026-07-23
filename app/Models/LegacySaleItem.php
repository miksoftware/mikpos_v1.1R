<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacySaleItem extends Model
{
    protected $table = 'detalleventas';
    protected $primaryKey = 'coddetalleventa';
    public $timestamps = false;
    protected $guarded = [];

    public function sale()
    {
        return $this->belongsTo(LegacySale::class, 'codventa', 'codventa');
    }
}
