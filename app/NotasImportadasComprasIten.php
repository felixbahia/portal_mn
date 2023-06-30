<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NotasImportadasComprasIten extends Model
{
    protected $fillable = [
        "id",
        "nota_id",
        "notas_importadas_compras_id",
        "produto_codigo",
        "produto_descricao",
        "ncm",
        "cfop",
        "unidade",
        "quantidade",
        "valor_unitario",
        "valor_total",
        "icms_aliquota",
        "icms_base",
        "icms_valor",
        "icms_aliquota_st",
        "icms_base_st",
        "icms_valor_st",
        "ipi_aliquota",
        "ipi_base",
        "ipi_valor",
    ];

    public function itens_nota(){
        return $this->hasOne('App\NotasImportadasCompra', 'id', "notas_importadas_compras_id");
    }
}
