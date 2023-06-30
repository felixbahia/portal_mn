<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatusProjeto extends Model
{
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    
    protected $table = 'status_projeto'; 
    protected $fillable = [
        'id','posicao','descricao','status_projeto_exibicao_id','created_by','updated_by','deleted_by'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

   protected $dates = ['deleted_at'];

    public function status_exibicao(){
        return $this->hasOne('App\StatusProjetoExibicao', 'posicao', 'status_projeto_exibicao_id');
    }
    public function createdby(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function updatedby(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletedby(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

}
