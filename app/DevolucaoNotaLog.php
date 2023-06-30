<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class DevolucaoNotaLog extends Model
{
    use SoftDeletes;

    public $fillable = [
        'devolucao_nota_id',
        'acao',
        'mensagem',
        'usuario',
        'nota_id_antigo',
        'nome_contato_antigo',
        'telefone_contato_antigo',
        'email_contato_antigo',
        'motivo_antigo',
        'status_antigo',
        'valor_parcial_antigo',
        'valor_antigo',
        'observacao_antigo',
        'nota_id_novo',
        'nome_contato_novo',
        'telefone_contato_novo',
        'email_contato_novo',
        'motivo_novo',
        'status_novo',
        'valor_parcial_novo',
        'valor_novo',
        'responsabilidade_frete',
        'observacao_novo',
        'laudo_imagem',
        'arquivo'
    ];

    public function devolucao_nota(){
        return $this->hasOne('App\DevolucaoNota', 'id', 'devolucao_nota_id');
    }

    public function usuario_detalhes(){
        return $this->hasOne('App\User', 'id', 'usuario');
    }

    public function nota_antiga(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'nota_id_antigo');
    }
    
    public function nota_nova(){
        return $this->hasOne('App\NotaVendaNasajon', 'id_nota', 'nota_id_novo');
    }

    public function motivo_antigo_detalhes(){
        return $this->hasOne('App\DevolucaoNotaMotivo', 'id', 'motivo_antigo');
    }

    public function motivo_novo_detalhes(){
        return $this->hasOne('App\DevolucaoNotaMotivo', 'id', 'motivo_novo');
    }


    public function status_antigo_detalhes(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'status_antigo');
    }

    public function status_novo_detalhes(){
        return $this->hasOne('App\DevolucaoNotaStatus', 'id', 'status_novo');
    }

    public function itens(){
        return $this->hasMany('App\DevolucaoNotaLogItens', 'devolucao_nota_log_id', 'id');
    }

}
