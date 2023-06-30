<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPRD1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODPRD';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'CODPRD', 'DESCR', 'MARCA', 'COMPOSICAO', 'LINHA', 'GRUPO', 'SUBGRUPO', 'NUMFABRICA', 'CODBARRA', 'PROCEDENCIA', 'TRIBICM', 'ALIQICM', 'TRIBECF', 'CODMSG', 'CLASFISC', 'ALIQIPI', 'UNIDADE_CMP', 'UNIDADE_VND', 'ECONOMICO_CMP', 'PESOLIQ', 'SIMILAR', 'FLAG_INATIVO', 'LOCAL1', 'LOCAL2', 'CURVA_VALOR', 'CURVA_FREQ', 'FATOR_MINIMO', 'FATOR_MAXIMO', 'DATA_ROTATIVO', 'PRCVND_PREFIX_A', 'PRCVND_PREFIX_V', 'PRCVND_OFERTA_A', 'PRCVND_OFERTA_V', 'DTMAX_OFERTA', 'PRCVND_BASE', 'DTPRCBASE', 'PRCVND_BASE_ANT', 'DTPRCBASE_ANT', 'MARGEMBASE', 'COMISSAO', 'INIBIDO_COMISSAO', 'INIBIDO_REPASSE', 'INIBIDO_DESCONTO', 'INIBIDO_COTA', 'ULTCMP_FOR1', 'ULTCMP_DATA1', 'ULTCMP_QTD1', 'ULTCMP_DESTINO1', 'ULTCMP_PRECO1', 'ULTCMP_PRZMED1', 'ULTCMP_FOR2', 'ULTCMP_DATA2', 'ULTCMP_QTD2', 'ULTCMP_DESTINO2', 'ULTCMP_PRECO2', 'ULTCMP_PRZMED2', 'ULTCMP_FOR3', 'ULTCMP_DATA3', 'ULTCMP_QTD3', 'ULTCMP_DESTINO3', 'ULTCMP_PRECO3', 'ULTCMP_PRZMED3', 'ULTCMP_BASE_PRECO', 'ULTCMP_BASE_DATA', 'ULTLPR_BASE_PRECO', 'ULTLPR_BASE_DATA', 'GARANTIA', 'UNIDGAR', 'RECEITA_PADRAO', 'DHALTCAD', 'FLAG_IAD', 'TIPO_PREMIO', 'PREMIO', 'INIBIDO_PREMIO', 'USO_CLIENTE_ALFA', 'USO_CLIENTE_NUM', 'MARGEMATACADO', 'EXTIPI', 'IND_NVE'
    ];

    public function comprasFuturas(){
        return $this->belongsTo('App\ComprasFuturas', 'CODPRD', 'CODPRD');
    }

    public function infoProduto(){
        return $this->belongsTo('App\PedidoPortal', 'cod_produto', 'CODPRD');
    }

    public function informacoes_adicionais(){
        return $this->hasOne('App\InformacaoAdicionalProduto', 'cod_produto', 'CODPRD');
    }

    public function proforma_produto(){
        return $this->hasMany('App\ProformaProduto', 'CODPRD', 'CODPRD');
    }

    public function compras(){
        return $this->hasMany('App\ConsultaProdutoComprasView', 'CODPRD', 'CODPRD');
    }
    public function estoque(){
        return $this->hasMany('App\Estoque', 'CODPRD', 'CODPRD');
    }

    public function preco(){
        return $this->hasOne('App\Preco','codigo_produto', 'CODPRD');
    }
}
