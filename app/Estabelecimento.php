<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Estabelecimento extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBPAE1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['ESTABEL'];

    protected $fillable = ['ESTABEL', 'APELIDO', 'ENDERECO', 'COMPLEMENTO', 'BAIRRO', 'CIDADE', 'ESTADO', 'CEP', 'CGC', 'IEST', 'IMUN', 'FONE_DDD', 'FONE_NUMERO', 'FONE_RAMAL', 'FAX', 'EMAIL', 'NREG_JUNTA', 'DATA_JUNTA', 'NIRE', 'POSTO_FISCAL', 'CONTATO', 'CONTADOR_NOME', 'CONTADOR_CPF', 'CONTADOR_CRC', 'CONTADOR_FONE', 'CONTADOR_EMAIL', 'FLAG_IAD', 'VARRE_HORA_INI', 'VARRE_HORA_FIM', 'VARRE_INTERVALO', 'VARRE_DHULT_MANUAL', 'VARRE_DHULT_AGENDADO', 'RAZAO_PAE', 'USO_CLIENTE_ALFA', 'ULTFOL_MAPA_ECF', 'DATA_INICIO_PL', 'HR_FORCA_LOTE_MANUAL'];

    public function regiao(){
    	$this->hasOne("App\CepCidade", 'uf', 'UF');
    }

}