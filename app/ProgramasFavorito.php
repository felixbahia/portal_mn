<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProgramasFavorito extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'programas_id'
    ];

    public function user(){
        return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function programa(){
        return $this->hasOne('App\Programa', 'id', 'programas_id');
    }
    
}
