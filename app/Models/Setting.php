<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id','key','value','user_id'
    ];

    public function project(){
        return $this->belongsTo('App\Models\Project', 'project_id');
    }

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id');
    }
}
