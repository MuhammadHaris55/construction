<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'start','end','revenue','cost','actual','trade_id','enabled'
    ];

    public function trade(){
        return $this->belongsTo('App\Models\Trade', 'trade_id');
    }

}
