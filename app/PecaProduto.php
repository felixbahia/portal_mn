<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PecaProduto extends Model
{
    use SoftDeletes;
    protected $fillable = ['produto', 'peca', 'codigo_barras', 'endereco_empresa_id', 'status_peca_id'];

    public function endereco(){
        return $this->belongsTo('App\PecaProduto', 'endereco_empresa_id', 'id');
    }

    public function status(){
        return $this->belongsTo('App\PecaProduto', 'status_peca_id', 'id');
    }
}
