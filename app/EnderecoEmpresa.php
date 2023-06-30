<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnderecoEmpresa extends Model
{
    use SoftDeletes;
    protected $fillable = ['endereco', 'codigo_barras'];

    public function pecas(){
        return $this->belongsTo('App\PecaProduto', 'endereco_empresa_id', 'id');
    }
}
