<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiberacaoPilotagem extends Model
{
    use SoftDeletes;

    protected $connection = 'pgsql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'lancamentos_deb_cred_vendedor_id',
        'estabelecimento',
        'users_id',
        'nota_numero',
        'nota_id',
        'valor_credito',
        'valor_desconto',
        'parcela',
        'comissao_atual',
        'comissao_alterada',
        'motivos_financeiros_id',
        'status_liberacao_pilotagems_id',
        'pedido_numero',
        'pedido_nasajon_id',
        'titulo_numero',
        'titulo_id',
        'titulo_data_emissao',
        'titulo_data_vencimento',
        'tipo_ajuste',
        'titulo_valor',
        'created_by',
        'updated_by',
        'deleted_by'
    ];
    
    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    public function criadoPor(){
        return $this->hasOne('App\User', 'id', 'created_by');
    }
    public function atualizadoPor(){
        return $this->hasOne('App\User', 'id', 'updated_by');
    }
    public function deletadoPor(){
        return $this->hasOne('App\User', 'id', 'deleted_by');
    }

    public function representante(){
        return $this->hasOne('App\User', 'id', 'users_id');
    }
    
    public function statusLiberacaoPilotagem(){
        return $this->hasOne('App\StatusLiberacaoPilotagem', 'id', 'status_liberacao_pilotagems_id');
    }

    public function tituloPagamentoNasajon(){
        return $this->hasOne('App\TituloPagamentoNasajon', 'documento_id', 'nota_id');
    }

    public function lancamentoDebCredVendedor(){
        return $this->hasOne('App\LancamentoDebCredVendedor', 'id', 'lancamentos_deb_cred_vendedor_id');
    }

    public function motivoFinanceiro(){
        return $this->hasOne('App\MotivoFinanceiro', 'id', 'motivos_financeiros_id');
    }
}
