<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmpresaNasajon extends Model
{
	protected $connection = 'nasajon';
	protected $table = 'ns.empresas';
	public $timestamps = false;
	public $incrementing = false;
    public $primaryKey = 'empresa';
    protected $keyType = 'string';
    
    public $fillable = [
        'codigo',
        'descricao',
        'tipoidentificacao',
        'raizcnpj',
        'ordemcnpj',
        'cpf',
        'razaosocial',
        'mascaraconta',
        'mascaragerencial',
        'usadv',
        'filantropica',
        'cnae',
        'naturezajuridica',
        'tipopagamento',
        'tipocooperativa',
        'tipoconstrutora',
        'numerocertificado',
        'ministerio',
        'dataemissaocertificado',
        'datavencimentocertificado',
        'numeroprotocolorenovacao',
        'dataprotocolorenovacao',
        'datapublicacaodou',
        'numeropaginadou',
        'nomecontato',
        'cpfcontato',
        'telefonefixocontato',
        'dddtelfixocontato',
        'telefonecelularcontato',
        'dddtelcelularcontato',
        'faxcontato',
        'dddfaxcontato',
        'emailcontato',
        'inativa',
        'inicioexercicio',
        'logotipo',
        'imagemoriginal',
        'infoimagem',
        'empresa',
        'grupoempresarial',
        'moeda',
        'idweb',
        'perfil',
        'inicio_atividades',
        'tributacaopiscofins',
        'alelonumerocontrato',
        'tipopontoeletronico',
        'multiplastabelasrubrica',
        'numerosiafi',
        'acordointernacionalisencaomulta',
        'tiposituacaopj',
        'tiposituacaopf',
        'regimeproprioprevidenciasocial',
        'municipioentefederativo',
        'descricaoleiseguradodiferenciado',
        'valorsubtetoexecutivo',
        'valorsubtetolegislativo',
        'valorsubtetojudiciario',
        'valorsubtetotodospoderes',
        'anosmaioridadedependenteexecutivo',
        'anosmaioridadedependentelegislativo',
        'anosmaioridadedependentejudiciario',
        'anosmaioridadedependentetodospoderes',
        'observacao',
        'regraponto',
        'usapontoweb',
        'entidadeeducativa',
        'empresadetrabalhotemporario',
        'entefederativo',
        'cnpjentefederativo',
        'subtetoentefederativo',
        'valorsubtetoentefederativo',
        'numeroregistrotrabalhotemporariomte',
        'esocialativo',
        'importacao_hash',
        'empresa_multinota',
        'tenant_multinotas',
        'lastupdate',
        'tenant',
        'optantesegurovida',
        'optantesegurofuneral',
        'optantepcmso',
        'codigoean',
        'decimaiscusto',
    ];

    public function estabelecimentos(){
        return $this->hasMany('App\NasajonEstabelecimento', 'empresa', 'empresa');
    }
};
