<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogsNaturezaProjeto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'id','chave','descricao','obs','created_by','updated_by','deleted_by','status_projeto_exibicao_id'
    ];

   protected $dates = ['deleted_at'];

    public function status_exibicao(){
        return $this->hasOne('App\StatusProjetoExibicao', 'id', 'status_projeto_exibicao_id');
    }
}
