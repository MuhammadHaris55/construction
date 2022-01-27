<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name','start','end','revenue','cost','actual','project_id','enabled', 'parent_id'
    ];

    public function project(){
        return $this->belongsTo('App\Models\Project', 'project_id');
    }

    public function items()
    {
        return $this->hasMany('App\Models\Item', 'trade_id');
    }

    //for self-referencing
    public function parent()
    {
        return $this->belongsTo('Trade', 'parent_id');
    }

    public function children()
    {
        return $this->hasMany('Trade', 'parent_id');
    }

}
