<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'start', 'end', 'revenue', 'cost', 'actual', 'trade_id', 'project_id', 'enabled', 'parent_id'
    ];

    public function trade()
    {
        return $this->belongsTo('App\Models\Trade', 'trade_id');
    }

    //for self-referencing
    public function parent()
    {
        return $this->belongsTo('Trade', 'parent_id');
    }
}
