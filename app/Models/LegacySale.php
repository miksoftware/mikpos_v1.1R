<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegacySale extends Model
{
    protected $table = 'ventas';
    protected $primaryKey = 'idventa';
    public $timestamps = false;
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(LegacySaleItem::class, 'codventa', 'codventa');
    }
}
