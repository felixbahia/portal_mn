<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NasajonEstabelecimento extends Model
{
	protected $connection = 'nasajon';
	protected $table = 'ns.estabelecimentos';
	public $timestamps = false;
	public $incrementing = false;
	public $primaryKey = 'estabelecimento';
	protected $keyType = 'string';

	public $fillable = [
		'codigo', 'descricao', 'tipoidentificacao', 'raizcnpj', 'ordemcnpj', 'cpf', 'caepf', 'cidade', 'inscricaoestadual', 'inscricaomunicipal', 'nomefantasia', 'email', 'site', 'tipologradouro', 'logradouro', 'numero', 'complemento', 'bairro', 'cep', 'tiposimples', 'dddtel', 'telefone', 'dddfax', 'fax', 'bloqueado', 'selecionarcfop', 'ramoatividade', 'qualificacao', 'naturezapj', 'anofiscal', 'inicio_atividades', 'final_atividades', 'dataregistro', 'suframa', 'atividademunicipal', 'atividadeestadual', 'registro', 'representante', 'cpfrepresentante', 'dddtelrepresentante', 'telefonerepresentante', 'ramalrepresentante', 'dddfaxrepresentante', 'faxrepresentante', 'emailrepresentante', 'caixapostal', 'ufcaixapostal', 'cepcaixapostal', 'fpas', 'acidentetrabalho', 'numeroproprietarios', 'numerofamiliares', 'numeroconta', 'tipopagamento', 'codigoterceiros', 'porte', 'fazpartepat', 'aliquotafilantropica', 'capitalsocial', 'observacao', 'pagapis', 'tipoconta', 'inicioexercicio', 'cei', 'datanascimentorepresentante', 'sexorepresentante', 'contacorrentepagadora', 'ibge', 'cnae', 'identificacaoregistro', 'agencia', 'contador', 'empresa', 'estabelecimento', 'contribuinteipi', 'sindicato', 'tipocontroleponto', 'centralizacontribuicaosindical', 'nomecontato', 'cpfcontato', 'telefonefixocontato', 'dddtelfixocontato', 'telefonecelularcontato', 'dddtelcelularcontato', 'faxcontato', 'dddfaxcontato', 'emailcontato', 'classificado', 'excessosublimite', 'aliquotaaplicavel', 'logotipo', 'alelocodigopessoajuridica', 'alelonumerofilial', 'nisrepresentante', 'dataimplantacaosaldo', 'regraponto', 'contribuinteicms', 'id_centro_custo', 'centralizadorsefip', 'tipocaepf', 'indicatiocontratacaoaprendiz', 'processoaprendiz', 'aprendizcontratadoporintermedio', 'instituicaoeducativa', 'indicativocontratacaopcd', 'processocontratacaopcd', 'processofap', 'processorat', 'periodoapuracaopontoproprio', 'matriz', 'id_pessoa', 'importacao_hash', 'estabelecimento_multinota', 'lastupdate', 'codigoexterno', 'entidadeinscritapaa', 'tenant', 'desabilitado_persona', 'armazem'
	];

	public function endereco(){
		return $this->hasOne('App\CepEndereco', 'cep', 'cep');
	}

	public function pessoa(){
		return $this->hasOne('App\PessoasNasajon', 'id', 'id_pessoa');
	}

	public function empresaDetalhes(){
		return $this->hasOne('App\EmpresaNasajon', 'empresa', 'empresa');
	}

	public function cidadeDetalhes(){
		return $this->hasOne('App\CepCidade', 'cod_ibge', 'ibge');
	}
}
