<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProdutoPromocional extends Model
{
    use SoftDeletes;
    protected $table = 'produtos_promocionais';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'grupo',
        'codigo_produto', 
        'codigo_estabelecimento', 
        'tipo_frete', 
        'codigo_cliente', 
        'codigo_vendedor', 
        'preco_real', 
        'preco_dolar',
        'data_expiracao', 
        'created_by', 
        'updated_by', 
        'deleted_by',
        'sem_desconto_adicional',
        'desconto_porcentagem'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function produto(){
        return $this->hasOne('App\ProdutoEspecificacao', 'codigo_produto', 'codigo_produto');
    }

    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'codigo', 'codigo_cliente');
    }

    public function vendedor(){
        return $this->hasOne('App\User', 'id', 'codigo_vendedor');
    }

    public function created_detalhes(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
}
