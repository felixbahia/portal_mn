<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ComissaoDataFechamento extends Model
{
    use SoftDeletes;
    
    public $fillable = [
        'periodo',
        'data_inicio',
        'data_fim',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $dates = [
        'data_inicio',
        'data_fim'
    ];

    public function createdBy(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function updatedBy(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletedBy(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
