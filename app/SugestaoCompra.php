<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SugestaoCompra extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'produto_codigo','produto_descricao','cliente_codigo','cliente_nome','volume_produto','valor_estimado_venda', 'status','created_by', 'updated_by', 'deleted_by','composicao'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    public function produto(){
        return $this->hasOne('App\ProdutoNasajon', 'codigo', 'produto_codigo');
    }
    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'cliente_codigo');
    }

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }

    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }

    public function deletadoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }
}
