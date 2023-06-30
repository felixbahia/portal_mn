<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PagamentoPixNasajon extends Model
{
    use \Awobaz\Compoships\Compoships;
    
    protected $connection = 'nasajon';
    protected $table = 'financas.vw_pix';
    public $timestamps = false;
    
	protected $fillable = [
        'pix_id','pix_txid','pix_idconvenio','pix_infopagador','pix_endtoendid','pix_horario','pix_valor','pix_datainsercao','pix_dataatualizacao','pix_request','pix_response','pix_tenant',
        'lastupdate','pix_idpessoa','pix_idtitulo','pix_situacao','titulo_numero','titulo_emissao','titulo_vencimento','titulo_competencia','pessoa_nome','pessoa_cnpj','conta_id',
        'conta_codigo','conta_nome','conta_numero','conta_digito','conta_agencianumero','conta_agenciadigito','pixpagador_id','pixpagador_cnpj','pixpagador_cpf','pixpagador_nome',
        'pixpagador_datainsercao','pixpagador_dataatualizacao','pixpagador_request','pixpagador_response','pixpagador_tenant','pixdevolucao_id','pixdevolucao_rtrid','pixdevolucao_status',
        'pixdevolucao_valor','pixdevolucao_datainsercao','pixdevolucao_dataatualizacao','pixdevolucao_request','pixdevolucao_response','pixdevolucao_tenant','pix_chavepix'

    ];
   
    public function cliente(){
        return $this->hasOne('App\ClienteNasajon', 'id', 'pix_idpessoa');

    }

    public function fornecedor(){
        return $this->hasOne('App\FornecedorNasajon', 'id', 'pix_idpessoa');
    }

    

      

    
}
