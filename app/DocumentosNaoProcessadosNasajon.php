<?php

namespace App;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Model;

class DocumentosNaoProcessadosNasajon extends Model
{
    protected $connection = 'nasajon';
    protected $table = "servicedocument.vwdocumentos";

    protected $guarded = [
        'documento',
        'tipo',
        'xml',
        'status',
        'operacao',
        'chave',
        'identificador',
        'id_docfis',
        'mensagem_retorno',
        'resposta',
        'datahora_inclusao',
        'cancelamento_sat_exportado',
        'status_email',
        'log_email',
        'excluido',
        'chave_emissao',
        'lastupdate',
        'tenant',
    ];

    public function pedidoVendaNasajon(){
        return $this->hasOne('App\PedidosVendaNasajon', 'id', 'id_docfis');
    }

    public function notaNasajon(){
        return $this->hasOne('App\NotasNasajon', 'id', 'id_docfis');
    }
}
