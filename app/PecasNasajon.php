<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PecasNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = 'estoque.produtoslotes';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'produtolote';
    protected $fillable = [
        'produtolote', 'produto', 'codigo', 'descricao', 'validade', 'bloqueado', 'estoqueminimo', 'estoquemaximo', 'fabricacao', 'transformacaoproducao_origem', 'transformacaoproducaoitem_origem', 'dfitemlote_origem', 'situacao', 'producao_ordemdeproducao_origem', 'producao_ordemdeproducao_item_origem', 'codigoagregacao', 'lastupdate', 'tenant', 'id_docfis', 'dataentrada', 'numerodf', 'volume', 'fornecedor', 'detentor', 'df_item', 'utilizar_volume_beep', 'produtolote_pai', 'particionado', 'codigointerno', 'processamentoop_item_origem'
    ];
}
