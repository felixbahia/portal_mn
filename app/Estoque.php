<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBEST2';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['CODPRD', 'ESTABEL'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'CODPRD', 'EST_PRATELEIRA', 'EST_DEPOSITO', 'EST_DEFEITO', 'EST_FORNECEDOR', 'EST_CONSERTADO', 'EST_SUCATEADO', 'EST_CONSIGNACAO', 'EST_CONSIGNADO', 'RESERVA', 'EMPENHO', 'DTESTZERO', 'DTULTINVENTARIO', 'CONGELADO', 'DTULTVENDA', 'VENDA_PERIODOATU', 'PERDA_PERIODOATU', 'PERDA_PORPRECOATU', 'VENDA_PERIODOANT', 'PERDA_PERIODOANT', 'PERDA_PORPRECOANT', 'VENDA_MESATU', 'PERDA_MESATU', 'VENDAMES_1', 'VENDAMES_2', 'VENDAMES_3', 'VENDAMES_4', 'VENDAMES_5', 'VENDAMES_6', 'VENDAMES_7', 'VENDAMES_8', 'VENDAMES_9', 'VENDAMES_10', 'VENDAMES_11', 'VENDAMES_12', 'PERDAMES_1', 'PERDAMES_2', 'PERDAMES_3', 'PERDAMES_4', 'PERDAMES_5', 'PERDAMES_6', 'PERDAMES_7', 'PERDAMES_8', 'PERDAMES_9', 'PERDAMES_10', 'PERDAMES_11', 'PERDAMES_12', 'PMEDIO_CICM', 'PMEDIO_SICM', 'FLAG_IAD', 'ESTABEL', 'EST_MINIMO'
    ];

    public function estoquePortal(){
        return $this->hasOne('App\ProdutosEstoque', 'codigo_produto', 'CODPRD')->where('estabelecimento', $this->ESTABEL);
    }
}
