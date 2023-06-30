<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
{
    protected $connection = 'srv_prologos';
    protected $table = 'TBVND1';
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'CODVND';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    	'CODVND', 'TIPVND', 'NOME', 'CEP', 'ENDERECO', 'BAIRRO', 'CIDADE', 'ESTADO', 'FONE', 'EMAIL', 'INDCOMIS', 'TIPCOMIS', 'COMISSAO', 'ATIVIDADE', 'FLAG_COTAS', 'META_VALOR', 'META_PORC1', 'META_PREMIO1', 'META_PORC2', 'META_PREMIO2', 'META_PORC3', 'META_PREMIO3', 'ULTNUMPED', 'DHALTCAD', 'ESTABEL', 'FLAG_IAD', 'TIPCOMIS_SERVICO', 'COMISSAO_SERVICO', 'RATEIO_VENDA_AVULSA', 'TIPCOMIS_EQUIPE', 'COMISSAO_EQUIPE', 'CHEFE_EQUIPE', 'CODSENHA', 'CELULAR', 'CNPJ_CPF', 'IE_RG', 'INICIO_NASC', 'SENHA_COMERCIAL', 'COD_BCO', 'AGE_BCO', 'CTA_BCO', 'FAVORECIDO_BCO', 'CODREGIAO', 'CODAREA', 'CODZONA', 'SUPERVISOR', 'GERENTE', 'CODUSU_VINC', 'PREPOSTO_VINC', 'CNPJ_CPF_CTA', 'OBS'
    ];
    
}
