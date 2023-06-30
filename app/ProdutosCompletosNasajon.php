<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProdutosCompletosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'integracoes.vw_produtos_completo';
    public $timestamps = false;
    public $incrementing = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'produto', ' codigo', ' especificacao', ' precovenda', ' unidade', ' marca', ' grupo', ' linha', ' precovenda_dolar', ' subgrupo', ' ncm', ' composicao', ' largura', ' gramatura', ' codigodebarras', ' pesobruto', ' pesoliquido', ' ipi', ' incentivo_sp', ' origemmercadoria', ' data_criacao', ' controlalote', ' figuratributaria', ' grupodeinventario', ' controlafracao', ' id_item'

    ];
}
