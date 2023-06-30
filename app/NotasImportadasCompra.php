<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasImportadasCompra extends Model
{
    protected $fillable = [
        "id",
        "nota_id",
        "estabelecimento_id",
        "estabelecimento_codigo",
        "estabelecimento_nome",
        "documento_numero",
        "emissao",
        "fornecedor_id",
        "fornecedor_codigo",
        "fornecedor_nome",
        "fornecedor_documento",
        "valor_total",
        "codigo_operacao",
        "descricao_operacao",
        "transportadora_id",
        "transportadora_codigo",
        "transportadora_nome",
        "transportadora_documento",
        "modalidade_frete",
        "quantidade",
        "peso_liquido",
        "desconto",
        "valor_frete",
        "valor_seguro",
        "valor_outras_despesas",
        "valor_ipi",
        "valor_icms",
        "pedido",
        "data_entrada",
        "chave",
        "informacao_complementar",
        "score"
    ];

    public function itens_nota(){
        return $this->hasMany('App\NotasImportadasComprasIten', 'notas_importadas_compras_id', "id");
    }

    public function scoreRespondido(){
        return $this->hasMany('App\ScoreFornecedoresNotasLancamento', 'notas_importadas_compra_id', "id");
    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'id', "fornecedor_id");
    }
}
