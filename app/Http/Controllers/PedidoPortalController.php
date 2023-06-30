<?php

namespace App\Http\Controllers;

use Carbon\Carbon;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\AliquotaPreco;
use App\AprovacaoDePedido;
use App\AuditoriaComissao;
use App\CarrinhoCompra;
use App\CepEndereco;
use App\CepEstado;
use App\CondicoesPagamentoWeb;
use App\EstabelecimentoCidadeFob;
use App\MargemPrazo;
use App\ParametrosPedido;
use App\PedidoItemPortal;
use App\PedidoPortal;
use App\StatusPedido;
use App\Transportador;
use App\User;
use App\ClienteNasajon;
use App\TransportadorNasajon;
use App\PedidosVendaNasajon;
use App\ProdutoNasajon;
use App\HistoricoPedido;
use App\ProdutoEspecificacao;
use App\Cotacoes;
use App\ClientePrePago;
use App\LancamentoProjeto;
use App\HistoricoProjeto;
use App\ProdutosEstoque;
use App\ComprasNasajon;
use App\ClienteBionexo;
use App\NotasCreditoReceberNasajon;
use App\GrupoEmpresarial;
use App\PedidosReservaProdutoNasajon;
use App\PagarmeCodigoErro;
use App\ParametrosAprovacao;
use App\ProdutoPromocional;
use App\PedidoProposta;
use App\TransportadoraEstabelecimento;
use App\ClicksignDocumento;
use App\ProdutoUsoConsumoNasajon;

use App\Http\Controllers\EmailController;
use App\Http\Requests\ListaDePrecosRequest;
use App\Http\Requests\PedidoSalvarRequest;
use App\Http\Requests\PedidoPortalDuplicarRequest;
use App\Http\Requests\PedidoPortalEdicaoFuturaRequest;

use Illuminate\Http\Request;

use App\Http\Controllers\UserController;
use App\Http\Controllers\AprovacaoDePedidoController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\PedidoItemPortalController;
use App\Http\Controllers\ClienteAnaliseSinteticaController;
use App\Http\Controllers\UnidadeNegocioMetaController;
use App\Http\Controllers\StoneTransacaoController;

use Exception;
use Auth;
use PDF;

class PedidoPortalController extends Controller
{
    private $codigo_grupo_textil_mn = ['0050758840003', '05075884000167', '05075884000167', '05075884000248', '06', '06', '06311274000188', '06311274000188', '06311274000269', '06311274000340', '06311274000420', '06311274000501', '07', '08'];

    private $codigo_cliente_balcao = ['0000010069999'];

    private $razao_cnpj_textil = '06311274';

    public $successStatus = 200;
    public $errorStatus = 422;

    private $estabelecimentos = [];
    private $estabelecimentos_prologos = [];
    private $estabelecimentos_not_pedido_interno = [];
    private $estabelecimentos_not_pedido = [];
    private $estabelecimentos_producao = [];

    private $tipo_vendas = [];
    private $tipo_vendas_especial = [];
    private $tipo_vendas_futuro = [];
    private $tipo_vendas_triangular = [];
    private $tipo_vendas_interno = [];

    private $tipo_venda_pronta_entrega = [];

    private $tipo_frete = [];

    private $grupos_especiais = [];
    private $media_especial = 45;


    private $estabelecimentos_pecas = [];
    private $quantidade_pecas = 0;

    private $frete_fob_valor_minimo = 0;

    private $condicaoPagamentoCielo = [];

    private $quantidade_limite_pilotagem = 0;

    private $limite_credito_pilotagem = 300000;
    private $limite_credito_pilotagem_teto = 600000;
    private $limite_credito_pilotagem_porcentagem = 0.05;

    private $quantidade_metros_limite_pilotagem = 6;
    private $quantidade_kg_limite_pilotagem = 2;
    private $quantidade_undiade_limite_pilotagem = 1;

    private $estabelecimentos_venda_presencial_cartao = [];

    private $nasajon_forma_pagamento_cartao = ['c41decd7-935f-449a-9a60-fd1a2330661b', 'b0444787-b579-422d-af2a-ce691cbff825'];
    
    private $condicao_de_pagamento_usar_credito = [4202, 4221, 4247];

	private $estabelecimentos_venda_observacao = [];

    private $tipo_pronta_entrega = ['venda', 'isento', 'pedido_orgaopublico', 'pronta_entrega_triangular', 'pronta_entrega_venda'];
		
    private $status_nasajon = [
        'aberto'    => 'Aberto',
        'separando' => 'Em separação',
        'a_faturar' => 'Em Faturamento',
        'faturado'  => 'Faturado'
    ];

    private $status_nasajon_indice = [
        'aberto'    => 'aberto',
        'separando' => 'separando',
        'a_faturar' => 'a_faturar',
        'faturado'  => 'faturado'
    ];


    public $tipo_pre_pago = [
        'pre_pago',
        'pre_pago_futuro',
        'pre_pago_rj_x_sp',
        'pre_pago_rj_x_sp_futuro',
        'pre_pago_triangular'
    ];

    private $codigo_uso_consumo = [
		'056201600AM01',
        '056050883AM01',
        '0560994000AM01',
        '0560316FTAM01',
        '05825BALAAM01',
        '058010470AM01',
        '056051170AM01',
        '055000551AM01',
        '058048590AM01',
        '03256PBE2AM01',
        '058058000AM01',
        '0582030DTAM01',
        '056059811AM01',
        '0585000063310',
        '05605117FAM01',
        '0567709D0AM01',
        '056604000AM01',
        '056024FT0AM01',
        '01ANDORRA0001',
        '058051180AM01',
        '05605121DAM01',
        '056200600AM01',
        '056039500AM01',
        '0581629MVAM01',
        '1208000015',
        '0566741E0AM01',
        '056390100AM01',
        '0560310FTAM01',
        '01PIQREG0AM01',
        '05660327DAM01',
        '056009500AM01',
        '058161000AM01',
        '058048600AM01',
        '058CTCM22AM01',
        '0581850FTAM01',
        '058500161AMO0',
        '058500151AMO0',
        '056601FT0AM01',
        '0560512FTAM01',
        '056500000AM01',
        '014000000AMO0',
        '0581626MVAM01',
        '0560510E0AM01',
        '0570063CBAM01',
        '0565100E0AM01',
        '056039660AM01',
        '056014050AM01',
        '0560114F0AM01',
        '056014011AM01',
        '05600980EAM01',
        '058100750AM01',
        '0566421D0AM01',
        '0565011E0AM01',
        '056057001AM01',
        '056051197AME1',
        '058CTDM22AM01',
        '056612100AM01',
        '058CTSS22AM01',
        '056039600AM01',
        '0H1092505AM01',
        '058045100AM01',
        '056348009AM01',
        '05631700FTAM01',
        '0581609MGAM01',
        '0560510D0AM01',
        '056008910AM01',
        '056008800AM01',
        '056059425AM01',
        '0H1001PCE2AM01',
        '058CTRV22AM01',
        '03256GORG0AM01',
        '0576302CBAM01',
        '056039891AM01',
        '042108PQ00AM01',
        '056600220AM01',
        '05804260EAM01',
        '0568230D0AM01',
        '058220260AM01',
        '056600980AM01',
        '056001041AM01',
        '056053270AM01',
        '0582030DLAM01',
        '001195411AM01',
        '058CTFM22AM01',
        '056009020AM01',
        '0H1001PCLAM01',
        '056014380AM01',
        '056099300AM01',
        '0563690FTAM01',
        '0359110SUPAM01',
        '0566581D0AM01',
        '0582505PVAM01',
        '05603995DAM01',
        '056611300AM01',
        '05660529DAM01',
        '056316FT0AM01',
        '058DRP150AM01',
        '05603714DAM01',
        '056500065AM01',
        '05880241MAM01',
        '0583452TPAM01',
        '0563530FTAM01',
        '019FRALD0AM01',
        '0566002685AM01',
        '056039140AM01',
        '056099600AM01',
        '05601859DAM01',
        '056634900AM01',
        '058CTST22AM01',
        '0563170FTAM01',
        '0563650FTAM01',
        '0581605MMAM01',
        '0582504PPAM01',
        '056999800AM01',
        '05642108PAM01',
        '056603100AM01',
        '054001080AM01',
        '058385G97AM01',
        '057006003AM01',
        '0588805TVAM01',
        '056001040AM01',
        '0568010D0AM01',
        '056612300AM01',
        '055000541AM01',
        '05806BOBYAM01',
        '056600600AM01',
        '058260301AM01',
        '058162251AM01',
        '05631733EAM01',
        '056200800AM01',
        '05400MAX0AM01',
        '0H1001PC20AM01',
        '054MAXLI0AM02',
        '058161600AM01',
        '0560510CBAM01',
        '056389FT0AM01',
        '056607100AM01',
        '058010455AM01',
        '05806TEXTAM01',
        '05609895EAM01',
        '056201601AM01',
        '056600200AM01',
        '056058800AM01',
        '0560202D0AM01',
        '056059910AM01',
        '056670000AM01',
        '058B15001AM01',
        '056603000AM01',
        '05605147FTAM01',
        '057005820AM01',
        '0581628MVAM01',
        '056500100AM01',
        '0580116FMAM01',
        '0570060CBAM01',
        '05601995DAM01',
        '058CTPN22AM01',
        '0583352MPAM01',
        '0562005E0AM01',
        '02PILOTAGEMCT',
        '0560318FTAM01',
        '056390FT0AM01',
        '058CTCD22AM01',
        '056162251AM01',
        '056650270AM01',
        '056009001AM01',
        '058161900AM01',
        '056059540AM01',
        '0566128000AM01',
        '058161800AM01',
        '057006102AM01',
        '058721405AM01',
        '056660800AM01',
        '058041800AM01',
        'HAMOSTRA2019F',
        '0585065INAM01',
        '056059210AM01',
        '05819FRALAM01',
        '05600220DAM01',
        '056612800AM01',
        '0H1001PC2PAM01',
        '056020000AM01',
        '0581607MCAM01',
        '056022FT0AM01',
        '05600190LAM01',
        '058CTSA22AM01',
        '056099400AM01',
        '058162700AM01',
        '058063100AM01',
        '056610558AM01',
        '0566002583AM01',
        '0566410D0AM01',
        '056607585AM01',
        '056600111AM01',
        '056059520AM01',
        '058101990AM01',
        '0560677E0AM01',
        '056039100AM01',
        '015101011AM01',
        '058421070AM01',
        '001195400AM01',
        '056800900AM01',
        '056600360AM01',
        '05600911DAM01',
        '056051350AM01',
        '0560956D0AM01',
        '0581633MMAM01',
        '058CTCT22AM01',
        '056015120AM01',
        '01ANCARTW',
        '581600AMOSTRA',
        '0581291CSAM01',
        '056200199AM01',
        '0560530E0AM01',
        '0560515FTAM01',
        '025PERC0E0AM01',
        '056053260AM01',
        '056014383AM01',
        '05605105FAM01',
        '056039990AM01',
        '056610129AM01',
        '056059780AM01',
        '01LIGHTBLUE01',
        '057006200AM01',
        '056008515AM01',
        '05666779DAM01',
        '027110000AM01',
        '0580477SMAM01',
        '056612821AM01',
        '056200900AM01',
        '05605280DAM01',
        '05600121DAM01',
        '056039600AM02',
        '0581601MAAM01',
        '058470341AM01',
        '056099200AM01',
        '0560569D0AM01',
        '05821STUDAM01',
        '058CTTY22AM01',
        'AMOSTRA',
        '056652400AM01',
        '0581612MPAM01',
        '056059530AM01',
        '058CTAB22AM01',
        '0566602E0AM01',
        '057928SUPAM01',
        '0560113F0AM01',
        '0580670E0AM01',
        '056059030AM01',
        '0560064FTAM01',
        '056600180AM01',
        '056660500AM01',
        '0580462SLAM01',
        '056059920AM01',
        '0560517E0AM01',
        '0570058CBAM01',
        '058100730AM01',
        '058101471AM01',
        '058022026AM01',
        '0566377D0AM01',
        '058160600AM01',
        '058CTAW22AM01',
        '058101330AM01',
        '05601960DAM01',
        '056200200AM01',
        '058018000AM01',
        '056060FT0AM01',
        '05665223DAM01',
        '057006205AM01',
        '056054512AM01',
        '0581635MDAM01',
        '056059912AM01',
        '058580301AM01',
        '056222001AM01',
        '058161400AM01',
        '0566580D0AM01',
        '056800290AM01',
        '056600190AM01',
        '056009991AM01',
        '0359110PL0AM01',
        '058CTCV22AM01',
        '058160800AM01',
        '056052100AM01',
        '056654010AM01',
        '05662126DAM01',
        '05665223DAM02',
        '056624970AM01',
        '058104556AM01',
        '056600100AM01',
        '001194422AM01',
        '056699200AM01',
        '056099100AM01',
        '058710000AM01',
        '05665091DAM01',
        '014000001',
        '056608200AM01',
        '0566002785AM01',
        '057005900AM01',
        '060200300AM01',
        '056039670AM01',
        '056023FT0AM01',
        '058500191AMO0',
        '0560562D0AM01',
        '0H1816005AM01',
        '054MAXHI0AM01',
        '056201632AM01',
        '0586013PPAM01',
        '0H1001PCAAM01',
        '056622500AM01',
        '058CTCR22AM01',
        '056201700AM01',
        '056645100AM01',
        '05601860DAM01',
        '1208000013',
        '015101000AM01',
        '056009998AM01',
        '058044100AM01',
        '0H1816116AM01',
        '05600154DAM01',
        '056099500AM01',
        '056600310AM01',
        '054MAXPLUSAM01',
        '0H1001PC2PAC01',
        '058225V00AM01',
        '0567622D0AM01',
        '056009900AM01',
        '03256C0TL5050',
        '0580056SGAM01',
        '0580476SMAM01',
        '058225V86AM01',
        '0565010E0AM01',
        '056000000AM01',
        '058015170AM01',
        '05600135DAM01',
        '05806XADPAM01',
        '056335100AM01',
        '056051016AM01',
        '01FELPA15AM01',
        '056051011AM01',
        '056382000AM01',
        '0581638MDAM01',
        '0566443D0AM01',
        '056670098AM01',
        '001194400AM01',
        '056672400AM01',
        '056603111AM01',
        '056051010AM01',
        '056609429AM01',
        '056312310AM01',
        '05600622DAM01',
        'IT8010100225',
        '0580398000AM0',
        '056039650AM01',
        '057006302AM01',
        '056085150AM01',
        '057006GOLAM01',
        '056010000AM01',
        '056602100AM01',
        '056014690AM01',
        '056312309AM01',
        '003537003',
        '056039920AM01',
        '0567733D0AM01',
        '056658200AM01',
        '0560988D0AM01',
        '006476',
        '0568120D0AM01',
        '056604FT0AM01',
        '056560000AM01',
        '0583701FDAM01',
        '057005910AM01',
        '0359110H00AM01',
        '0560526FTAM01',
        '056051197AM01',
        '058100830AM01',
        '056056310AM01',
        '0560457F0AM01',
        '056658300AM01',
        '0563390100AM01',
        '056035650AM01',
        '3256AMOSTRA01',
        '056331FT0AM01',
        '056006313AM01',
        '056634860AM01',
        '056009700AM01',
        '056059220AM01',
        '056090828AM01',
        '0581630MVAM01',
        '058251049AM01',
        '0581634MTAM01',
        '056200400AM01',
        '05669101DAM01',
        '056317500AM01',
        '001195500AM01',
        '056600987AM01',
        '056099000AM01',
        '0225Z640AM001',
        '058104552AM01',
        '010002860AM01',
        '058500181AMO0',
        '056650300AM01',
        '056059926AM01',
        '058250400AM01',
        '05665310DAM01',
        '056604889AM01',
        '060200200AM01',
        '056371000AM01',
        '058108590AM01',
        '0550005CBAM01',
        '05605524DAM01',
        '0580670000AM0',
        '0580634000AM0',
        '0563510FTAM01',
        '058250300AM01',
        '056605FT0AM01',
        '058100820AM01',
        '056600280AM01',
        '056598000AM01',
        '056301800AM01',
        '0560010000AM01',
        '0560017L0AM01',
        '0225Y790AM001',
        '0570059CBAM01',
        '058049580AM01',
        '056601400AM01',
        '001194411AM01',
        '058470301AM01',
        '0560396CBAM01',
        '056600172AM01',
        '0550054CBAM01',
        '056612400AM01',
        '05631525EAM01',
        '0580461SLAM01',
        '0560500D0AM01',
        '0H3008114AM01',
        '058CTSJ22AM01',
        '058CTSF22AM01',
        '056610345AM01',
        '014000000AMO01',
        '056020100AM01',
        '056623010AM01',
        '05655100DAM01',
        '560479TTAM001',
        '054001090AM01',
        '05603999AM01',
        '056651300AM01',
        '025PERC00AM01',
        '05605210CRTLA',
        '056199600AM01',
        '056500522AM01',
        '058500141AMO0',
        '05660125DAM01',
        '0566010D0AM01',
        '058160400AM01',
        '0563171100AM01',
        '03256PCC0AM01',
        '05605600DAM01',
        '056612500AM01',
        '056800291AM01',
        '056069652AM01',
        '056987D00AM01',
        '056051150AM01',
        '035911M00AM01',
        '056039940AM01',
        '0565122D0AM01',
        '056988FT0AM01',
        '056600151AM01',
        '0582501PBAM01',
        '1208000003',
        '0563710FTAM01',
        '0560020LXAM01',
        '0566075D0AM01',
        '0563171D0AM01',
        '058051000AM01',
        '0560203D0AM01',
        '056600112AM01',
        '058275082AM01',
        '056670099AM01',
        '001193400AM01',
        '058CTAP22AM01',
        '056025FT0AM01',
        '056051110AM01',
        '035911010AM01',
        '056200700AM01',
        '010002850AM01',
        '03256B146AM01',
        '056019920AM01',
        '001193422AM01',
        '056201682AM01',
        '056670200AM01',
        '0582505CSAM01',
        '0560315D0AM01',
        '0580485CBAM01',
        '1208000002',
        '01193500AM01',
        '14000000AMO01',
        '05600200DAM01',
        '0582502BRAM01',
        '0580513TBAM01',
        '05600999LAM01',
        '05605120DAM01',
        '0560399E0AM01',
        '058500171AMO0',
        '057006301AM01',
        '01PIQUET00AM01',
        '057006000AM01',
        '0565980D0AM01',
        '056009990AM01',
        '056097710AM01',
        '056108600AM01',
        '0560988000AM01',
        '058CTAN22AM01',
        '0580180UMAM01',
        '0563194E0AM01',
        '056002200AM01',
        '0560988CBAM01',
        '0563160E0AM01',
        '0581603MNAM01',
        '056009650AM01',
        '056019500AM01',
        '001194500AM01',
        '058CTSG22AM01',
        '056201448AM01',
        '056600109AM01',
        '0583844DNAM01',
        '058101530AM01',
        '056026FT0AM01',
        '05665201DAM01',
        '058CTDA22AM01',
        '0560514D0AM01',
        '581631MMPAM01',
        '058CTVC22AM01',
        '058010630AM01',
        '058500046AMO0',
        '0581635MCAM01',
        '0560391CBAM01',
        '060PERK00AM01',
        '058047530AM01',
        '057612000AM01',
        '0566520FTAM01',
        '0560021000AM01',
        '56396AT001000',
        '056014160AM01',
        '0586123000AM0',
        '056600125AM01',
        '056050165AM01',
        '058H10955AM01',
        '056600490AM01',
        '058CTMNP1AM01',
        '0566089E0AM01',
        '01PIQUETSAM01',
        '05603401EAM01',
        '056300000AM01',
        '056051000AM01',
        '056069990AM01',
        '0582030DBAM01',
        '058CTVF22AM01',
        '058CTCG22AM01',
        '056059650AM01',
        '0580514TFAM01',
        '05601970DAM01',
        '0562007E0AM01',
        '056053000AM01',
        '001193411AM01',
        '0560518FTAM01',
        '1208CARTMNPRO',
        '056690000AM01',
        '060200400AM01',
        '058CTDW22AM01',
        '0580101MCAM01',
        '0560564D0AM01',
        '056053010AM01',
        '0H5026005AM01',
        '058047520AM01',
        '056201654AM01',
        '056650859AM01',
        '056059930AM01',
        '056098900AM01',
        '0580495CBAM01'
	];
    private $codigo_uso_consumo_id = [
        '056200900AM01' => '46e2e7f1-b792-4579-8e99-05016f04d647', 
        '056200300AM01' => '251cfe46-6201-4f6e-b285-b965f07135ca',
		'056200300AM01' => '251cfe46-6201-4f6e-b285-b965f07135ca',
		'056201600AM01' => '562471e2-bd62-41ee-9520-2b89e3649ee2',
        '056050883AM01' => '03c581f5-d090-4680-a44b-e4de1669c251',
        '0560994000AM01' => '420e771c-2eae-4614-bc45-c1e4627c7a9b',
        '0560316FTAM01' => '4577a083-ce50-47d7-8b99-7e9a99d7b0cb',
        '05825BALAAM01' => 'd2597afd-482f-4c56-a952-75150e1e0e9c',
        '058010470AM01' => 'd72d8c43-e170-45e4-bebb-a53741c745ad',
        '056051170AM01' => 'af2652db-4061-460d-8365-0dd4bd22c29b',
        '055000551AM01' => 'b98bba89-753b-4a41-9b62-28f48d172192',
        '058048590AM01' => 'f42abba0-aa5e-4a26-a9ac-a656a7f592fb',
        '03256PBE2AM01' => 'fc1755af-1b98-471d-920c-22bbf05e9135',
        '058058000AM01' => 'a16557ac-41f1-44f7-bb38-c8b8bbc2a61d',
        '0582030DTAM01' => '68c9660b-71f4-4bc3-a955-40b1a916cc2c',
        '056059811AM01' => '93dccb82-2aa1-4a79-b4c5-35c429766b9a',
        '0585000063310' => '136858c7-b882-41b8-aa80-5c8412a1a87d',
        '05605117FAM01' => '0748adc8-75dd-46e8-bea5-cc224ecb1f07',
        '0567709D0AM01' => '3f054a0d-6d1c-4625-ad19-2eb5df0bd89e',
        '056604000AM01' => '6fc6a9d9-017e-449b-b66c-17116d901ada',
        '056024FT0AM01' => '4154c3a6-0e9f-4bfb-bb4a-56e1577bf259',
        '01ANDORRA0001' => 'ba2788f6-b0fe-4b45-9146-a96e87b3776e',
        '058051180AM01' => 'b8ae2824-27a6-4978-afc1-2a5c98889e27',
        '05605121DAM01' => '634ec6b1-38cf-4f07-9598-f9f814271268',
        '056200600AM01' => 'f716254a-85a9-4d7f-9878-34017a103a53',
        '056039500AM01' => 'ffa833eb-c323-49df-b5df-eb69ab223d5b',
        '0581629MVAM01' => 'c0e41046-048e-4eeb-9ae2-acdba24f45ab',
        '1208000015' => '0b016bae-2203-4e36-ab6b-1acd831bd820',
        '0566741E0AM01' => '4233c393-8664-4271-98b7-85038d027711',
        '056390100AM01' => '356833f9-5fe8-47b3-bba8-0e614efcdd3d',
        '0560310FTAM01' => 'e59e43f7-0446-4f73-9005-d92a4497c309',
        '01PIQREG0AM01' => '0120b7f0-fa38-4a7e-8b9f-d5b9e3bc7f6c',
        '05660327DAM01' => 'fd9110a4-66dd-4dd1-a5f0-692962c67b95',
        '056009500AM01' => '3ad9cc31-9386-4387-9d23-962112c7338a',
        '058161000AM01' => '34a13b6e-aad1-435a-bde9-dc6c3ad8531b',
        '058048600AM01' => '55bd2063-3842-496b-9cf1-12fc26cd4dea',
        '058CTCM22AM01' => 'af055ef3-0efe-479b-a1d0-eb27b44bb8ff',
        '0581850FTAM01' => '890b45ab-892c-4861-9488-d94d76e36011',
        '058500161AMO0' => '3757a8d2-8fc9-42ed-bbef-02641c4bbb5e',
        '058500151AMO0' => '8dffd040-5d01-4238-aff9-7f47220bb0f8',
        '056601FT0AM01' => 'a0feeaf5-46b7-465c-8a45-204c55689c58',
        '0560512FTAM01' => '4174fce4-3a80-4706-ac7f-1c7dbed8fb74',
        '056500000AM01' => '49947f66-4ace-4119-9462-7229860ca47d',
        '014000000AMO0' => '80a69a3c-972e-4e9d-be98-0850a0819aef',
        '0581626MVAM01' => 'df17d092-8c2f-465a-b23a-c995b2b67411',
        '0560510E0AM01' => '142dae99-679e-4205-9a8c-f705f63a6d60',
        '0570063CBAM01' => '61e5b8c4-f367-41bc-85a8-c923bfba2947',
        '0565100E0AM01' => '8a910fc3-4cb4-4583-97ac-1f3268c97637',
        '056039660AM01' => '12e81449-80c8-44bf-a187-ec13e1aa1f28',
        '056014050AM01' => '8a5609ad-ae33-4c97-9812-bc1fdc86e69f',
        '0560114F0AM01' => 'aa607dc0-d201-49d4-b66b-c409e0be4a17',
        '056014011AM01' => 'b5e8f2b1-421e-4685-9132-8b03677b0b31',
        '05600980EAM01' => '75d690bf-3d52-46f5-94a7-8ead538e63c2',
        '058100750AM01' => '1c6a7c1b-a61c-4738-98ab-f15562fe46ec',
        '0566421D0AM01' => 'aa11fdd8-534d-4742-b32a-1d9d5edfe886',
        '0565011E0AM01' => 'd99ecf7f-3ada-4dd2-8a8d-0a38c7f39416',
        '056057001AM01' => '90682c56-fca6-40c9-8aae-978c1d9927e0',
        '056051197AME1' => '8bce4b06-5f67-4004-9928-550fb11bfa0b',
        '058CTDM22AM01' => 'd4c25480-c8b2-4b32-920b-2686ed2c8043',
        '056612100AM01' => '027a552e-2329-4c2d-8999-da74de61e00f',
        '058CTSS22AM01' => '780c6ecb-3865-4177-95f3-38c63e5745d8',
        '056039600AM01' => '2b77272b-10bc-4003-995d-71f175c0948f',
        '0H1092505AM01' => 'aaddb370-156e-4685-97bb-efcd359f3176',
        '058045100AM01' => 'c99c3fce-635e-4242-b09d-248d9d83933b',
        '056348009AM01' => 'caa1f923-7588-4021-ba60-134488c7d5d9',
        '05631700FTAM01' => 'cdef3e82-9fe4-4f19-aa57-25805d1b9125',
        '0581609MGAM01' => '7bf58090-55df-4d5f-ad41-856bbb889e36',
        '0560510D0AM01' => '21ce0054-c791-4841-bb7b-9d44a18d819a',
        '056008910AM01' => '8b4ae0d3-c655-4162-822f-e8084b24c475',
        '056008800AM01' => 'b8a5c836-eec7-4478-b68d-dd674c058500',
        '056059425AM01' => '3e07137d-be9b-4a61-a502-fce662813a70',
        '0H1001PCE2AM01' => '49ecf4d2-10b6-4dc6-af1d-0010ef63f0af',
        '058CTRV22AM01' => 'a76bb2a0-6706-43cf-8507-df14f0dd1c3b',
        '03256GORG0AM01' => '1def47f1-2a85-4e16-8916-e9f40440e9ea',
        '0576302CBAM01' => '9d8e3167-9f4c-4758-b4ce-514053e5b6be',
        '056039891AM01' => '0d310c21-3f7d-473a-9cdc-6448c3a6c685',
        '042108PQ00AM01' => 'c55deee0-b588-4965-876c-df383ee130e8',
        '056600220AM01' => 'b7b73f95-e5c5-4f94-9c0a-90f21be2ea59',
        '05804260EAM01' => 'd9870da2-53f8-4e5d-b255-dcd1ab9064a6',
        '0568230D0AM01' => '6a17978d-4a6f-4720-91d4-7851402e2622',
        '058220260AM01' => 'c7bd834c-8f38-4fd9-9f7c-ca34c0bd07c1',
        '056600980AM01' => '36ff69b4-ed34-485b-9447-6c5350cf23a1',
        '056001041AM01' => 'a55855c5-ef92-4973-b440-32cb9c6ecd9c',
        '056053270AM01' => '27144eb2-e56b-4529-84ed-0346bd8fc872',
        '0582030DLAM01' => 'e335a3b5-afc5-4508-82cd-2865a359c8d3',
        '001195411AM01' => '577a6260-e0fa-4b60-9f6c-44b79f9a3894',
        '058CTFM22AM01' => '3d79df88-5701-45fe-9dd0-b2fcd8cc4dbd',
        '056009020AM01' => '7cbf426b-e716-445b-a2cc-21cdb9fd515a',
        '0H1001PCLAM01' => 'bb4b8373-9acc-4578-b171-ce7adb0e4750',
        '056014380AM01' => 'c2a2fab6-0b74-4e84-80ca-e35d46ba6524',
        '056099300AM01' => 'd5589148-a09e-43e6-9027-219ff1f1e214',
        '0563690FTAM01' => 'd403b52d-e0e1-4844-9c1f-0b0ecea07cf2',
        '0359110SUPAM01' => 'c3266605-1821-4648-9714-95c359cc9b91',
        '0566581D0AM01' => '845da1ee-56d9-4f13-b441-ea2dd3429c9a',
        '0582505PVAM01' => '606d03e8-5c0d-46f8-9be4-57894484b822',
        '05603995DAM01' => '95ad4a3f-abe3-4a08-974e-24ed1f78f68d',
        '056611300AM01' => '8c0b4e93-064c-4cc0-a6f6-8a243d8ae801',
        '05660529DAM01' => 'a9dcd876-feab-40f7-84cd-756fd85caa49',
        '056316FT0AM01' => 'c5baa7e2-f908-4d32-99bd-7508fb875f0c',
        '058DRP150AM01' => '4f2da9a0-7640-4b96-ae15-68be597de00d',
        '05603714DAM01' => '3ae215b7-38c8-4512-a352-ccae726535c9',
        '056500065AM01' => 'd7d5a336-4ea1-49e2-a29a-c802586c52d3',
        '05880241MAM01' => 'bd377bd4-f1d3-4262-8a1e-b47c87ff24fd',
        '0583452TPAM01' => '4b659bfc-30d8-4012-bc28-799ebcd0a3f5',
        '0563530FTAM01' => '98394a9b-0a79-4522-8101-ef2d84bce9bb',
        '019FRALD0AM01' => '33539726-1f41-4a0c-8aab-6d162fa10b8e',
        '0566002685AM01' => '40f31ee8-4afd-4b34-b440-d5fb7ab3bf54',
        '056039140AM01' => 'b0d18de3-ee92-48bf-9f02-5c761880f05c',
        '056099600AM01' => 'ee4f550f-92e9-4435-aafb-87127a8bb898',
        '05601859DAM01' => '379540f8-5c10-4ccd-86be-8006488b6a3a',
        '056634900AM01' => '753e9183-7e0c-4afd-b224-23699cca90f2',
        '058CTST22AM01' => 'b43d41aa-a028-4564-8acf-91d851544bab',
        '0563170FTAM01' => '028bd8af-c476-4d7c-9e07-94bf89ba9d3e',
        '0563650FTAM01' => '82e0a62a-6f92-4263-afea-0e847ff7105e',
        '0581605MMAM01' => '17074931-df26-470c-8dcc-e533fb7d3fcc',
        '0582504PPAM01' => '3515e1b8-2e02-4eaf-a217-5bd587ce6a60',
        '056999800AM01' => 'e07db732-68aa-466f-8a05-f0126025b272',
        '05642108PAM01' => 'f75389d3-7ba6-4a6c-a67e-08e73ec6cefe',
        '056603100AM01' => '4bc65dce-1286-4a68-972d-ff1c8044cd84',
        '054001080AM01' => '17b609bc-8c97-4e46-9bd5-69b9917664c1',
        '058385G97AM01' => '126dac7d-cf30-487b-9302-a09f664205d6',
        '057006003AM01' => '1d7f73f9-3cde-475b-a719-d1b96cb52695',
        '0588805TVAM01' => '731cc382-a53f-400d-90ab-ab675143a8c8',
        '056001040AM01' => '465571c9-cfb3-4db3-97d0-d252956388d4',
        '0568010D0AM01' => 'e7d7085a-971c-4ecd-9ffa-9364f2142938',
        '056612300AM01' => '5713f132-60dd-4ab8-9c52-6c947ff9318a',
        '055000541AM01' => 'bf1efebc-9998-4853-9c62-e66a3895656d',
        '05806BOBYAM01' => '126b3e26-433b-4ad9-a260-94937c068401',
        '056600600AM01' => '6a6ef6e9-0414-41ec-9a1b-3ed2e4ffca37',
        '058260301AM01' => '50236836-69f4-44f6-907c-d404b21cf573',
        '058162251AM01' => 'f3c70a62-4833-4130-834b-308a28ec859c',
        '05631733EAM01' => 'b5de4dfc-6d19-41be-97d3-9cde35130d7a',
        '056200800AM01' => '6b440fb1-1197-489c-b7eb-92105f9d6bb4',
        '05400MAX0AM01' => 'b45d693a-f79e-40d7-a420-67e7883e30eb',
        '0H1001PC20AM01' => 'c230f6fc-ce8a-4496-87c4-7bbb4558bb8e',
        '054MAXLI0AM02' => '4cb5c070-d29f-4162-95ff-a50131838aa2',
        '058161600AM01' => '8e23857d-3745-4b25-8572-1cf68e17cf4c',
        '0560510CBAM01' => '9d0a1fd5-3972-4799-92cc-0820a929eb26',
        '056389FT0AM01' => '32abc61c-fa91-43c3-81a9-f1876628e96a',
        '056607100AM01' => '56569e7c-7e91-4ba0-afb2-f2e5c7d3bcfd',
        '058010455AM01' => 'fe8261cf-3d84-4bf4-bc24-1e73a92fd988',
        '05806TEXTAM01' => 'cb53a587-0ac5-48b0-a810-2255ccdb2a88',
        '05609895EAM01' => '26269a89-d6bd-4cbc-8bca-3ad9ba9ba568',
        '056201601AM01' => '065f8200-5497-4772-a72d-094c99367f02',
        '056600200AM01' => '6ea64981-32a7-4a2b-8c28-1764b0698ec3',
        '056058800AM01' => '42d8c60c-521e-49ff-9a3c-af0a199c7370',
        '0560202D0AM01' => 'e20f5552-d1f5-4844-84b7-183c1a284a38',
        '056059910AM01' => 'cef5c0e3-1dff-4007-875e-6eb8d1500d9f',
        '056670000AM01' => 'a52dfde2-1d3f-459c-83b9-69c12924ce99',
        '058B15001AM01' => '6a73ee7e-726a-4c73-9e98-b2331289a1ab',
        '056603000AM01' => 'd01b2408-25b8-4bea-b587-8f5846565a0a',
        '05605147FTAM01' => '5f142339-f787-41ca-a49c-42889af26912',
        '057005820AM01' => '69656e8e-6aac-429a-8efc-cc19c2b589c1',
        '0581628MVAM01' => 'bad211ee-1b6a-4fb6-9053-1eb370194955',
        '056500100AM01' => '00880394-aef1-40e7-a726-5049db70311e',
        '0580116FMAM01' => '7d97f798-5730-4e98-ba29-c66d115412c4',
        '0570060CBAM01' => '8ae9c6bf-dcf5-4cde-9059-86006a33c2a7',
        '05601995DAM01' => '351b5dd8-1cff-4319-987f-be2a4e63c45c',
        '058CTPN22AM01' => '8526da2c-b35c-4207-b21e-ef99d73a8c01',
        '0583352MPAM01' => '7a6f8162-56cb-45eb-9480-b7fd559fb2a9',
        '0562005E0AM01' => '82f8c583-2e18-433a-9447-c6fa6fa637fc',
        '02PILOTAGEMCT' => 'f6656626-53e1-4a41-b602-a52beb81ada4',
        '0560318FTAM01' => '4dee47c9-ab21-48db-905d-9a6b2c56b9fa',
        '056390FT0AM01' => '95d60852-870b-40c0-af5c-937f7c4c24cb',
        '058CTCD22AM01' => '1e7aac12-3cb3-44e3-801c-6a06ad524670',
        '056162251AM01' => '98128837-4a14-4c6a-a727-73797a2ba4d1',
        '056650270AM01' => 'dcfca2b5-26f1-4096-942a-5ec6bb604396',
        '056009001AM01' => 'bc474fe2-6d9b-46d2-a948-34c2b168ba64',
        '058161900AM01' => 'e50131c7-535b-4b22-b11a-9f11fa124150',
        '056059540AM01' => 'e60c93af-6374-4d1a-aa7c-fd10050e75f5',
        '0566128000AM01' => '1ad88eab-ac9c-46e4-a908-4d8665c3792c',
        '058161800AM01' => 'bc676f5f-5c37-4e3f-8c6b-e84b3f93f86c',
        '057006102AM01' => '163232fa-672e-432d-b0af-7535f5cac7ae',
        '058721405AM01' => '169acb27-a90e-427d-adcf-b1e812325caa',
        '056660800AM01' => '8f82fd82-7af3-42cb-9931-1264f668407b',
        '058041800AM01' => '7562adf6-c664-4767-abe3-e8850d0782c0',
        'HAMOSTRA2019F' => 'e15be2f9-7b98-4740-9d4f-49b33a9b6f38',
        '0585065INAM01' => '56e70d98-9c0c-4823-8973-67f712626878',
        '056059210AM01' => '706ef2aa-cf39-4299-99c6-293f009b63f1',
        '05819FRALAM01' => 'fd0fbfda-6754-4407-8eba-5dd0fb18b5b1',
        '05600220DAM01' => '8f2db6ca-29cf-4ecb-80ac-ea87ad56bf8e',
        '056612800AM01' => '347d39ba-264b-442d-9e2b-83df9385b69f',
        '0H1001PC2PAM01' => 'e10a03e5-d5d0-49cc-aee5-a05025895d7f',
        '056020000AM01' => 'fa45b240-2524-4d17-9621-05c8882dcef7',
        '0581607MCAM01' => '4a11cacf-3eff-4644-beca-6c14a4430890',
        '056022FT0AM01' => '85ece01c-9ccc-4447-bc0a-40b02c7fa26b',
        '05600190LAM01' => '9f1f3444-e7ae-4f6b-9d38-4af45f6af018',
        '058CTSA22AM01' => '78df3d26-1fe3-47ff-885d-c1f14c5c391a',
        '056099400AM01' => '2ae2f7bf-265e-49f5-b7c1-cb87ab464fb7',
        '058162700AM01' => 'c7f6f982-8eb5-4d99-b83d-115b33879fab',
        '058063100AM01' => 'ec9ebe4b-efc6-453c-b7cf-8bd91cd81eb1',
        '056610558AM01' => '7850e591-91f8-4927-a645-92992630e88b',
        '0566002583AM01' => 'ce116038-49e7-4c69-a1bb-8486e84a85c3',
        '0566410D0AM01' => '5a4550b0-aad5-47a6-8a29-4089b1353622',
        '056607585AM01' => '4a344be9-5ee9-4cde-9e09-fc3b0dd11006',
        '056600111AM01' => 'e82e2b7a-83f2-4406-a566-73d8697b7b2c',
        '056059520AM01' => '02a4334a-cf45-4cfc-b374-e8569e3f73a2',
        '058101990AM01' => 'e41e6154-a76e-47f5-8b23-1f7054f75699',
        '0560677E0AM01' => '5c7f83d2-5144-4dff-a30b-3db670f48931',
        '056039100AM01' => '0850cdc5-ec52-47d0-ab0d-c1c21621ae5d',
        '015101011AM01' => 'e414115b-fcd1-456d-9023-a611fe3e5f4a',
        '058421070AM01' => '73818982-971a-4191-8f0e-7f2523e8ece1',
        '001195400AM01' => '6381e930-1a9b-4218-9ed4-48b94afcbf24',
        '056800900AM01' => 'cbf00c60-cf0b-433d-9a6e-c4e28f70c8a5',
        '056600360AM01' => '40888f7a-f5ef-4d1b-b04b-485f3264432c',
        '05600911DAM01' => '533de935-ebe3-4817-b0ee-7ed43f1c6bec',
        '056051350AM01' => '1366829f-93f2-426d-95be-d89be4b3b73e',
        '0560956D0AM01' => '8d57a8fc-09f7-4a63-a191-41eeb404a5b1',
        '0581633MMAM01' => '88a3e0b4-a254-43c7-99a4-bf7123974b0e',
        '058CTCT22AM01' => '2b42c870-1c99-4639-af51-ca5c144c01a5',
        '056015120AM01' => '018e02b3-611f-4b7f-8780-f149fc149ea5',
        '01ANCARTW' => '7214ebf5-7962-4179-aace-faa54f831049',
        '581600AMOSTRA' => 'e8de4623-1e7a-4c72-8167-fa524199f6d4',
        '0581291CSAM01' => 'c60b73be-9cf3-4163-9782-e295691c5ffc',
        '056200199AM01' => 'ddeb431e-6566-4d04-8d8a-bc203915b663',
        '0560530E0AM01' => 'bff38ab8-5009-40a8-9389-3020aec98098',
        '0560515FTAM01' => '328ef881-818d-4e09-8a0b-c911a4b1cc19',
        '025PERC0E0AM01' => 'c5692fc3-b4bf-43d9-98e3-c84127ae7aa2',
        '056053260AM01' => '0addf4f6-4b85-4c6a-bb34-e7b3150cf6cc',
        '056014383AM01' => '19e62201-4be6-4dd1-8b2b-63fae48ff225',
        '05605105FAM01' => '3c5b421a-1a2d-48e7-a1fe-d0eb38bf65c6',
        '056039990AM01' => 'dd627970-c904-44df-9330-640293947033',
        '056610129AM01' => 'eacc4a66-a9d8-4251-b8e7-c5738b8cfa94',
        '056059780AM01' => '278f7ae1-a299-450c-a1a3-996f75a45239',
        '01LIGHTBLUE01' => '328101fd-3188-49e1-b8c5-e98f48d40c31',
        '057006200AM01' => 'aab906de-bce3-4933-9700-9f42053799c3',
        '056008515AM01' => 'e127c6e1-a31a-4787-8739-86b4761c44cd',
        '05666779DAM01' => 'e1c19353-6715-4781-8888-ed6a2acf53ad',
        '027110000AM01' => '53e9920c-e0c0-4ce5-90cc-1e4b7db7722d',
        '0580477SMAM01' => '0fd77cfb-4496-4904-8a74-b35ad641b998',
        '056612821AM01' => '810f16d9-6da7-4794-9cac-2519716c092e',
        '056200900AM01' => '46e2e7f1-b792-4579-8e99-05016f04d647',
        '05605280DAM01' => 'a8f82f36-9bf8-4151-a11c-91a3ff464d26',
        '05600121DAM01' => 'f9910b60-b044-402f-9953-f3d87f2e0b18',
        '056039600AM02' => '2e05abbf-06dd-40f2-9baf-910194be6910',
        '0581601MAAM01' => 'ad5f5032-df36-47e4-b8ec-51992e525794',
        '058470341AM01' => '0ad98c08-c8de-4c34-a717-9dcaea4b8872',
        '056099200AM01' => 'ed69b045-dd5d-4cb7-a4e3-5d4c00eb98b1',
        '0560569D0AM01' => 'db8d73a9-e531-4b67-b4b8-fdb7f8035173',
        '05821STUDAM01' => '5fa996e6-d1bb-4c82-9706-a2c6eabbbea6',
        '058CTTY22AM01' => 'faf69336-8eab-414a-a098-ddfa429f8574',
        'AMOSTRA' => 'b1d149b2-3e50-4aa4-ad4a-6851a8bd15a1',
        '056652400AM01' => '62d91fae-2beb-42cc-8b2e-8e6add14af3d',
        '0581612MPAM01' => 'e426730a-7125-4e35-8263-40da178c5aa8',
        '056059530AM01' => '6f2e76b7-7bfd-4494-8f0f-13660798db68',
        '058CTAB22AM01' => '3c64ece8-5932-4a5b-a466-3c448cedf4a1',
        '0566602E0AM01' => '0af840be-5633-475e-ae26-c06e2cfffc83',
        '057928SUPAM01' => '9b55b1f5-28f4-4263-857e-4f4e71e56c4b',
        '0560113F0AM01' => 'd6167cb8-9402-4030-96a3-fb4410c371d2',
        '0580670E0AM01' => 'd938fefa-aa16-4508-afa2-fbcfe61c460e',
        '056059030AM01' => '9eb3ddcf-e15f-4c77-b887-980f55f357e8',
        '0560064FTAM01' => 'b2230f61-be45-498b-ba9a-23b4c03fbc00',
        '056600180AM01' => '19e2dfc7-097e-453f-86c3-ba4344a4383c',
        '056660500AM01' => '04d172e4-3a37-41c5-a4aa-53536ab7f2c0',
        '0580462SLAM01' => 'a6d8744c-0c17-4d17-b832-a0ae4efbc2e4',
        '056059920AM01' => 'a48793ef-0016-4f3f-a00e-57106525abb1',
        '0560517E0AM01' => '7599e029-c87d-4508-8c8e-822c0fc9cdf8',
        '0570058CBAM01' => '58e4e56b-b664-47fb-8570-ff837f30e653',
        '058100730AM01' => 'bc3f0aa9-7ee6-44a4-82c9-f7663b4846cd',
        '058101471AM01' => '9485faa7-de94-4305-ab3d-2433b3192fec',
        '058022026AM01' => 'bc91a75c-66df-4a1a-a894-55b811a5117b',
        '0566377D0AM01' => '5fa3bc83-9caf-4ea1-bb5a-b798747c9fd7',
        '058160600AM01' => '83a7d3b7-47a9-4c74-b065-b0e672dd6629',
        '058CTAW22AM01' => '3991d8c3-a647-4269-ac42-138be6a46151',
        '058101330AM01' => 'f2f97bcd-9f9f-4897-81af-76c260fb8253',
        '05601960DAM01' => '1763d4ef-fba0-4f30-8d6d-0e7c2f6fc7a9',
        '056200200AM01' => '9710c722-e266-493b-921f-f1424593c33d',
        '058018000AM01' => '01079e73-db48-40c1-8fe5-c240eea239b8',
        '056060FT0AM01' => 'e5f874be-ae1b-41cd-8edf-9d79fdf1796c',
        '05665223DAM01' => 'e1ccc3e1-95e5-4e40-b46d-63fb46bfef2d',
        '057006205AM01' => '51925bac-b853-4104-a91f-6c56a9ade9f7',
        '056054512AM01' => 'c7433b0a-7874-4368-a351-7ef9f1f8e0ed',
        '0581635MDAM01' => '45b6b1f0-87ad-4fd5-9596-fb178a40bb08',
        '056059912AM01' => 'e49d298c-1258-41c8-8027-40a028d6172d',
        '058580301AM01' => '2d7643d1-75a5-4cb9-be28-31aefbee27da',
        '056222001AM01' => 'e2a7db90-4392-4c14-929a-228638f5fbb4',
        '058161400AM01' => 'b2fe6b44-82cf-46e3-9401-afe29d570a88',
        '0566580D0AM01' => '8617c9f2-6854-484d-a4b7-bf29f7111031',
        '056800290AM01' => 'b4e547dc-9b6e-4b2d-aeef-da0ec2425012',
        '056600190AM01' => '9b9f0ab8-76da-4dd2-a2dc-81756962a315',
        '056009991AM01' => '73dfc189-4c3a-4bfc-b92b-4666fd141a42',
        '0359110PL0AM01' => '1960e684-aeba-4974-b794-3344a3a821d9',
        '058CTCV22AM01' => '509f5ed0-0ae8-4db9-b3e3-f76b644e4d32',
        '058160800AM01' => '635c9ee2-9aa8-4cf2-a24f-411a1cf20c05',
        '056052100AM01' => 'b1470d76-d84f-4432-ac8a-8be32eacdea0',
        '056654010AM01' => '8baa0b40-abd6-42a8-9c14-fe70af1902b8',
        '05662126DAM01' => '8416d965-bf98-4f67-b775-e36b4b2f7b46',
        '05665223DAM02' => '48c4683a-cff5-47e2-878a-2e867468889f',
        '056624970AM01' => '953e44a5-178f-46e1-b590-7e95026409dd',
        '058104556AM01' => '906480d9-2c8e-4a24-8f34-ccbd5422ad5c',
        '056600100AM01' => '23708e59-f79d-48ae-a9e7-3605170e2158',
        '001194422AM01' => '3cedfb34-0426-409b-97d2-b6fe89368929',
        '056699200AM01' => 'fb14636d-cf47-4605-b08f-8e50e81d4b06',
        '056099100AM01' => '6c867945-5a11-4543-bcea-2f324befa558',
        '058710000AM01' => 'c05c9aec-c325-422e-9aea-9a75ec1ea5a8',
        '05665091DAM01' => 'a0442a53-b4fb-485c-866e-7d8482d32180',
        '014000001 ' => 'd9eb3074-1eb2-4c84-8b2e-f8c321db78dd',
        '056608200AM01' => 'ffcb3644-ef3e-4ed6-810a-3bdef6d826ed',
        '0566002785AM01' => '189a1af4-0d3b-4f8d-9927-eef9c8c3aff9',
        '057005900AM01' => 'fd53fdc0-df92-42d0-b3b7-5e6a2f665cd6',
        '060200300AM01' => '27222049-740f-487c-8d15-426fe78f8eea',
        '056039670AM01' => '111ecc95-dfa1-41e2-bf46-1c5d7ac20a5e',
        '056023FT0AM01' => '08106c08-ec4f-406e-8faf-dd85ded41809',
        '058500191AMO0' => '6f805044-c269-487e-bd78-7695875c89e4',
        '0560562D0AM01' => '245c1694-3231-48a4-b04f-9274ce059f3a',
        '0H1816005AM01' => 'b3dc2da2-a4bc-42d6-8613-e9412c51cedb',
        '054MAXHI0AM01' => 'c32efa2e-a7f1-4015-bb28-b3a41ef748a9',
        '056201632AM01' => '8dc828b0-a873-497c-9f2a-43c11060b537',
        '0586013PPAM01' => 'fdbf4665-abf3-4521-bc9d-ea2bb5e68640',
        '0H1001PCAAM01' => '9d59b6e2-a496-4df0-bbca-d6f3083d911a',
        '056622500AM01' => 'd273ed26-9cf1-48ee-82b8-91c6ada42a9e',
        '058CTCR22AM01' => '064441f9-d341-4984-8580-5b0f3b335ae0',
        '056201700AM01' => '9baa60e9-3b4b-4f01-9098-676d93a226a4',
        '056645100AM01' => '0862f076-7c57-4ac5-b7d8-32338e94141c',
        '05601860DAM01' => 'a7cd0aaa-47c9-4bcd-a3e9-5e7d9be512e1',
        '1208000013' => 'c241d35c-db43-4ee1-b748-5d9d8c8533b1',
        '015101000AM01' => 'bd2ab3bc-1148-4ba9-96cd-39c9d29c460e',
        '056009998AM01' => 'f9856efe-4339-42e3-a24f-2cef4d077684',
        '058044100AM01' => '5687b801-e822-496c-a183-5c310cb95d99',
        '0H1816116AM01' => '7f645b40-ebd1-45ac-bed9-f616f9315d17',
        '05600154DAM01' => 'aed33c91-633b-4321-9fa6-a35f6bcc7f06',
        '056099500AM01' => 'a0e4a3a6-ef1e-47e9-add6-49b215236a26',
        '056600310AM01' => 'e77230aa-9447-429e-9d27-7b33f3701c97',
        '054MAXPLUSAM01' => '1776337f-950b-42be-be5d-6852085d2ba7',
        '0H1001PC2PAC01' => '03758d8d-3829-4dfe-b93c-ddc474041f29',
        '058225V00AM01' => '41094043-3012-43d1-b8fa-06de1cb83f1e',
        '0567622D0AM01' => '75f8cd1a-902c-4c25-9dd2-1158c09d1eb0',
        '056009900AM01' => '0b05e0d5-788a-4e89-a9b2-0104f980d0e4',
        '03256C0TL5050' => '9de2bfc4-19e3-490d-9ac0-76c02a2645bf',
        '0580056SGAM01' => '76b6c469-147f-4afb-ba87-a5c3e0fd40e6',
        '0580476SMAM01' => 'f8a65385-3750-4191-bd31-ff1a0a3e35bd',
        '058225V86AM01' => 'f0400bc5-8b48-41d5-829f-708e1d700b46',
        '0565010E0AM01' => 'efe62a4a-d2e4-489e-acff-3e317792861e',
        '056000000AM01' => '4218a77d-f4b0-49e8-9aea-f5489d4a63f4',
        '058015170AM01' => 'f84ee710-6543-4dd8-8c15-13e0f6b03b3a',
        '05600135DAM01' => '932ca8a4-fcf4-4b2a-bc07-0f7a7fee7c8e',
        '05806XADPAM01' => '83557c92-5715-48cc-8dff-bb8c9e17622e',
        '056335100AM01' => '7e90340e-43b4-465b-99a3-e5f86015497a',
        '056051016AM01' => 'eae00ffb-5cc1-4c4a-a802-2e07221fd36e',
        '01FELPA15AM01' => 'd26401c8-ae44-41b2-b63e-40ba95fe4b74',
        '056051011AM01' => '3c8d65a2-53e9-44f1-b84b-2bf2380b22f0',
        '056382000AM01' => 'ef669c85-4068-4b5f-90e5-2c40d2a48369',
        '0581638MDAM01' => '16d83d48-f467-496e-a8a8-d8fb8f115e3e',
        '0566443D0AM01' => '4bf26d12-f035-40f2-a811-edcb95f50ce7',
        '056670098AM01' => '4f6ca118-22b0-4cc5-ad56-7cf39f22e361',
        '001194400AM01' => '5c6fb633-d56f-4f32-ad45-57f38b9d0bc5',
        '056672400AM01' => '9ee8c5d0-4c42-4d37-95d2-6ba92320a46a',
        '056603111AM01' => 'cf1ac9f9-0519-4e49-8920-f633b4ea441c',
        '056051010AM01' => '8c3d11e1-cb9b-418d-8ca1-185ff86da30b',
        '056609429AM01' => '3709ea4a-0a50-4e36-9488-1d81a6ccbbc5',
        '056312310AM01' => '2ba3eb19-f8d6-4956-b3e6-21af838d62b1',
        '05600622DAM01' => 'b306cdd5-414d-46f2-9881-81a0ab7dcde2',
        'IT8010100225 ' => '4db65236-9bf8-4609-8e18-024aecef9b7a',
        '0580398000AM0' => 'b34a5a9b-bbec-4c5a-8b56-92d6c0a90e44',
        '056039650AM01' => 'b7bc52fd-4e8c-4e07-a636-5248c8357e03',
        '057006302AM01' => '0cd46b6c-2352-4276-bfa8-151e201c1995',
        '056085150AM01' => '4bb24a59-8fdd-456b-84aa-9961605da5c0',
        '057006GOLAM01' => 'b8b98e63-9147-4f09-be2b-bd43652a6444',
        '056010000AM01' => '088199c5-762f-4d26-aded-5e0414f4b308',
        '056602100AM01' => 'cb209bf3-279f-4932-ae97-2fc8365c3884',
        '056014690AM01' => '00fc9339-402d-4d41-ba36-c4e52dc97db2',
        '056312309AM01' => 'ff3e859c-3d06-4b42-9092-9f1187413f0f',
        '003537003 ' => 'cb379109-df85-49b3-8ff8-9e4c41815f3b',
        '056039920AM01' => 'af630f24-1d4a-4341-9097-a30e9b8ad6bb',
        '0567733D0AM01' => '0d682161-a74f-4ad5-ab70-c00928a8b11b',
        '056658200AM01' => 'b609cc9c-55f2-4626-ac02-8c54717f6a82',
        '0560988D0AM01' => 'e778569f-b86a-446c-ac81-b924952aa800',
        '006476' => '4a641524-9ed2-4ee2-a1a5-3f06604227fc',
        '0568120D0AM01' => '721abdd1-d9d5-47ce-bdb6-f877bc5f1bfe',
        '056604FT0AM01' => '714ef8a7-ff8b-4df5-a743-09e068ca28b6',
        '056560000AM01' => '1eb13fed-4043-4874-995d-f434d0ccfc59',
        '0583701FDAM01' => '2be8a60d-a6ca-41c2-9fa4-a42024d1f030',
        '057005910AM01' => '80ce6f8e-bb2a-4ec5-b71c-4c3e20b75ffc',
        '0359110H00AM01' => '465d7f15-6b88-46bb-b09e-fef73aaf1dfe',
        '0560526FTAM01' => 'bb9641eb-957d-486c-ae8f-b1206f0abe47',
        '056051197AM01' => '2ac803d4-536c-409b-adb4-c24403d4e29f',
        '058100830AM01' => 'c6563923-f51e-4438-9a39-c6bc618089de',
        '056056310AM01' => '459287d6-f835-41e7-ac1a-e2d49ea925ae',
        '0560457F0AM01' => '1c0cdb4c-33b4-4432-8585-36f43fe81e48',
        '056658300AM01' => '20486150-4625-467e-ac31-97b061fb3acf',
        '0563390100AM01' => '17c4524d-6673-45f4-ae39-18cab623d01c',
        '056035650AM01' => 'e7de18f0-9d47-47d6-b01d-124702b2a2e6',
        '3256AMOSTRA01' => '8654e6b5-24fe-422f-b1e9-c3c8e4da43ef',
        '056331FT0AM01' => 'abdfa1f6-3f77-499c-9ba6-48f386db63be',
        '056006313AM01' => 'fa861ca1-d0c6-4b48-a346-8f7e8eca8c4c',
        '056634860AM01' => '58f8b573-adec-4fa6-ad52-b517771cc8fc',
        '056009700AM01' => 'f4276223-d6dc-49c8-a812-a50bd9afdf57',
        '056059220AM01' => 'b8528271-cf71-4c49-93fc-56902d40ba9c',
        '056090828AM01' => '3d148c67-37a9-4ebb-873a-deb255a2351a',
        '0581630MVAM01' => 'd6be0826-c38f-4872-bea1-55de3ef797ba',
        '058251049AM01' => '4d93ac02-6d12-4323-a03f-50776428226c',
        '0581634MTAM01' => '8bf8f50d-b8e8-43c7-9e8e-425939230237',
        '056200400AM01' => '10441d8c-f444-4954-bbe9-4539b3fa5663',
        '05669101DAM01' => 'ec5f6c48-3c96-400b-ba4f-d1262ab3885a',
        '056317500AM01' => '4fd5d2fd-689f-427d-819f-a60049d725ff',
        '001195500AM01' => '39df38e0-716a-40cd-8a8b-7baae2532196',
        '056600987AM01' => '46756478-84bd-4170-ae94-3f64f8c3a7ef',
        '056099000AM01' => '987c65cc-ea48-4a09-a75c-34fd0ecb9c6f',
        '0225Z640AM001' => 'f4b2ca5d-c979-4675-9f2c-94ecb2f432dd',
        '058104552AM01' => '9475e78b-56f9-4577-ae5f-9e6f27aa960e',
        '010002860AM01' => 'c6c7844b-9441-404d-a423-94e5dcfde0e5',
        '058500181AMO0' => '10d4ab41-a080-48f6-9c77-a9632ed874b4',
        '056650300AM01' => '43026f9a-15da-4623-b5fd-b0af77e95df8',
        '056059926AM01' => 'df07987b-f839-4a43-8cfd-99f8ad5559d0',
        '058250400AM01' => 'e7eab1f3-9354-4e8d-8538-0429ee49333d',
        '05665310DAM01' => '3b837ab1-b456-485e-9373-244c76ee82e1',
        '056604889AM01' => 'a25670f1-4fc2-493e-94f8-89a77bd7cc2d',
        '060200200AM01' => 'c3bdba70-dfbf-4bb7-8229-ac6aff72ad26',
        '056371000AM01' => '25e0b75c-0027-46ed-9f15-aefc2a039ccc',
        '058108590AM01' => '7e77588d-7772-4a24-9845-a3a0f2aeabea',
        '0550005CBAM01' => '2ef9ea9d-60ce-4ee3-9c93-8d98d5af885e',
        '05605524DAM01' => '3c070194-0065-4ab8-877b-20fd29805390',
        '0580670000AM0' => '773d272a-9cb3-4cd3-bac2-611af9f901bc',
        '0580634000AM0' => '6b84e304-0b78-4b3f-aa0a-d206b53be1ad',
        '0563510FTAM01' => '23b3ef4a-1e28-433b-8d10-965616a3f6e1',
        '058250300AM01' => '844b59fd-b569-4bf6-92c1-3d77add92e3a',
        '056605FT0AM01' => 'f48b9c39-b53a-4af5-8b70-9d647043f67c',
        '058100820AM01' => '7839d7fa-49a3-4145-a3f0-c53d36b0ecd4',
        '056600280AM01' => 'b496abab-26d9-4099-912c-3cd9aa23997f',
        '056598000AM01' => 'e3018e59-c217-47d0-bd74-a5861b7dac79',
        '056301800AM01' => 'a3a449ea-f13e-4887-9bb9-ccddf1a0a189',
        '0560010000AM01' => '05ac6115-bfda-4d74-b2fa-5c50fa0f05eb',
        '0560017L0AM01' => 'df3834d4-5950-4a0f-bdb3-19542592a4a2',
        '0225Y790AM001' => 'f91fedce-8a40-414e-959f-0a35a11d50ed',
        '0570059CBAM01' => 'cb2ac116-540b-44dc-a9d0-08d30b5a57bd',
        '058049580AM01' => 'a796041f-8542-4e13-816f-44344730ee8d',
        '056601400AM01' => 'f758297c-cf54-42e2-8ac4-c183b7c3660c',
        '001194411AM01' => '91018cc1-9ed8-4ad2-9354-1ce86d053cbd',
        '058470301AM01' => 'c274f2d7-465a-44f1-858f-d69faef9571c',
        '0560396CBAM01' => '7954734b-ba09-4e0d-aef4-a6a393e14df1',
        '056600172AM01' => 'c978edf8-3756-41b0-bfd5-e518aca51aeb',
        '0550054CBAM01' => '844721ec-09aa-402e-855b-a8fe27b6ba0a',
        '056612400AM01' => '4af88e2d-b064-4fb6-9f1a-5ac7ffeaf6f7',
        '05631525EAM01' => '26c5f6bb-e19b-4c4b-87c9-9b86a16b8e06',
        '0580461SLAM01' => '2e6e2a3f-9730-4480-b7ab-012ae8c47710',
        '0560500D0AM01' => '3b98614e-4b99-4c6b-aa56-2e24f571bf58',
        '0H3008114AM01' => '12a9b98d-1779-4270-b174-065c16ae4af7',
        '058CTSJ22AM01' => '64b0a727-978b-4009-a1bc-a05cb8a4d112',
        '058CTSF22AM01' => 'cec12aff-fd2e-4dee-9fd5-394582259766',
        '056610345AM01' => 'f2ef1a2a-53c7-48e0-a740-84eb9ba33dc6',
        '014000000AMO01' => '301f302d-2308-452a-9127-e503b10f98a6',
        '056020100AM01' => '0e4cae5c-8f77-41a3-b6e1-fdfc139ace5e',
        '056623010AM01' => '62ae06e3-b4ed-4f8e-a45b-7e08d02d159a',
        '05655100DAM01' => '5cfb8b25-ec72-44e8-94da-11a9ef81572b',
        '560479TTAM001' => '04a3d964-469a-44b7-8398-96147b943aa8',
        '054001090AM01' => '82b44138-040e-4fd4-8d2c-a1e7ecfaf106',
        '05603999AM01 ' => '96622bb9-d84c-4663-801e-7c1581fb1b3e',
        '056651300AM01' => 'e155e814-e3ff-4a9d-95a8-b688ef1ff608',
        '025PERC00AM01' => '04ba1bdb-bb99-4207-ace5-a5d71d204137',
        '05605210CRTLA' => '44b6a5b2-3f77-45b7-ad2b-2ecb4f6cfdd5',
        '056199600AM01' => '04625acb-fcb1-4ccc-9a66-76e0f4fc90ed',
        '056500522AM01' => '6191ff92-6f0b-4c37-8bae-188ede2fd6db',
        '058500141AMO0' => '8f50d47e-407b-4e4f-89ee-fb7e9ec62390',
        '05660125DAM01' => '99518bb3-8379-49ed-ab95-46ac601adf92',
        '0566010D0AM01' => '2dbfea31-8022-498c-902a-c1579698df82',
        '058160400AM01' => '2b2de93a-c853-4a1b-9c85-b999538d64ab',
        '0563171100AM01' => 'ca382f14-95c7-454c-bc83-bbcfb8d2710c',
        '03256PCC0AM01' => '92082ea4-09d0-40f8-81d8-9ef68f1c0d72',
        '05605600DAM01' => 'c639352b-e183-4a8c-b607-a5fb7c3d898d',
        '056612500AM01' => '786f4248-48e4-41ea-9d51-f3ad890d735e',
        '056800291AM01' => 'c66719fc-499d-4cf6-915e-50dcdc032af1',
        '056069652AM01' => '0c893d65-f5fe-4556-8011-4a2a9f8264f4',
        '056987D00AM01' => '4b254832-7ac6-4807-af60-5a0186de0358',
        '056051150AM01' => 'c724ecc5-0efe-4f72-9a97-9e1cd6784000',
        '035911M00AM01' => '591b1973-11cd-47c9-bedd-a9e14fa82766',
        '056039940AM01' => '68aba0c0-f8d1-4362-986c-463f697d7a9c',
        '0565122D0AM01' => '0d56005d-e489-476f-9d92-92f37f060a28',
        '056988FT0AM01' => '95f571dc-3eb3-4310-9e60-91a0b28011e5',
        '056600151AM01' => '71b4b502-3fc7-4437-85c6-5778a252b13f',
        '0582501PBAM01' => '0bb5b46d-321a-42f3-9985-e855e14628cd',
        '1208000003' => '00279d41-924c-4ec7-9885-179afb007d8a',
        '0563710FTAM01' => '8257c992-2a48-4f91-82c5-6653c17eae37',
        '0560020LXAM01' => 'ba5aaa19-07f1-428f-be89-683abac845e7',
        '0566075D0AM01' => 'd4474b06-47f0-429c-b9e2-085afb299b08',
        '0563171D0AM01' => 'b2e3756f-2142-4e22-a8aa-d0e0403bc3f6',
        '058051000AM01' => '9ac2a1c3-1b6f-4d46-9801-d17cd2e32371',
        '0560203D0AM01' => '1da36dee-c344-48f1-999e-8fe787bdf73b',
        '056600112AM01' => '0f1b610e-afb5-445c-bbe5-3ceda9e61644',
        '058275082AM01' => '1b6c2799-af5f-4c35-af6e-6ca22547396e',
        '056670099AM01' => '40c682f6-40ae-4a12-a1a6-17673e41b357',
        '001193400AM01' => 'f6ea683c-6e46-4a0e-b4cc-eda70eb8b26d',
        '058CTAP22AM01' => 'f7e0678b-9eeb-4347-a8b4-f31f1b21d10b',
        '056025FT0AM01' => '38ce9e5d-da3a-497c-8125-e39560529832',
        '056051110AM01' => 'd817c326-9aef-4db9-9846-75fe35ddd9c6',
        '035911010AM01' => '7aee6720-d62c-41bd-9197-c99518be8a5d',
        '056200700AM01' => 'a26e7eb1-f837-4a90-ab7f-68e78fcee53a',
        '010002850AM01' => 'cde24552-efa8-4b16-b00d-52591a8cde85',
        '03256B146AM01' => '670a6f50-7f9d-4255-bf2b-5d660558a591',
        '056019920AM01' => '941496bc-6686-402f-8127-18c3156c6570',
        '001193422AM01' => '8d8e3ba3-94ea-4070-a303-d5f55e1ff126',
        '056201682AM01' => '614c5434-232d-4a6b-93c1-71034ad61dc8',
        '056670200AM01' => 'bf476f05-bc4f-4c1e-83b1-06eba129128e',
        '0582505CSAM01' => '023d37e2-db79-438d-b748-5f59ecc69856',
        '0560315D0AM01' => 'f69ec73c-40e3-4e14-9e75-8f7f6e9d047b',
        '0580485CBAM01' => '676a7b4b-bd88-4659-abc3-822bf71fee2f',
        '1208000002' => '00b10b96-557f-4757-aeec-865674efe5be',
        '01193500AM01 ' => '3405c208-370f-4afa-973a-ce646181d8d7',
        '14000000AMO01' => 'f1a2741d-9dcb-4017-9a79-bca057ad2588',
        '05600200DAM01' => 'cb5d6757-265c-4d1c-9a68-8738828c21a5',
        '0582502BRAM01' => 'c9199a16-140b-462d-9ba3-0c9ae91de144',
        '0580513TBAM01' => '66ed4532-95e3-4fb8-b825-1a7756c8f466',
        '05600999LAM01' => '25b16309-7433-4dc0-a4a2-e0c15238e4c6',
        '05605120DAM01' => '1b93d363-e60e-480c-8377-dd80bd3f5f05',
        '0560399E0AM01' => '825f36f1-7509-4a3a-9995-db758f9646b6',
        '058500171AMO0' => 'db28d154-529d-4053-a64f-7062a4a0edb6',
        '057006301AM01' => 'c7d807b2-96e6-49ff-8650-a01a0d357685',
        '01PIQUET00AM01' => '1d5f0860-5d60-4ecb-9ad1-d319144e5252',
        '057006000AM01' => 'c25893e6-eed4-408c-b969-f72734abbd7e',
        '0565980D0AM01' => '798e5497-dd2c-43d0-bc3e-1b3132c6e81d',
        '056009990AM01' => 'bc366215-9804-4fc2-8168-b2f468a240c5',
        '056097710AM01' => '34532a23-e79a-48ab-bcbc-0293cb93dcd9',
        '056108600AM01' => 'b8f0a6ca-fc0b-44a8-90d1-7a8b0fbb2c3a',
        '0560988000AM01' => '9047e16b-4b9f-4893-a8d2-6e4e3c6b894d',
        '058CTAN22AM01' => 'd744b487-044b-4949-8ae5-5241870f6a82',
        '0580180UMAM01' => '16d4ecf2-d4b0-4fb4-b75a-b9b63befac4f',
        '0563194E0AM01' => 'abf2769c-7cea-447f-943e-69fc0dae92d7',
        '056002200AM01' => '89e56a8c-e9dd-40de-b0a2-dbf66e019fd3',
        '0560988CBAM01' => '9c4a7092-714d-485a-9ac3-879915c257e3',
        '0563160E0AM01' => '05c856bf-45b3-40af-9263-50eb3d3e9450',
        '0581603MNAM01' => '6eb297bf-761a-4f0a-9568-c815d0b1a424',
        '056009650AM01' => '5258ac1a-1f56-4964-9334-a7540c3ed56e',
        '056019500AM01' => '9a88e89d-05bd-4b00-b0a2-ffe9bef05beb',
        '001194500AM01' => '24193ea6-137a-4312-969a-84b43d2903b9',
        '058CTSG22AM01' => '9748132b-913a-4fc3-abf0-833992320cae',
        '056201448AM01' => '30d061fb-696a-4b9a-90cb-fdc7f37a83b5',
        '056600109AM01' => '01b1d4d7-99c4-4d74-8486-933de0d802f8',
        '0583844DNAM01' => '013318aa-dc4c-4c0d-973b-ff30037c6c1d',
        '058101530AM01' => '0e6e34f0-882e-4d91-8bdf-79674d19629b',
        '056026FT0AM01' => '9acb496d-624d-473e-b9de-4634a1a61daf',
        '05665201DAM01' => '3722d9c4-353b-4d21-8649-39ed446b6a87',
        '058CTDA22AM01' => 'd5cd508c-dded-4c14-8684-e1e8e5d8d6e1',
        '0560514D0AM01' => '3eb86848-ca99-4776-8d17-010b607d6f83',
        '581631MMPAM01' => '6451a3bf-cfe3-4fb5-a421-3b8150fc8a95',
        '058CTVC22AM01' => '2f626981-676b-403e-85d8-51d1ef252f83',
        '058010630AM01' => 'bf095ee9-a0fd-4666-9f1a-df77eba583cc',
        '058500046AMO0' => '29dac20e-00d4-4ef7-a554-6e02ae827de5',
        '0581635MCAM01' => '6bcfc3ef-ae2e-4d5c-9099-7936139b9b98',
        '0560391CBAM01' => '51a9f10d-43e7-4ad7-950c-f523cd3bd3c6',
        '060PERK00AM01' => 'e72577af-8c96-42e9-9b06-28594b029fa6',
        '058047530AM01' => 'bdad451b-46ba-4447-8ba1-ad6d970ddf33',
        '057612000AM01' => '50224f7e-fd93-4020-9c3a-179756c83c39',
        '0566520FTAM01' => 'f4bf41d5-12d5-4223-b89c-12f5eff556ea',
        '0560021000AM01' => 'cff1d252-9793-4c4c-99fb-0283456ec689',
        '56396AT001000' => '92c50ef2-2823-4f2e-bb9f-addc55af7f7e',
        '056014160AM01' => 'bb5943c2-5e02-45d0-8a49-d6b6e3479f96',
        '0586123000AM0' => 'befbc3a0-df25-4d9e-86f1-c69c1647e56f',
        '056600125AM01' => 'ff6ad6d5-d0f4-45c5-aa01-e46231541834',
        '056050165AM01' => 'e2ac7f9a-2b0f-438d-b7b8-619958490c54',
        '058H10955AM01' => '622e072a-58a5-48fc-951a-bd6fd2322da2',
        '056600490AM01' => '01f3b85c-b84c-4e26-ae25-b4d34a58836f',
        '058CTMNP1AM01' => '2d80d359-9520-4112-8397-550ca37e6da9',
        '0566089E0AM01' => '97b66e64-dfc9-4172-8472-9c0b635ed9ae',
        '01PIQUETSAM01' => '55e0b2f5-3a86-446e-b7be-a2ede09a7c64',
        '05603401EAM01' => 'dfb5369e-5170-4a0e-8ce0-1853dd98a83e',
        '056300000AM01' => '152272a5-bd90-41f8-a373-e86e3cc7899c',
        '056051000AM01' => '610af5a5-17cf-48b0-8bc6-c133bfdd5f71',
        '056069990AM01' => '28a2faf0-ce89-4548-902e-6f9e45e47215',
        '0582030DBAM01' => '3bcd8abf-6a56-44d8-915e-43245beac663',
        '058CTVF22AM01' => '3e8a12c2-0945-4ee8-8afa-0d10d35d5dbd',
        '058CTCG22AM01' => '558b8a7f-67b4-4373-9160-a4999d39ee6a',
        '056059650AM01' => '6f6474e1-0f1a-4afa-949e-f8d63e885347',
        '0580514TFAM01' => 'ca944ce3-e972-461d-9d28-2abad96530f0',
        '05601970DAM01' => '0cf37ee8-205c-44a2-8a0a-0ed685d8d0d8',
        '0562007E0AM01' => 'd16b99f1-5bb7-415f-bd2d-c6077e91c01a',
        '056053000AM01' => 'b8de9414-11e2-421a-bc2f-c060b8371046',
        '001193411AM01' => 'bfbb8dfe-29b7-43cb-9186-1cb02844b32a',
        '0560518FTAM01' => '59161c9b-855e-433d-9900-32af2b5d4783',
        '1208CARTMNPRO' => 'c44f51a4-2147-49d2-8166-42757806fe5d',
        '056690000AM01' => 'b9d08c57-9970-49b5-be8e-ee00d7871eb1',
        '060200400AM01' => '404dcf48-96c1-4d28-9648-7b5b105e99f9',
        '058CTDW22AM01' => '855ac902-0857-441c-87be-8f982716b88a',
        '0580101MCAM01' => '6227f998-5ef9-4486-a240-3936d0296418',
        '0560564D0AM01' => '82f32914-2ed4-46ce-8dd0-68a18cf1a10e',
        '056053010AM01' => '0811f697-224c-43d5-898a-b78276aa5757',
        '0H5026005AM01' => '437418ec-2745-4a7a-994e-d3307951995c',
        '058047520AM01' => '3c65b527-e152-41e6-ac3c-8961d6ea35a5',
        '056201654AM01' => '39b39a92-7420-47b6-9839-ddda7e41a435',
        '056650859AM01' => '3356eb73-4d73-499e-9840-edca0f558f37',
        '056059930AM01' => '4de1682a-8514-4662-9859-feb7959e2f7a',
        '056098900AM01' => '381ea056-ac11-4e35-9f0d-45ace38307af',
        '0580495CBAM01' => '412f3ad8-4e8b-434a-b54c-f18f0e369fe2'
    ];
    private $codigo_unidade = [
        'UN' => '76ad1013-8bcd-4e09-82f0-cf8ab98d1886'
    ];
    private $codigo_uso_consumo_peso = [
        '056200900AM01' => 0.008, 
        '056200300AM01' => 0.08,
		'056201600AM01' => 0.08,
        '056050883AM01' => 0.08,
        '0560994000AM01' => 0.08,
        '0560316FTAM01' => 0.08,
        '05825BALAAM01' => 0.08,
        '058010470AM01' => 0.08,
        '056051170AM01' => 0.08,
        '055000551AM01' => 0.08,
        '058048590AM01' => 0.08,
        '03256PBE2AM01' => 0.08,
        '058058000AM01' => 0.08,
        '0582030DTAM01' => 0.08,
        '056059811AM01' => 0.08,
        '0585000063310' => 0.08,
        '05605117FAM01' => 0.08,
        '0567709D0AM01' => 0.08,
        '056604000AM01' => 0.08,
        '056024FT0AM01' => 0.08,
        '01ANDORRA0001' => 0.08,
        '058051180AM01' => 0.08,
        '05605121DAM01' => 0.08,
        '056200600AM01' => 0.08,
        '056039500AM01' => 0.08,
        '0581629MVAM01' => 0.08,
        '1208000015' => 0.08,
        '0566741E0AM01' => 0.08,
        '056390100AM01' => 0.08,
        '0560310FTAM01' => 0.08,
        '01PIQREG0AM01' => 0.08,
        '05660327DAM01' => 0.08,
        '056009500AM01' => 0.08,
        '058161000AM01' => 0.08,
        '058048600AM01' => 0.08,
        '058CTCM22AM01' => 0.08,
        '0581850FTAM01' => 0.08,
        '058500161AMO0' => 0.08,
        '058500151AMO0' => 0.08,
        '056601FT0AM01' => 0.08,
        '0560512FTAM01' => 0.08,
        '056500000AM01' => 0.08,
        '014000000AMO0' => 0.08,
        '0581626MVAM01' => 0.08,
        '0560510E0AM01' => 0.08,
        '0570063CBAM01' => 0.08,
        '0565100E0AM01' => 0.08,
        '056039660AM01' => 0.08,
        '056014050AM01' => 0.08,
        '0560114F0AM01' => 0.08,
        '056014011AM01' => 0.08,
        '05600980EAM01' => 0.08,
        '058100750AM01' => 0.08,
        '0566421D0AM01' => 0.08,
        '0565011E0AM01' => 0.08,
        '056057001AM01' => 0.08,
        '056051197AME1' => 0.08,
        '058CTDM22AM01' => 0.08,
        '056612100AM01' => 0.08,
        '058CTSS22AM01' => 0.08,
        '056039600AM01' => 0.08,
        '0H1092505AM01' => 0.08,
        '058045100AM01' => 0.08,
        '056348009AM01' => 0.08,
        '05631700FTAM01' => 0.08,
        '0581609MGAM01' => 0.08,
        '0560510D0AM01' => 0.08,
        '056008910AM01' => 0.08,
        '056008800AM01' => 0.08,
        '056059425AM01' => 0.08,
        '0H1001PCE2AM01' => 0.08,
        '058CTRV22AM01' => 0.08,
        '03256GORG0AM01' => 0.08,
        '0576302CBAM01' => 0.08,
        '056039891AM01' => 0.08,
        '042108PQ00AM01' => 0.08,
        '056600220AM01' => 0.08,
        '05804260EAM01' => 0.08,
        '0568230D0AM01' => 0.08,
        '058220260AM01' => 0.08,
        '056600980AM01' => 0.08,
        '056001041AM01' => 0.08,
        '056053270AM01' => 0.08,
        '0582030DLAM01' => 0.08,
        '001195411AM01' => 0.08,
        '058CTFM22AM01' => 0.08,
        '056009020AM01' => 0.08,
        '0H1001PCLAM01' => 0.08,
        '056014380AM01' => 0.08,
        '056099300AM01' => 0.08,
        '0563690FTAM01' => 0.08,
        '0359110SUPAM01' => 0.08,
        '0566581D0AM01' => 0.08,
        '0582505PVAM01' => 0.08,
        '05603995DAM01' => 0.08,
        '056611300AM01' => 0.08,
        '05660529DAM01' => 0.08,
        '056316FT0AM01' => 0.08,
        '058DRP150AM01' => 0.08,
        '05603714DAM01' => 0.08,
        '056500065AM01' => 0.08,
        '05880241MAM01' => 0.08,
        '0583452TPAM01' => 0.08,
        '0563530FTAM01' => 0.08,
        '019FRALD0AM01' => 0.08,
        '0566002685AM01' => 0.08,
        '056039140AM01' => 0.08,
        '056099600AM01' => 0.08,
        '05601859DAM01' => 0.08,
        '056634900AM01' => 0.08,
        '058CTST22AM01' => 0.08,
        '0563170FTAM01' => 0.08,
        '0563650FTAM01' => 0.08,
        '0581605MMAM01' => 0.08,
        '0582504PPAM01' => 0.08,
        '056999800AM01' => 0.08,
        '05642108PAM01' => 0.08,
        '056603100AM01' => 0.08,
        '054001080AM01' => 0.08,
        '058385G97AM01' => 0.08,
        '057006003AM01' => 0.08,
        '0588805TVAM01' => 0.08,
        '056001040AM01' => 0.08,
        '0568010D0AM01' => 0.08,
        '056612300AM01' => 0.08,
        '055000541AM01' => 0.08,
        '05806BOBYAM01' => 0.08,
        '056600600AM01' => 0.08,
        '058260301AM01' => 0.08,
        '058162251AM01' => 0.08,
        '05631733EAM01' => 0.08,
        '056200800AM01' => 0.08,
        '05400MAX0AM01' => 0.08,
        '0H1001PC20AM01' => 0.08,
        '054MAXLI0AM02' => 0.08,
        '058161600AM01' => 0.08,
        '0560510CBAM01' => 0.08,
        '056389FT0AM01' => 0.08,
        '056607100AM01' => 0.08,
        '058010455AM01' => 0.08,
        '05806TEXTAM01' => 0.08,
        '05609895EAM01' => 0.08,
        '056201601AM01' => 0.08,
        '056600200AM01' => 0.08,
        '056058800AM01' => 0.08,
        '0560202D0AM01' => 0.08,
        '056059910AM01' => 0.08,
        '056670000AM01' => 0.08,
        '058B15001AM01' => 0.08,
        '056603000AM01' => 0.08,
        '05605147FTAM01' => 0.08,
        '057005820AM01' => 0.08,
        '0581628MVAM01' => 0.08,
        '056500100AM01' => 0.08,
        '0580116FMAM01' => 0.08,
        '0570060CBAM01' => 0.08,
        '05601995DAM01' => 0.08,
        '058CTPN22AM01' => 0.08,
        '0583352MPAM01' => 0.08,
        '0562005E0AM01' => 0.08,
        '02PILOTAGEMCT' => 0.08,
        '0560318FTAM01' => 0.08,
        '056390FT0AM01' => 0.08,
        '058CTCD22AM01' => 0.08,
        '056162251AM01' => 0.08,
        '056650270AM01' => 0.08,
        '056009001AM01' => 0.08,
        '058161900AM01' => 0.08,
        '056059540AM01' => 0.08,
        '0566128000AM01' => 0.08,
        '058161800AM01' => 0.08,
        '057006102AM01' => 0.08,
        '058721405AM01' => 0.08,
        '056660800AM01' => 0.08,
        '058041800AM01' => 0.08,
        'HAMOSTRA2019F' => 0.08,
        '0585065INAM01' => 0.08,
        '056059210AM01' => 0.08,
        '05819FRALAM01' => 0.08,
        '05600220DAM01' => 0.08,
        '056612800AM01' => 0.08,
        '0H1001PC2PAM01' => 0.08,
        '056020000AM01' => 0.08,
        '0581607MCAM01' => 0.08,
        '056022FT0AM01' => 0.08,
        '05600190LAM01' => 0.08,
        '058CTSA22AM01' => 0.08,
        '056099400AM01' => 0.08,
        '058162700AM01' => 0.08,
        '058063100AM01' => 0.08,
        '056610558AM01' => 0.08,
        '0566002583AM01' => 0.08,
        '0566410D0AM01' => 0.08,
        '056607585AM01' => 0.08,
        '056600111AM01' => 0.08,
        '056059520AM01' => 0.08,
        '058101990AM01' => 0.08,
        '0560677E0AM01' => 0.08,
        '056039100AM01' => 0.08,
        '015101011AM01' => 0.08,
        '058421070AM01' => 0.08,
        '001195400AM01' => 0.08,
        '056800900AM01' => 0.08,
        '056600360AM01' => 0.08,
        '05600911DAM01' => 0.08,
        '056051350AM01' => 0.08,
        '0560956D0AM01' => 0.08,
        '0581633MMAM01' => 0.08,
        '058CTCT22AM01' => 0.08,
        '056015120AM01' => 0.08,
        '01ANCARTW' => 0.08,
        '581600AMOSTRA' => 0.08,
        '0581291CSAM01' => 0.08,
        '056200199AM01' => 0.08,
        '0560530E0AM01' => 0.08,
        '0560515FTAM01' => 0.08,
        '025PERC0E0AM01' => 0.08,
        '056053260AM01' => 0.08,
        '056014383AM01' => 0.08,
        '05605105FAM01' => 0.08,
        '056039990AM01' => 0.08,
        '056610129AM01' => 0.08,
        '056059780AM01' => 0.08,
        '01LIGHTBLUE01' => 0.08,
        '057006200AM01' => 0.08,
        '056008515AM01' => 0.08,
        '05666779DAM01' => 0.08,
        '027110000AM01' => 0.08,
        '0580477SMAM01' => 0.08,
        '056612821AM01' => 0.08,
        '056200900AM01' => 0.08,
        '05605280DAM01' => 0.08,
        '05600121DAM01' => 0.08,
        '056039600AM02' => 0.08,
        '0581601MAAM01' => 0.08,
        '058470341AM01' => 0.08,
        '056099200AM01' => 0.08,
        '0560569D0AM01' => 0.08,
        '05821STUDAM01' => 0.08,
        '058CTTY22AM01' => 0.08,
        'AMOSTRA' => 0.08,
        '056652400AM01' => 0.08,
        '0581612MPAM01' => 0.08,
        '056059530AM01' => 0.08,
        '058CTAB22AM01' => 0.08,
        '0566602E0AM01' => 0.08,
        '057928SUPAM01' => 0.08,
        '0560113F0AM01' => 0.08,
        '0580670E0AM01' => 0.08,
        '056059030AM01' => 0.08,
        '0560064FTAM01' => 0.08,
        '056600180AM01' => 0.08,
        '056660500AM01' => 0.08,
        '0580462SLAM01' => 0.08,
        '056059920AM01' => 0.08,
        '0560517E0AM01' => 0.08,
        '0570058CBAM01' => 0.08,
        '058100730AM01' => 0.08,
        '058101471AM01' => 0.08,
        '058022026AM01' => 0.08,
        '0566377D0AM01' => 0.08,
        '058160600AM01' => 0.08,
        '058CTAW22AM01' => 0.08,
        '058101330AM01' => 0.08,
        '05601960DAM01' => 0.08,
        '056200200AM01' => 0.08,
        '058018000AM01' => 0.08,
        '056060FT0AM01' => 0.08,
        '05665223DAM01' => 0.08,
        '057006205AM01' => 0.08,
        '056054512AM01' => 0.08,
        '0581635MDAM01' => 0.08,
        '056059912AM01' => 0.08,
        '058580301AM01' => 0.08,
        '056222001AM01' => 0.08,
        '058161400AM01' => 0.08,
        '0566580D0AM01' => 0.08,
        '056800290AM01' => 0.08,
        '056600190AM01' => 0.08,
        '056009991AM01' => 0.08,
        '0359110PL0AM01' => 0.08,
        '058CTCV22AM01' => 0.08,
        '058160800AM01' => 0.08,
        '056052100AM01' => 0.08,
        '056654010AM01' => 0.08,
        '05662126DAM01' => 0.08,
        '05665223DAM02' => 0.08,
        '056624970AM01' => 0.08,
        '058104556AM01' => 0.08,
        '056600100AM01' => 0.08,
        '001194422AM01' => 0.08,
        '056699200AM01' => 0.08,
        '056099100AM01' => 0.08,
        '058710000AM01' => 0.08,
        '05665091DAM01' => 0.08,
        '014000001' => 0.08,
        '056608200AM01' => 0.08,
        '0566002785AM01' => 0.08,
        '057005900AM01' => 0.08,
        '060200300AM01' => 0.08,
        '056039670AM01' => 0.08,
        '056023FT0AM01' => 0.08,
        '058500191AMO0' => 0.08,
        '0560562D0AM01' => 0.08,
        '0H1816005AM01' => 0.08,
        '054MAXHI0AM01' => 0.08,
        '056201632AM01' => 0.08,
        '0586013PPAM01' => 0.08,
        '0H1001PCAAM01' => 0.08,
        '056622500AM01' => 0.08,
        '058CTCR22AM01' => 0.08,
        '056201700AM01' => 0.08,
        '056645100AM01' => 0.08,
        '05601860DAM01' => 0.08,
        '1208000013' => 0.08,
        '015101000AM01' => 0.08,
        '056009998AM01' => 0.08,
        '058044100AM01' => 0.08,
        '0H1816116AM01' => 0.08,
        '05600154DAM01' => 0.08,
        '056099500AM01' => 0.08,
        '056600310AM01' => 0.08,
        '054MAXPLUSAM01' => 0.08,
        '0H1001PC2PAC01' => 0.08,
        '058225V00AM01' => 0.08,
        '0567622D0AM01' => 0.08,
        '056009900AM01' => 0.08,
        '03256C0TL5050' => 0.08,
        '0580056SGAM01' => 0.08,
        '0580476SMAM01' => 0.08,
        '058225V86AM01' => 0.08,
        '0565010E0AM01' => 0.08,
        '056000000AM01' => 0.08,
        '058015170AM01' => 0.08,
        '05600135DAM01' => 0.08,
        '05806XADPAM01' => 0.08,
        '056335100AM01' => 0.08,
        '056051016AM01' => 0.08,
        '01FELPA15AM01' => 0.08,
        '056051011AM01' => 0.08,
        '056382000AM01' => 0.08,
        '0581638MDAM01' => 0.08,
        '0566443D0AM01' => 0.08,
        '056670098AM01' => 0.08,
        '001194400AM01' => 0.08,
        '056672400AM01' => 0.08,
        '056603111AM01' => 0.08,
        '056051010AM01' => 0.08,
        '056609429AM01' => 0.08,
        '056312310AM01' => 0.08,
        '05600622DAM01' => 0.08,
        'IT8010100225' => 0.08,
        '0580398000AM0' => 0.08,
        '056039650AM01' => 0.08,
        '057006302AM01' => 0.08,
        '056085150AM01' => 0.08,
        '057006GOLAM01' => 0.08,
        '056010000AM01' => 0.08,
        '056602100AM01' => 0.08,
        '056014690AM01' => 0.08,
        '056312309AM01' => 0.08,
        '003537003' => 0.08,
        '056039920AM01' => 0.08,
        '0567733D0AM01' => 0.08,
        '056658200AM01' => 0.08,
        '0560988D0AM01' => 0.08,
        '006476' => 0.08,
        '0568120D0AM01' => 0.08,
        '056604FT0AM01' => 0.08,
        '056560000AM01' => 0.08,
        '0583701FDAM01' => 0.08,
        '057005910AM01' => 0.08,
        '0359110H00AM01' => 0.08,
        '0560526FTAM01' => 0.08,
        '056051197AM01' => 0.08,
        '058100830AM01' => 0.08,
        '056056310AM01' => 0.08,
        '0560457F0AM01' => 0.08,
        '056658300AM01' => 0.08,
        '0563390100AM01' => 0.08,
        '056035650AM01' => 0.08,
        '3256AMOSTRA01' => 0.08,
        '056331FT0AM01' => 0.08,
        '056006313AM01' => 0.08,
        '056634860AM01' => 0.08,
        '056009700AM01' => 0.08,
        '056059220AM01' => 0.08,
        '056090828AM01' => 0.08,
        '0581630MVAM01' => 0.08,
        '058251049AM01' => 0.08,
        '0581634MTAM01' => 0.08,
        '056200400AM01' => 0.08,
        '05669101DAM01' => 0.08,
        '056317500AM01' => 0.08,
        '001195500AM01' => 0.08,
        '056600987AM01' => 0.08,
        '056099000AM01' => 0.08,
        '0225Z640AM001' => 0.08,
        '058104552AM01' => 0.08,
        '010002860AM01' => 0.08,
        '058500181AMO0' => 0.08,
        '056650300AM01' => 0.08,
        '056059926AM01' => 0.08,
        '058250400AM01' => 0.08,
        '05665310DAM01' => 0.08,
        '056604889AM01' => 0.08,
        '060200200AM01' => 0.08,
        '056371000AM01' => 0.08,
        '058108590AM01' => 0.08,
        '0550005CBAM01' => 0.08,
        '05605524DAM01' => 0.08,
        '0580670000AM0' => 0.08,
        '0580634000AM0' => 0.08,
        '0563510FTAM01' => 0.08,
        '058250300AM01' => 0.08,
        '056605FT0AM01' => 0.08,
        '058100820AM01' => 0.08,
        '056600280AM01' => 0.08,
        '056598000AM01' => 0.08,
        '056301800AM01' => 0.08,
        '0560010000AM01' => 0.08,
        '0560017L0AM01' => 0.08,
        '0225Y790AM001' => 0.08,
        '0570059CBAM01' => 0.08,
        '058049580AM01' => 0.08,
        '056601400AM01' => 0.08,
        '001194411AM01' => 0.08,
        '058470301AM01' => 0.08,
        '0560396CBAM01' => 0.08,
        '056600172AM01' => 0.08,
        '0550054CBAM01' => 0.08,
        '056612400AM01' => 0.08,
        '05631525EAM01' => 0.08,
        '0580461SLAM01' => 0.08,
        '0560500D0AM01' => 0.08,
        '0H3008114AM01' => 0.08,
        '058CTSJ22AM01' => 0.08,
        '058CTSF22AM01' => 0.08,
        '056610345AM01' => 0.08,
        '014000000AMO01' => 0.08,
        '056020100AM01' => 0.08,
        '056623010AM01' => 0.08,
        '05655100DAM01' => 0.08,
        '560479TTAM001' => 0.08,
        '054001090AM01' => 0.08,
        '05603999AM01' => 0.08,
        '056651300AM01' => 0.08,
        '025PERC00AM01' => 0.08,
        '05605210CRTLA' => 0.08,
        '056199600AM01' => 0.08,
        '056500522AM01' => 0.08,
        '058500141AMO0' => 0.08,
        '05660125DAM01' => 0.08,
        '0566010D0AM01' => 0.08,
        '058160400AM01' => 0.08,
        '0563171100AM01' => 0.08,
        '03256PCC0AM01' => 0.08,
        '05605600DAM01' => 0.08,
        '056612500AM01' => 0.08,
        '056800291AM01' => 0.08,
        '056069652AM01' => 0.08,
        '056987D00AM01' => 0.08,
        '056051150AM01' => 0.08,
        '035911M00AM01' => 0.08,
        '056039940AM01' => 0.08,
        '0565122D0AM01' => 0.08,
        '056988FT0AM01' => 0.08,
        '056600151AM01' => 0.08,
        '0582501PBAM01' => 0.08,
        '1208000003' => 0.08,
        '0563710FTAM01' => 0.08,
        '0560020LXAM01' => 0.08,
        '0566075D0AM01' => 0.08,
        '0563171D0AM01' => 0.08,
        '058051000AM01' => 0.08,
        '0560203D0AM01' => 0.08,
        '056600112AM01' => 0.08,
        '058275082AM01' => 0.08,
        '056670099AM01' => 0.08,
        '001193400AM01' => 0.08,
        '058CTAP22AM01' => 0.08,
        '056025FT0AM01' => 0.08,
        '056051110AM01' => 0.08,
        '035911010AM01' => 0.08,
        '056200700AM01' => 0.08,
        '010002850AM01' => 0.08,
        '03256B146AM01' => 0.08,
        '056019920AM01' => 0.08,
        '001193422AM01' => 0.08,
        '056201682AM01' => 0.08,
        '056670200AM01' => 0.08,
        '0582505CSAM01' => 0.08,
        '0560315D0AM01' => 0.08,
        '0580485CBAM01' => 0.08,
        '1208000002' => 0.08,
        '01193500AM01' => 0.08,
        '14000000AMO01' => 0.08,
        '05600200DAM01' => 0.08,
        '0582502BRAM01' => 0.08,
        '0580513TBAM01' => 0.08,
        '05600999LAM01' => 0.08,
        '05605120DAM01' => 0.08,
        '0560399E0AM01' => 0.08,
        '058500171AMO0' => 0.08,
        '057006301AM01' => 0.08,
        '01PIQUET00AM01' => 0.08,
        '057006000AM01' => 0.08,
        '0565980D0AM01' => 0.08,
        '056009990AM01' => 0.08,
        '056097710AM01' => 0.08,
        '056108600AM01' => 0.08,
        '0560988000AM01' => 0.08,
        '058CTAN22AM01' => 0.08,
        '0580180UMAM01' => 0.08,
        '0563194E0AM01' => 0.08,
        '056002200AM01' => 0.08,
        '0560988CBAM01' => 0.08,
        '0563160E0AM01' => 0.08,
        '0581603MNAM01' => 0.08,
        '056009650AM01' => 0.08,
        '056019500AM01' => 0.08,
        '001194500AM01' => 0.08,
        '058CTSG22AM01' => 0.08,
        '056201448AM01' => 0.08,
        '056600109AM01' => 0.08,
        '0583844DNAM01' => 0.08,
        '058101530AM01' => 0.08,
        '056026FT0AM01' => 0.08,
        '05665201DAM01' => 0.08,
        '058CTDA22AM01' => 0.08,
        '0560514D0AM01' => 0.08,
        '581631MMPAM01' => 0.08,
        '058CTVC22AM01' => 0.08,
        '058010630AM01' => 0.08,
        '058500046AMO0' => 0.08,
        '0581635MCAM01' => 0.08,
        '0560391CBAM01' => 0.08,
        '060PERK00AM01' => 0.08,
        '058047530AM01' => 0.08,
        '057612000AM01' => 0.08,
        '0566520FTAM01' => 0.08,
        '0560021000AM01' => 0.08,
        '56396AT001000' => 0.08,
        '056014160AM01' => 0.08,
        '0586123000AM0' => 0.08,
        '056600125AM01' => 0.08,
        '056050165AM01' => 0.08,
        '058H10955AM01' => 0.08,
        '056600490AM01' => 0.08,
        '058CTMNP1AM01' => 0.08,
        '0566089E0AM01' => 0.08,
        '01PIQUETSAM01' => 0.08,
        '05603401EAM01' => 0.08,
        '056300000AM01' => 0.08,
        '056051000AM01' => 0.08,
        '056069990AM01' => 0.08,
        '0582030DBAM01' => 0.08,
        '058CTVF22AM01' => 0.08,
        '058CTCG22AM01' => 0.08,
        '056059650AM01' => 0.08,
        '0580514TFAM01' => 0.08,
        '05601970DAM01' => 0.08,
        '0562007E0AM01' => 0.08,
        '056053000AM01' => 0.08,
        '001193411AM01' => 0.08,
        '0560518FTAM01' => 0.08,
        '1208CARTMNPRO' => 0.08,
        '056690000AM01' => 0.08,
        '060200400AM01' => 0.08,
        '058CTDW22AM01' => 0.08,
        '0580101MCAM01' => 0.08,
        '0560564D0AM01' => 0.08,
        '056053010AM01' => 0.08,
        '0H5026005AM01' => 0.08,
        '058047520AM01' => 0.08,
        '056201654AM01' => 0.08,
        '056650859AM01' => 0.08,
        '056059930AM01' => 0.08,
        '056098900AM01' => 0.08,
        '0580495CBAM01' => 0.08,
    ];
    private $codigo_uso_consumo_descricao = [
        '056200900AM01' => 'CARTELA HELANCA SCHOOL', 
        '056200300AM01' => 'CARTELA HELANCA PLUS',
		'056201600AM01' => 'CARTELA DENIM SANIBEL PLUS 001 BLUE',
        '056050883AM01' => 'CARTELA DENIM SPIRIT',
        '0560994000AM01' => 'CARTELA VISCOSE SLUB COM ELASTANO CAMPECHE',
        '0560316FTAM01' => 'CARTELA TRICOLINE CATALUNIA STRIPE',
        '05825BALAAM01' => 'CARTELA TRICOSTAR BALAOZINHO NUVENS',
        '058010470AM01' => 'CARTELA  WORKFIT',
        '056051170AM01' => 'CARTELA TRICOLINE ATLANTICA ECO-BCI',
        '055000551AM01' => 'CARTELA MOLETOM',
        '058048590AM01' => 'CARTELA SARJA PELETIZADA SEGOVIA',
        '03256PBE2AM01' => 'CARTELA PERCAL BRISA ESTAMPADO',
        '058058000AM01' => 'CARTELA FIL A FIL',
        '0582030DTAM01' => 'CARTELA DENIM TRANCOSO',
        '056059811AM01' => 'CARTELA MALHA FRIA POOL',
        '0585000063310' => 'CARTELA DENIM YARIS PREMIUM 633/10',
        '05605117FAM01' => 'CARTELA TRICOLINE FT ATLANTICA ECO-BCI',
        '0567709D0AM01' => 'CARTELA BORDADO COTE DE AZUIR MULTICOLOR',
        '056604000AM01' => 'CARTELA CREPE MOUSSON',
        '056024FT0AM01' => 'CARTELA TRICOLINE WORK CITY STRIPES FT',
        '01ANDORRA0001' => 'CARTELA TEC. ANDORRA',
        '058051180AM01' => 'CARTELA TRICOLINE ROMA PREMIUM ECO',
        '05605121DAM01' => 'CARTELA TRICOLINE CERVANTES DOBBY',
        '056200600AM01' => 'CARTELA MALHA NEOPRENE SOFT',
        '056039500AM01' => 'CARTELA AIR FLOW ECO',
        '0581629MVAM01' => 'CARTELA MALHA VISCO LINO LARGE',
        '1208000015' => 'CARTELA DUPLA ECO 300GR',
        '0566741E0AM01' => 'CARTELA RAYON POPLIN VOYAGE PRINTED',
        '056390100AM01' => 'CARTELA CHAMBRAY LINEN COTTON',
        '0560310FTAM01' => 'CARTELA TRICOLINE CATALUNIA',
        '01PIQREG0AM01' => 'CARTELA PIQUET REGGIO',
        '05660327DAM01' => 'CARTELA RENDA SUECA DE BICO',
        '056009500AM01' => 'CARTELA HI MULTI CHIFON LISO',
        '058161000AM01' => 'CARTELA MALHA MICRO ORIGAMI',
        '058048600AM01' => 'CARTELA SARJA PELETIZADA TEXAS',
        '058CTCM22AM01' => 'CARTELA CONSTANCIO MODA',
        '0581850FTAM01' => 'CARTELA FORRO LISTRADO FT',
        '058500161AMO0' => 'CARTELA DENIM FLEX MOLD',
        '058500151AMO0' => 'CARTELA DENIM LIBERTY',
        '056601FT0AM01' => 'CARTELA LINEN COTTON LUXOR',
        '0560512FTAM01' => 'CARTELA FLANELA CHECK EVEREST',
        '056500000AM01' => 'CARTELA TECIDO PARA COBERTOR',
        '014000000AMO0' => 'CARTELA TEXTIL MN',
        '0581626MVAM01' => 'CARTELA MALHA VISCO LINO RIB',
        '0560510E0AM01' => 'CARTELA TRICOLINE AMALFI PRINT',
        '0570063CBAM01' => 'CARTELA 1/2 MALHA MESCLA PA 30/1 CINZA CABIDE',
        '0565100E0AM01' => 'CARTELA MICROFIBRA SOFT ESTAMPADA',
        '056039660AM01' => 'CARTELA CORTA VENTO RIPSTOP DUPLA FACE WATER PROOF',
        '056014050AM01' => 'CARTELA COURO MAX MARA',
        '0560114F0AM01' => 'CARTELA TRICOLINE FIL A FIL URBAN',
        '056014011AM01' => 'CARTELA TWO WAY SPANDEX',
        '05600980EAM01' => 'CARTELA OXFORD PARMA ESTAMPADO',
        '058100750AM01' => 'CARTELA 8 X M',
        '0566421D0AM01' => 'CARTELA TULE BORDADO PEDRARIA MICHELY',
        '0565011E0AM01' => 'CARTELA MATELASSE DUPLA FACE SUBLIME',
        '056057001AM01' => 'CARTELA DENIM ONIX PLUS',
        '056051197AME1' => 'CARTELA TRICOLINE BERLIN ESTAMPADO',
        '058CTDM22AM01' => 'CARTELA DOPTEX MALHARIA',
        '056612100AM01' => 'CARTELA CREPE MOUSSE SPAN PD 101 OPTICAL WHITE',
        '058CTSS22AM01' => 'CARTELA SISA',
        '056039600AM01' => 'CARTELA CHIFFON STRIPES SOHO',
        '0H1092505AM01' => 'CARTELA PIQUET VENEZA',
        '058045100AM01' => 'CARTELA SARJA LONDRES PESADA',
        '056348009AM01' => 'CARTELA DENIM CHAMBRAY GABBANA',
        '05631700FTAM01' => 'CARTELA TRICOLINE SLUB GOYA FT',
        '0581609MGAM01' => 'CARTELA MALHA GRAFIC',
        '0560510D0AM01' => 'CARTELA TRICOLINE BULGATTI FIO TINTO DOBBY',
        '056008910AM01' => 'CARTELA CETIM SUECIA LISO',
        '056008800AM01' => 'CARTELA CETIM SUECIA PRINTED',
        '056059425AM01' => 'CARTELA MALHA FRENCH KASHIMIRA MELANGE',
        '0H1001PCE2AM01' => 'CARTELA PERCAL AMETISTA PLUS ESTAMPADO',
        '058CTRV22AM01' => 'CARTELA ROVACH',
        '03256GORG0AM01' => 'CARTELA GORGURINHO',
        '0576302CBAM01' => 'CARTELA 1/2 MALHA PA 30/1  BANANA MESCLA  ESP CABIDE',
        '056039891AM01' => 'CARTELA SATTEN TWILL ZARA',
        '042108PQ00AM01' => 'CARTELA PIQUET QUADRADO',
        '056600220AM01' => 'CARTELA SARJA BLEND NORONHA',
        '05804260EAM01' => 'CARTELA SARJA COMBAT',
        '0568230D0AM01' => 'CARTELA BORDADO 3D COM PEDRARIA SHINE',
        '058220260AM01' => 'CARTELA UNISTILL SOFT',
        '056600980AM01' => 'CARTELA BARBIE SPAN PRINTED',
        '056001041AM01' => 'CARTELA TRICOLINE WORK CITY',
        '056053270AM01' => 'CARTELA FORRO DE BOLSO MESSINA',
        '0582030DLAM01' => 'CARTELA DENIM LILLE',
        '001195411AM01' => 'CARTELA DOBBY SANTISTA 4 HAL',
        '058CTFM22AM01' => 'CARTELA FERNANDO MALUHY',
        '056009020AM01' => 'CARTELA SATEEN SPAN HERMES LISO',
        '0H1001PCLAM01' => 'CARTELA PERCAL AMETISTA PLUS 180 FIOS LISTRADO',
        '056014380AM01' => 'CARTELA MALHA CIRRE',
        '056099300AM01' => 'CARTELA VISCOSE SARJA FALESIA ECO',
        '0563690FTAM01' => 'CARTELA TRICOLINE FAIYAJ DOBBY',
        '0359110SUPAM01' => 'CARTELA TECIDO MADRID PLUS',
        '0566581D0AM01' => 'CARTELA ALFAIATARIA ROCHESTER DOBBY',
        '0582505PVAM01' => 'CARTELA PERCAL VIVIANE',
        '05603995DAM01' => 'CARTELA CETIM GABBANA ESTAMPADO',
        '056611300AM01' => 'CARTELA LIQUID FLUID MICHELY',
        '05660529DAM01' => 'CARTELA RENDA NOTREDAME TWO TONES',
        '056316FT0AM01' => 'CARTELA LINEN COTTON STRIPED',
        '058DRP150AM01' => 'CARTELA COBERTOR PLUS DREAMS SOLTEIRO',
        '05603714DAM01' => 'CARTELA BANDEIRA BRASIL',
        '056500065AM01' => 'CARTELA DENIM CAMISARIA VISEU',
        '05880241MAM01' => 'CARTELA MEIA MALHA COTTON',
        '0583452TPAM01' => 'CARTELA TRICOLINE PRIME SPAN ACETINADA',
        '0563530FTAM01' => 'CARTELA TRICOLINE HILFIGER',
        '019FRALD0AM01' => 'CARTELA TECIDO P/ FRALDA',
        '0566002685AM01' => 'CARTELA LINEN RAYON PARATY',
        '056039140AM01' => 'CARTELA TAFETA BANDEIRA BRASIL',
        '056099600AM01' => 'CARTELA VISCOSE NASSAU SLUB PLUS',
        '05601859DAM01' => 'CARTELA MALHA MOLETON MATELASSE 3D',
        '056634900AM01' => 'CARTELA FEMME TOUCH TECHNO',
        '058CTST22AM01' => 'CARTELA SANTANENSE',
        '0563170FTAM01' => 'CARTELA TRICOLINE SLUB GOYA FT',
        '0563650FTAM01' => 'CARTELA TRICOLINE VARENNE DOBBY',
        '0581605MMAM01' => 'CARTELA MALHA MONTARIA PRIME',
        '0582504PPAM01' => 'CARTELA PERCALE PRO PREMIUM',
        '056999800AM01' => 'CARTELA CETIM CHARMOUSE LISO',
        '05642108PAM01' => 'CARTELA PIQUET QUADRADO ABERTO 2,50 LG',
        '056603100AM01' => 'CARTELA LA BATIDA AMISTERDA',
        '054001080AM01' => 'CARTELA ALGODAO LIGHT',
        '058385G97AM01' => 'CARTELA MOLETOM FELPADO NEVADA',
        '057006003AM01' => 'CARTELA 1/2 MALHA MESCLA PA 30/1 CINZA',
        '0588805TVAM01' => 'CARTELA TERGAL VERAO II',
        '056001040AM01' => 'CARTELA TRICOLINE WORK TOKIO LISO',
        '0568010D0AM01' => 'CARTELA BORDADO COM PEDRARIA GAP',
        '056612300AM01' => 'CARTELA CREPE AYA SOFT DULL',
        '055000541AM01' => 'CARTELA MOLETINHO',
        '05806BOBYAM01' => 'CARTELA BOBYLEEN NATURAL',
        '056600600AM01' => 'CARTELA LINHO BLEND ALOHA',
        '058260301AM01' => 'CARTELA FLANELA LISA EXTRA II',
        '058162251AM01' => 'CARTELA ALFAIATARIA PALACE',
        '05631733EAM01' => 'CARTELA TRICOLINE SLUB MALAGA PRINTED',
        '056200800AM01' => 'CARTELA PLUSH NEPAL',
        '05400MAX0AM01' => 'CARTELA MAXLINHOL MEDIUM',
        '0H1001PC20AM01' => 'CARTELA PERCAL AMETISTA PLUS 200 FIOS LIGHT',
        '054MAXLI0AM02' => 'CARTELA MAXLINHOL LIGH',
        '058161600AM01' => 'CARTELA MALHA VISCO CANALE',
        '0560510CBAM01' => 'CARTELA TRICOLINE PARIS SPAN CABIDE',
        '056389FT0AM01' => 'CARTELA TRICOLINE SOFT DUBAI',
        '056607100AM01' => 'CARTELA BOUBLE FASHION LISO',
        '058010455AM01' => 'CARTELA  SKY WORK',
        '05806TEXTAM01' => 'CARTELA TEXTOLEEN',
        '05609895EAM01' => 'CARTELA VISCOSE MELANGE PRINTED',
        '056201601AM01' => 'CARTELA DENIM ROYALE FLEX',
        '056600200AM01' => 'CARTELA CREPE FOUR WAY',
        '056058800AM01' => 'CARTELA DENIM PHOENIX',
        '0560202D0AM01' => 'CARTELA LAISE TC CARICIA',
        '056059910AM01' => 'CARTELA BEMBER PLUS',
        '056670000AM01' => 'CARTELA CREPE GEORGETE GGT SOFT LISO',
        '058B15001AM01' => 'CARTELA GAB MICROF MAQ',
        '056603000AM01' => 'CARTELA LA SUICA BATIDA',
        '05605147FTAM01' => 'CARTELA TRICOLINE ATLANTA FIO TINTO FT01-01',
        '057005820AM01' => 'CARTELA 1/2 MALHA PENT. VENEZA',
        '0581628MVAM01' => 'CARTELA MALHA VISCO LINO TRICO',
        '056500100AM01' => 'CARTELA TECIDO LENCOL LISO FLOWERS 2,25',
        '0580116FMAM01' => 'CARTELA FORRO MISTO',
        '0570060CBAM01' => 'CARTELA 1/2 MALHA MESCLA 30/1 CABIDE',
        '05601995DAM01' => 'CARTELA VOIL RAYON DOBBY VERS FIO 60',
        '058CTPN22AM01' => 'CARTELA TESSIFIL PANAMA',
        '0583352MPAM01' => 'CARTELA TRICOLINE MAXFILL PREMIUM LISA',
        '0562005E0AM01' => 'CARTELA FLEECE POLAR ESTAMPADO',
        '02PILOTAGEMCT' => 'PILOTAGEM PARA CARTELA',
        '0560318FTAM01' => 'CARTELA TRICOLINE CATALUNIA CHECK',
        '056390FT0AM01' => 'CARTELA TRICOLINE DOBBY BARI',
        '058CTCD22AM01' => 'CARTELA CEDRO',
        '056162251AM01' => 'CARTELA OXFORD STRETCH',
        '056650270AM01' => 'CARTELA PELE FUR ALASCA',
        '056009001AM01' => 'CARTELA BEMBER BASIC',
        '058161900AM01' => 'CARTELA MALHA CANELADA MIKONOS',
        '056059540AM01' => 'CARTELA GALAXY SURF WEAR SOUL',
        '0566128000AM01' => 'CARTELA CREPE AMANDA HIGH TWIST PLUS',
        '058161800AM01' => 'CARTELA MALHA VISCO TRICO',
        '057006102AM01' => 'CARTELA MALHA CANELADA',
        '058721405AM01' => 'CARTELA TNT 40',
        '056660800AM01' => 'CARTELA BACK SATIM TRIPOLI',
        '058041800AM01' => 'CARTELA SARJA PELETIZADA AMSTERDAM',
        'HAMOSTRA2019F' => 'CARTELA CONJUNTO HOSPITALAR',
        '0585065INAM01' => 'CARTELA INDIGO 5,5 OZ',
        '056059210AM01' => 'CARTELA DENIM GRENELLE',
        '05819FRALAM01' => 'CARTELA TECIDO FRALDA',
        '05600220DAM01' => 'CARTELA CASIMIRA LONDON SHINE',
        '056612800AM01' => 'CARTELA CREPE AMANDA HITWIST',
        '0H1001PC2PAM01' => 'CARTELA PERCAL AMETISTA PLUS 200 FIOS PLUS',
        '056020000AM01' => 'CARTELA VOIL POEME TINTO',
        '0581607MCAM01' => 'CARTELA MALHA CANELADA FLAT',
        '056022FT0AM01' => 'CARTELA TRICOLINE WORK CITY FIL A FIL',
        '05600190LAM01' => 'CARTELA TRICOLINE WORK TOKIO L505',
        '058CTSA22AM01' => 'CARTELA SANTISTA',
        '056099400AM01' => 'CARTELA VISCOSE SLUB COM ELASTANO CAMPECHE',
        '058162700AM01' => 'CARTELA MALHA VISCO LINO PLUS',
        '058063100AM01' => 'CARTELA TRICOLINE TINTA DEVILLE',
        '056610558AM01' => 'CARTELA ENTRETELA COLANTE DE MALHA',
        '0566002583AM01' => 'CARTELA LINEN RAYON ANGRA',
        '0566410D0AM01' => 'CARTELA TULE BORDADO MICHELANGELO',
        '056607585AM01' => 'CARTELA TULE FLUID',
        '056600111AM01' => 'CARTELA BENGALINE CONFORT',
        '056059520AM01' => 'CARTELA GALAXY SURF WEAR',
        '058101990AM01' => 'CARTELA  KOBE JOB',
        '0560677E0AM01' => 'CARTELA VISCOSE BALLI ESTAMPADA',
        '056039100AM01' => 'CARTELA PEACH CHIFFON LIQUID',
        '015101011AM01' => 'CARTELA CRETONE SISA LISTRADO PLUS',
        '058421070AM01' => 'CARTELA TERGAL VERAO',
        '001195400AM01' => 'CARTELA DOBBY SANTISTA 4W0I',
        '056800900AM01' => 'CARTELA PERCAL TURQUESA',
        '056600360AM01' => 'CARTELA FAKE LINEN PD',
        '05600911DAM01' => 'CARTELA TAFFETA NEWGATES JACQUARD',
        '056051350AM01' => 'CARTELA TRICOLINE LINEN LOOK',
        '0560956D0AM01' => 'CARTELA TAFFETA TRAFALGAR DOBBY',
        '0581633MMAM01' => 'CARTELA MEIA MALHA DRY',
        '058CTCT22AM01' => 'CARTELA CONSTANCIO',
        '056015120AM01' => 'CARTELA TRICOLINE CLASSIC ROYALE',
        '01ANCARTW' => 'CARTELAS',
        '581600AMOSTRA' => 'CARTELA DE MALHAS',
        '0581291CSAM01' => 'CARTELA SARJA CIT SPAN',
        '056200199AM01' => 'CARTELA MALHA NEOPRENE STAR',
        '0560530E0AM01' => 'CARTELA TRICOLINE ESTAMPADA DEVILLE PLUS',
        '0560515FTAM01' => 'CARTELA FLANELA MONTERREY',
        '025PERC0E0AM01' => 'CARTELA PERCAL BELLA ESTAMPADO',
        '056053260AM01' => 'CARTELA FORRO DE BOLSO EGEO',
        '056014383AM01' => 'CARTELA CIRRE ZOE',
        '05605105FAM01' => 'CARTELA TRICOLINE BULGATTI FIO TINTO',
        '056039990AM01' => 'CARTELA TECNOSPORT',
        '056610129AM01' => 'CARTELA BENGALINE GERMANY SUPER POWER',
        '056059780AM01' => 'CARTELA VELUDO CRISTAL BARBIE',
        '01LIGHTBLUE01' => 'CARTELA TEC. LIGHT BLUE',
        '057006200AM01' => 'CARTELA PUNHO PIQUET',
        '056008515AM01' => 'CARTELA CUPRO STAMBUL TWILL',
        '05666779DAM01' => 'CARTELA TWEED ALPIS',
        '027110000AM01' => 'CARTELA ALGODAO TRANCADO',
        '0580477SMAM01' => 'CARTELA SARJA NEW MANCHESTER PESADA',
        '056612821AM01' => 'CARTELA CREPE HERRINGBONE SATIN',
        '056200900AM01' => 'CARTELA HELANCA SCHOOL',
        '05605280DAM01' => 'CARTELA TRICOLINE DOBBY SPAN VERMAZZA',
        '05600121DAM01' => 'CARTELA RENDA MADRI DUBLADA',
        '056039600AM02' => 'CARTELA CHIFFON LINEN ECO',
        '0581601MAAM01' => 'CARTELA MALHA ACTIVE',
        '058470341AM01' => 'CARTELA FLANELA LISA SAO JOANENSE',
        '056099200AM01' => 'CARTELA VISCOSE CREPE SANTORINI ECO',
        '0560569D0AM01' => 'CARTELA TROPICAL GALLES',
        '05821STUDAM01' => 'CARTELA STUDIO STRETCH',
        '058CTTY22AM01' => 'CARTELA TESSIFIL YARA ABUD',
        'AMOSTRA' => 'CARTELAS',
        '056652400AM01' => 'CARTELA ALFAIATARIA PRESTON SHINE',
        '0581612MPAM01' => 'CARTELA MALHA PERFORMACE LISA',
        '056059530AM01' => 'CARTELA GALAXY NEON SURF WEAR SPAN',
        '058CTAB22AM01' => 'CARTELA ALPINA BOTTONS',
        '0566602E0AM01' => 'CARTELA BENGALINE JACQUARD',
        '057928SUPAM01' => 'CARTELA SUPLEX 111 MESA DE AMOSTRA ESTAMPADO 1553',
        '0560113F0AM01' => 'CARTELA TRICOLINE FIL A FIL NICE',
        '0580670E0AM01' => 'CARTELA CREPE GEORGETTE GGT DIG PRINT',
        '056059030AM01' => 'CARTELA TRICOLINE VIENA LISA',
        '0560064FTAM01' => 'CARTELA CASIMIRA CASTELO',
        '056600180AM01' => 'CARTELA LINHAO KIR HI TWIST PD',
        '056660500AM01' => 'CARTELA ORGANZA CRISTAL MN',
        '0580462SLAM01' => 'CARTELA SARJA NEW LONDRES PESADA',
        '056059920AM01' => 'CARTELA VELUDO COTELE WASHED SPAN 16 CANAIS',
        '0560517E0AM01' => 'CARTELA SARJA ARMY CAMUFLADO PRINT',
        '0570058CBAM01' => 'CARTELA 1/2 MALHA PENT. VENEZA CABIDE',
        '058100730AM01' => 'CARTELA 1 X M',
        '058101471AM01' => 'CARTELA L 220',
        '058022026AM01' => 'CARTELA  UNISTILL',
        '0566377D0AM01' => 'CARTELA BORDADO LE CHAMBON',
        '058160600AM01' => 'CARTELA MALHA ICE',
        '058CTAW22AM01' => 'CARTELA DOPTEX ACTIVE WEAR',
        '058101330AM01' => 'CARTELA RUTH COR',
        '05601960DAM01' => 'CARTELA COTTON LAISE ROMANCE LIGHT',
        '056200200AM01' => 'CARTELA MALHA SUEDE ANTILOPE',
        '058018000AM01' => 'CARTELA UNIMADRID',
        '056060FT0AM01' => 'CARTELA VELUDO FIO TINTO GENEBRA',
        '05665223DAM01' => 'CARTELA ALFAIATARIA FIO TINTO CAMBRIDGE ECO',
        '057006205AM01' => 'CARTELA MALHA PIQUET',
        '056054512AM01' => 'CARTELA MALHA SAFIRA SPAN',
        '0581635MDAM01' => 'CARTELA MALHA SQUARE DRY',
        '056059912AM01' => 'CARTELA PLUSH ISTAMBUL',
        '058580301AM01' => 'CARTELA RIBANA COTTON',
        '056222001AM01' => 'CARTELA CAMURCA SUED SPAN',
        '058161400AM01' => 'CARTELA MALHA VISCO PLUS',
        '0566580D0AM01' => 'CARTELA ALFAIATARIA BRISTOL',
        '056800290AM01' => 'CARTELA PERCAL TURQUESA STRIPED',
        '056600190AM01' => 'CARTELA LINEN LOOK LUAU',
        '056009991AM01' => 'CARTELA CETIM SPAN PD',
        '0359110PL0AM01' => 'CARTELA TECIDO MADRID PLUS',
        '058CTCV22AM01' => 'CARTELA CORES VERAO',
        '058160800AM01' => 'CARTELA MALHA CANELADA GRAFIC',
        '056052100AM01' => 'CARTELA TRICOLINE DELTA',
        '056654010AM01' => 'CARTELA ALFAIATARIA VENETTA PRINTED',
        '05662126DAM01' => 'CARTELA TULE BORDADO PROENCA',
        '05665223DAM02' => 'CARTELA ALFAIATARIA FIO TINTO CLIFFORD ECO',
        '056624970AM01' => 'CARTELA VEGAS TWILL SPAN PD',
        '058104556AM01' => 'CARTELA VICHY JOB X',
        '056600100AM01' => 'CARTELA VOIL SATEEN MONALISA SPAN LISO',
        '001194422AM01' => 'CARTELA DOBBY SANTISTA 3 BZI',
        '056699200AM01' => 'CARTELA SEDA MISSONI',
        '056099100AM01' => 'CARTELA VISCOSE MARESIAS ECO',
        '058710000AM01' => 'CARTELA FORRO CANELADO',
        '05665091DAM01' => 'CARTELA PELE FOUR SILVER',
        '014000001' => 'CARTELA WORKWEAR',
        '056608200AM01' => 'CARTELA SHANTUNG SPAN PARTY',
        '0566002785AM01' => 'CARTELA LINEN RAYON PARATY FOIL',
        '057005900AM01' => 'CARTELA 1/2 MALHA MILANO PENTEADA',
        '060200300AM01' => 'CARTELA MALHA PIMA TENCEL 30/1',
        '056039670AM01' => 'CARTELA CORTA VENTO ORION WATER PROOF SHINE',
        '056023FT0AM01' => 'CARTELA TRICOLINE WORK CITY CHECK FT',
        '058500191AMO0' => 'CARTELA DENIM GRACE 410',
        '0560562D0AM01' => 'CARTELA TROPICAL SQUARE',
        '0H1816005AM01' => 'CARTELA CRETONE SISA LIGHT',
        '054MAXHI0AM01' => 'CARTELA MAXLINHOL HIGH',
        '056201632AM01' => 'CARTELA DENIM DOHA FLEX',
        '0586013PPAM01' => 'CARTELA PIED POULLE 4X4 (PREMIUM)',
        '0H1001PCAAM01' => 'CARTELA PERCAL AMETISTA PLUS 180 FIOS',
        '056622500AM01' => 'CARTELA ALFAIATARIA BELUTI MESCLA',
        '058CTCR22AM01' => 'CARTELA COSTA RICA',
        '056201700AM01' => 'CARTELA DENIM SANIBEL WIDE',
        '056645100AM01' => 'CARTELA SUMMER VELVET PD',
        '05601860DAM01' => 'CARTELA MALHA MATELASSE 3D',
        '1208000013' => 'CARTELA DUPLA MALHARIA',
        '015101000AM01' => 'CARTELA CRETONE SISA LISTRADO LIGHT',
        '056009998AM01' => 'CARTELA CETIM CHARMOUSE LISO',
        '058044100AM01' => 'CARTELA SARJA LONDRES LEVE',
        '0H1816116AM01' => 'CARTELA CRETONE SISA PLUS',
        '05600154DAM01' => 'CARTELA JACQUARD JAIPUR',
        '056099500AM01' => 'CARTELA VISCOSE CREPE LUNA',
        '056600310AM01' => 'CARTELA RAYON SLUB SUMMER PD',
        '054MAXPLUSAM01' => 'CARTELA MAXLINHOL PLUS',
        '0H1001PC2PAC01' => 'CARTELA PERCAL AMETISTA PLUS 200 FIOS PLUS CABIDE',
        '058225V00AM01' => 'CARTELA ALFAIATARIA PALACE',
        '0567622D0AM01' => 'CARTELA BORDADO ADELY',
        '056009900AM01' => 'CARTELA CETIM SPAN NACRE',
        '03256C0TL5050' => 'CARTELA CRETONE TURMALINA',
        '0580056SGAM01' => 'CARTELA SARJA SG',
        '0580476SMAM01' => 'CARTELA SARJA NEW MANCHESTER LEVE',
        '058225V86AM01' => 'CARTELA TWO WAY STRETCH AIR JET',
        '0565010E0AM01' => 'CARTELA TECIDO LENCOL DREAMS',
        '056000000AM01' => 'CARTELA MICROF CAMISA PREMIUM',
        '058015170AM01' => 'CARTELA  TECHNOPOLO FIT',
        '05600135DAM01' => 'CARTELA RENDA SEVILHA',
        '05806XADPAM01' => 'CARTELA TEXTOLEEN XADREZ',
        '056335100AM01' => 'CARTELA TRICOLINE MAXFIL LISA',
        '056051016AM01' => 'CARTELA TRICOLINE MUMBAI',
        '01FELPA15AM01' => 'CARTELA FELPA',
        '056051011AM01' => 'CARTELA TRICOLINE PARIS SPAN',
        '056382000AM01' => 'CARTELA TRICOLINE BERGAMO TWILL LISO',
        '0581638MDAM01' => 'CARTELA MALHA DIAGONAL MESCLA SPAN',
        '0566443D0AM01' => 'CARTELA TULE BORDADO MONALISA',
        '056670098AM01' => 'CARTELA CREPE MADAME POLY WAY',
        '001194400AM01' => 'CARTELA DOBBY SANTISTA 3 HAL',
        '056672400AM01' => 'CARTELA NUDE SPANDEX',
        '056603111AM01' => 'CARTELA RAYON SLUB SUMMER PRINTED',
        '056051010AM01' => 'CARTELA TRICOLINE BULGATTI',
        '056609429AM01' => 'CARTELA DOUBLE MOSS CREPE',
        '056312310AM01' => 'CARTELA TRICOLINE MELANGE POOL',
        '05600622DAM01' => 'CARTELA SUPER TROPICAL SANTORINI',
        'IT8010100225' => 'CARTELA TECIDO AMOSTRA',
        '0580398000AM0' => 'CARTELA SATTEN TWILL ZARA DIG PRINTED',
        '056039650AM01' => 'CARTELA CORTA VENTO ROAD WATER PROOF',
        '057006302AM01' => 'CARTELA 1/2 MALHA PA 30/1  BANANA MESCLA  ESP',
        '056085150AM01' => 'CARTELA CUPRO BOREAL',
        '057006GOLAM01' => 'CARTELA GOLA PIQUET',
        '056010000AM01' => 'CARTELA BISTRETCH COMPACT',
        '056602100AM01' => 'CARTELA OXFORD GOLD STRETCH',
        '056014690AM01' => 'CARTELA MALHA CHIMPA',
        '056312309AM01' => 'CARTELA TRICOLINE OXFORDINE POOL',
        '003537003' => 'TECIDO CARTELA',
        '056039920AM01' => 'CARTELA SATEEN CHIFON FENDI LISO',
        '0567733D0AM01' => 'CARTELA BORDADO CHERRY',
        '056658200AM01' => 'CARTELA ALFAIATARIA NOVATE',
        '0560988D0AM01' => 'CARTELA VISCOSE NASSAU LUREX',
        '006476' => 'CARTELA BOLY BUCHAS DIVERSAS',
        '0568120D0AM01' => 'CARTELA BORDADO 3D COM PEDRARIA MARLYN',
        '056604FT0AM01' => 'CARTELA RAYON LINEN LUREX',
        '056560000AM01' => 'CARTELA  MICROFIBRA CAMISA PREMIUM',
        '0583701FDAM01' => 'CARTELA FELPA DELICATA FELPUDA',
        '057005910AM01' => 'CARTELA 1/2 MALHA NEW MILANO',
        '0359110H00AM01' => 'CARTELA TECIDO MADRID HIGH',
        '0560526FTAM01' => 'CARTELA FLANELA CHAMONIX',
        '056051197AM01' => 'CARTELA TRICOLINE BERLIN PD',
        '058100830AM01' => 'CARTELA D JUAN',
        '056056310AM01' => 'CARTELA TROPICAL SAVILE SHINE',
        '0560457F0AM01' => 'CARTELA TRICOLINE FIO TINTO GRANITO',
        '056658300AM01' => 'CARTELA ALFAIATARIA POTENZA SPAN',
        '0563390100AM01' => 'CARTELA TRICOLINE FARGO',
        '056035650AM01' => 'CARTELA MALHA SCUBA SUED',
        '3256AMOSTRA01' => 'CARTELA',
        '056331FT0AM01' => 'CARTELA TRICOLINE TOULOUSE JACQUARD',
        '056006313AM01' => 'CARTELA CASIMIRA IMPERIAL',
        '056634860AM01' => 'CARTELA FEMME TOUCH SPAN PD',
        '056009700AM01' => 'CARTELA CETIM CHARMOUSE ESTAMPADO',
        '056059220AM01' => 'CARTELA DENIM GRENELLE PREMIUM FLEX',
        '056090828AM01' => 'CARTELA VISCOSE TWILL BOND',
        '0581630MVAM01' => 'CARTELA MALHA VISCO SUNSET',
        '058251049AM01' => 'CARTELA MORIM TRES MARIAS',
        '0581634MTAM01' => 'CARTELA MALHA VISCO PRIME',
        '056200400AM01' => 'CARTELA FLEECE POLAR',
        '05669101DAM01' => 'CARTELA RENDA NORUEGUESA',
        '056317500AM01' => 'CARTELA SOFT PEACH FLEUR LISO',
        '001195500AM01' => 'CARTELA DOBBY SANTISTA 4 A9I',
        '056600987AM01' => 'CARTELA BARBIE SPAN LISO',
        '056099000AM01' => 'CARTELA VISCOSE ALFA',
        '0225Z640AM001' => 'CARTELA TECIDO TECHNO PEACH TWILL 1,60LG',
        '058104552AM01' => 'CARTELA VICHY JOB L',
        '010002860AM01' => 'CARTELA TRICOSTAR LISO',
        '058500181AMO0' => 'CARTELA DENIM HI-FI PREMIUM',
        '056650300AM01' => 'CARTELA SHERPA TUNIS',
        '056059926AM01' => 'CARTELA PLUSH QUEBEC',
        '058250400AM01' => 'CARTELA LOGAN WHISKY AVERM SAO GERALDO',
        '05665310DAM01' => 'CARTELA ALFAIATARIA MURANO',
        '056604889AM01' => 'CARTELA ARMANI RAYON CREPE PD',
        '060200200AM01' => 'CARTELA MALHA PIMA 40/1 PENTEADA',
        '056371000AM01' => 'CARTELA TRICOLINE SCOZIA WOOL MELANGE',
        '058108590AM01' => 'CARTELA OXFORD JOB',
        '0550005CBAM01' => 'CARTELA MOLETOM CABIDE',
        '05605524DAM01' => 'CARTELA RENDA ESTAMPADA CROIX',
        '0580670000AM0' => 'CARTELA CREPE GGT SOFT DIG PRINTED',
        '0580634000AM0' => 'CARTELA FEMME TOUCH DIG PRINTED',
        '0563510FTAM01' => 'CARTELA TRICOLINE MILANO FIO 70',
        '058250300AM01' => 'CARTELA PERCALE ALVEJADO',
        '056605FT0AM01' => 'CARTELA CREPE RAYON LINEN FT',
        '058100820AM01' => 'CARTELA L 227',
        '056600280AM01' => 'CARTELA LINEN RAYON ALOHA WASHED PD',
        '056598000AM01' => 'CARTELA VELUDO COTELE MALHA BLEND',
        '056301800AM01' => 'CARTELA TRICOLINE TOULOUSE DOBBY',
        '0560010000AM01' => 'CARTELA CITY STRETCH',
        '0560017L0AM01' => 'CARTELA TRICOLINE WORK TOKIO',
        '0225Y790AM001' => 'CARTELA TECIDO TECHNO SKI 1,50LG',
        '0570059CBAM01' => 'CARTELA 1/2 MALHA MILANO PENTEADA CABIDE',
        '058049580AM01' => 'CARTELA SARJA PELETIZADA MAROC',
        '056601400AM01' => 'CARTELA RAYON SATIN ECLAT ECO',
        '001194411AM01' => 'CARTELA DOBBY SANTISTA 3 A9I',
        '058470301AM01' => 'CARTELA FLANELA LISA',
        '0560396CBAM01' => 'CARTELA CHIFFON STRIPES SOHO CABIDE',
        '056600172AM01' => 'CARTELA PRADA POLY TWILL',
        '0550054CBAM01' => 'CARTELA MOLETINHO CABIDE',
        '056612400AM01' => 'CARTELA CREPE MOOD',
        '05631525EAM01' => 'CARTELA TRICOLINE POSITANO ACETINADO',
        '0580461SLAM01' => 'CARTELA SARJA NEW LONDRES LEVE',
        '0560500D0AM01' => 'CARTELA MALHA LAISE BUZIOS',
        '0H3008114AM01' => 'CARTELA FLANELA SARJADA  ESTAMPADA',
        '058CTSJ22AM01' => 'CARTELA SAO JOANENSE',
        '058CTSF22AM01' => 'CARTELA SANTA FE',
        '056610345AM01' => 'CARTELA BENGALINE BERLIM PD',
        '014000000AMO01' => 'CARTELA TEXTIL MN',
        '056020100AM01' => 'CARTELA VOIL POEME DOBBY',
        '056623010AM01' => 'CARTELA ALFAIATARIA CASTLE',
        '05655100DAM01' => 'CARTELA INDIGO SHIRT NEVADA DOBBY',
        '560479TTAM001' => 'CARTELA TRICOLINE TRIBECA STRIP',
        '054001090AM01' => 'CARTELA ALGODAO PLUS',
        '05603999AM01' => 'DESATIVADO',
        '056651300AM01' => 'CARTELA ALFAIATARIA NEW YORK ECO',
        '025PERC00AM01' => 'CARTELA PERCAL BELLA',
        '05605210CRTLA' => 'CARTELA TRICOLINE DELTA',
        '056199600AM01' => 'CARTELA TWO WAY EMOZIONE',
        '056500522AM01' => 'CARTELA DENIM CAMISARIA AVEIRO',
        '058500141AMO0' => 'CARTELA DENIM HI-FI BLUE',
        '05660125DAM01' => 'CARTELA RENDA PARA LINGERIE MAX MARA',
        '0566010D0AM01' => 'CARTELA LINEN COTTON DOBBY',
        '058160400AM01' => 'CARTELA MALHA DOLL LIGHT',
        '0563171100AM01' => 'CARTELA TRICOLINE SLUB GOYA LISA',
        '03256PCC0AM01' => 'CARTELA PERCAL CITRINO BCO',
        '05605600DAM01' => 'CARTELA TROPICAL PERROTS SHINE',
        '056612500AM01' => 'CARTELA CREPE CHANNEL',
        '056800291AM01' => 'CARTELA PERCAL JADE',
        '056069652AM01' => 'CARTELA DENIM YARIS',
        '056987D00AM01' => 'CARTELA VISCOSE NASSAU DOBBY LUREX',
        '056051150AM01' => 'CARTELA FLANELA ASPEN MELANGE',
        '035911M00AM01' => 'CARTELA TECIDO MADRID MEDIUM',
        '056039940AM01' => 'CARTELA SATIN GLOSS TWIST LISO',
        '0565122D0AM01' => 'CARTELA TRICOLINE TOULOUSE HI DOBBY',
        '056988FT0AM01' => 'CARTELA VISCOSE NASSAU FT',
        '056600151AM01' => 'CARTELA PRADA TWILL SPANDEX',
        '0582501PBAM01' => 'CARTELA PERCAL BELLA',
        '1208000003' => 'CARTELA TRIPLA 300GR/M2',
        '0563710FTAM01' => 'CARTELA TRICOLINE SCOZZIA WOOL MELANGE',
        '0560020LXAM01' => 'CARTELA TRICOLINE WORK TOKIO LX504',
        '0566075D0AM01' => 'CARTELA TULE DOBBY FLUID',
        '0563171D0AM01' => 'CARTELA TRICOLINE SLUB GOYA DOBBY',
        '058051000AM01' => 'CARTELA TRICOLINE ROMA PREMIUM',
        '0560203D0AM01' => 'CARTELA VOIL POEME URAGUIRI',
        '056600112AM01' => 'CARTELA OXFORDINE STRETCH',
        '058275082AM01' => 'CARTELA MOLETOM FLEECE PLUS',
        '056670099AM01' => 'CARTELA CREPE GEORGETE SPILBERG LISO',
        '001193400AM01' => 'CARTELA DOBBY SANTISTA 2 A9I',
        '058CTAP22AM01' => 'CARTELA ALPINA CAMISARIA',
        '056025FT0AM01' => 'CARTELA TRICOLINE WORK CITY LAB STRIPES',
        '056051110AM01' => 'CARTELA TRICOLINE PADOVA ACETINADA',
        '035911010AM01' => 'CARTELA TECIDO MADRID 180 FIOS',
        '056200700AM01' => 'CARTELA MALHA SUEDE FLEX',
        '010002850AM01' => 'CARTELA TRICOSTAR ESTAMPADO',
        '03256B146AM01' => 'CARTELA BAGUM',
        '056019920AM01' => 'CARTELA TWO WAY PASSIONI',
        '001193422AM01' => 'CARTELA DOBBY SANTISTA 2 JCI',
        '056201682AM01' => 'CARTELA DENIM TEKNIK',
        '056670200AM01' => 'CARTELA RAYON POPLIN TWILL PD',
        '0582505CSAM01' => 'CARTELA COTTON BARCELONA',
        '0560315D0AM01' => 'CARTELA TRICOLINE CATALUNIA DOBBY',
        '0580485CBAM01' => 'CARTELA SARJA PELETIZADA SEGOVIA  CABIDE',
        '1208000002' => 'CARTELA DUPLA 300GR/M2',
        '01193500AM01' => 'CARTELA DOBBY SANTISTA 2 W0I',
        '14000000AMO01' => 'CARTELA TEXTIL MN',
        '05600200DAM01' => 'CARTELA CASIMIRA LONDON',
        '0582502BRAM01' => 'CARTELA PERCAL BRISA ABERTO',
        '0580513TBAM01' => 'CARTELA TRICOLINE BERGAMO TWILL',
        '05600999LAM01' => 'CARTELA SATEEN DULL SPAN LISO',
        '05605120DAM01' => 'CARTELA TRICOLINE RODIN DOBBY',
        '0560399E0AM01' => 'CARTELA TECNOSPORT ESTAMPADO',
        '058500171AMO0' => 'CARTELA DENIM INTENSITY FLEX',
        '057006301AM01' => 'CARTELA 1/2 MALHA PA 30/1 001 CINZA MESCLA ESP',
        '01PIQUET00AM01' => 'CARTELA PIQUET NOVARA',
        '057006000AM01' => 'CARTELA 1/2 MALHA MESCLA 30/1',
        '0565980D0AM01' => 'CARTELA TECIDO LENCOL MICROFIBRA EMBOSS',
        '056009990AM01' => 'CARTELA BISTRETCH',
        '056097710AM01' => 'CARTELA VISCOSE BALLI',
        '056108600AM01' => 'CARTELA FIORINO JOB',
        '0560988000AM01' => 'CARTELA VISCOSE NASSAU SLUB PD',
        '058CTAN22AM01' => 'CARTELA ANTONIOLLI',
        '0580180UMAM01' => 'CARTELA UNIMADRID',
        '0563194E0AM01' => 'CARTELA DENIM GABBANA SPAN PRINTED',
        '056002200AM01' => 'CARTELA SARJA BLEND NORONHA',
        '0560988CBAM01' => 'CARTELA VISCOSE NASSAU SLUB PD',
        '0563160E0AM01' => 'CARTELA TRICOLINE MEMPHIS',
        '0581603MNAM01' => 'CARTELA MALHA NEW SUPLEX',
        '056009650AM01' => 'CARTELA MOUSSELINE SPAN PD',
        '056019500AM01' => 'CARTELA COTTON LAISE ROMANCE',
        '001194500AM01' => 'CARTELA DOBBY SANTISTA 3 W0I',
        '058CTSG22AM01' => 'CARTELA SAO GERALDO',
        '056201448AM01' => 'CARTELA DENIM VINCE FLEX 101448',
        '056600109AM01' => 'CARTELA RAYON PRINCESS LISO',
        '0583844DNAM01' => 'CARTELA DENIM 6990 PRETO',
        '058101530AM01' => 'CARTELA GALLES',
        '056026FT0AM01' => 'CARTELA TRICOLINE WORK CITY LAB CHECK',
        '05665201DAM01' => 'CARTELA ALFAIATARIA MARSHAL',
        '058CTDA22AM01' => 'CARTELA DOPTEX ACTIVE WEAR',
        '0560514D0AM01' => 'CARTELA TRICOLINE ATLANTA DOBBY',
        '581631MMPAM01' => 'CARTELA MALHA MONTARIA POLY',
        '058CTVC22AM01' => 'CARTELA VALENCA COLLECTION',
        '058010630AM01' => 'CARTELA  CORALLE',
        '058500046AMO0' => 'CARTELA DENIM AVEIRO CAMISARIA',
        '0581635MCAM01' => 'CARTELA MALHA DIAGONAL CONCEPT SPAN',
        '0560391CBAM01' => 'CARTELA PEACH CHIFFON LIQUID CABIDE',
        '060PERK00AM01' => 'CARTELA PERKALEEN PLUS',
        '058047530AM01' => 'CARTELA SARJA MANCHESTER PESADA',
        '057612000AM01' => 'CARTELA CREPE MUNIC ESTAMPADO',
        '0566520FTAM01' => 'CARTELA ALFAIATARIA MARSHAL FT',
        '0560021000AM01' => 'CARTELA OXFORD GOLD STRETCH',
        '56396AT001000' => 'CARTELA TRICOLINE SEER SUCKEER',
        '056014160AM01' => 'CARTELA COURO NORD',
        '0586123000AM0' => 'CARTELA CREPE AYA DIG PRINTED',
        '056600125AM01' => 'CARTELA LINHO PIENZA ECO',
        '056050165AM01' => 'CARTELA MALHA MESH ACTION',
        '058H10955AM01' => 'CARTELA PIQUET VENEZA',
        '056600490AM01' => 'CARTELA TREE SKIN PD',
        '058CTMNP1AM01' => 'CARTELA MN PRO 1º EDICAO',
        '0566089E0AM01' => 'CARTELA FAKE LINE PR',
        '01PIQUETSAM01' => 'CARTELA PIQUET SAVONA',
        '05603401EAM01' => 'CARTELA MALHA LIGATEX',
        '056300000AM01' => 'CARTELA TRICOLINE PUCCI PLUS',
        '056051000AM01' => 'CARTELA TRICOLINE ROMA',
        '056069990AM01' => 'CARTELA VELUDO COTELE 21 CANAIS',
        '0582030DBAM01' => 'CARTELA DENIM BRAGA',
        '058CTVF22AM01' => 'CARTELA VALENCA FIRMUS',
        '058CTCG22AM01' => 'CARTELA CATAGUASES',
        '056059650AM01' => 'CARTELA VELUDO MOLHADO EIFFEL',
        '0580514TFAM01' => 'CARTELA TRICOLINE FERRARA ACETINADA',
        '05601970DAM01' => 'CARTELA COTTON LAISE ROMANCE PLUS',
        '0562007E0AM01' => 'CARTELA MALHA SUEDE FLEX PRINTED',
        '056053000AM01' => 'CARTELA LENCOL DE MICROFIBRA LISO GARDEN',
        '001193411AM01' => 'CARTELA DOBBY SANTISTA 2 BZI',
        '0560518FTAM01' => 'CARTELA TRICOLINE MUSTANG',
        '1208CARTMNPRO' => 'CARTELA MN PRO 2023',
        '056690000AM01' => 'CARTELA RAYON POPLIN ANTUERPIA SPAN',
        '060200400AM01' => 'CARTELA MALHA PIMA 40/1 SPANDEX',
        '058CTDW22AM01' => 'CARTELA DOPTEX WORK WEAR',
        '0580101MCAM01' => 'CARTELA MALHA CRPE RISCA DE GIZ',
        '0560564D0AM01' => 'CARTELA TROPICAL WINDSOR',
        '056053010AM01' => 'CARTELA ALGODAO GREY ARMY',
        '0H5026005AM01' => 'CARTELA PIQUET CHAMONIX',
        '058047520AM01' => 'CARTELA SARJA MANCHESTER LEVE',
        '056201654AM01' => 'CARTELA DENIM ABU DHABI 001',
        '056650859AM01' => 'CARTELA PELE FOUR LISA GOLD',
        '056059930AM01' => 'CARTELA MALHA PONTO ROMA AZZARO',
        '056098900AM01' => 'CARTELA VISCOSE MELANGE DUO',
        '0580495CBAM01' => 'CARTELA SARJA PELETIZADA MAROC  CABIDE'
    ];

	/**
	 * porcentagem_metragem_exata
	 *
	 * @var int
	 */
	private $porcentagem_metragem_exata = 10;

    private $storage = 'public/contratos_pedidos/';

    public function __construct(){

        $estabelecimentos = returnEmpresasNasajonView();
        unset($estabelecimentos[20]);
        $this->estabelecimentos = $estabelecimentos;
        $this->estabelecimentos_prologos = [];
		$this->estabelecimentos_not_pedido_interno = [0, 1, 2, 6];
		$this->estabelecimentos_not_pedido = [0, 1, 2, 6, 7];
        $this->estabelecimentos_producao = [5];

        $this->tipo_vendas = [
            'pronta_entrega_venda' => 'Pronta Entrega',
            'pronta_entrega_triangular' => 'Pronta Entrega - Triangular',
            'pedido_futuro_venda' => 'Pedido Futuro',
            'pedido_futuro_triangular' => 'Pedido Futuro - Triangular',
            'pedido_orgaopublico' => 'Pedido Orgão Publico',
            'pedido_pilotagem' => 'Pedido Pilotagem'
        ];
        $this->tipo_vendas_especial = [
            'producao' => 'Produção',
            'producao_triangular' => 'Produção - Triangular',
            'pre_pago_producao' => 'Pré-Pago - Produção',
            'pre_pago_producao_triangular' => 'Pré-Pago - Produção - Triangular',
            'pre_pago' => 'Pré-Pago - Pronta Entrega',
            'pre_pago_triangular' => 'Pré-Pago - Pronta Entrega - Triangular',
            'pre_pago_futuro' => 'Pré-Pago - Pedido Futuro',
			'rj_x_sp' => 'Operação RJ/SP',
			'rj_x_sp_triangular' => 'Operação RJ/SP - Triangular',
            'pre_pago_rj_x_sp' => 'Pré-Pago - Operação RJ/SP ',
            'rj_x_sp_futuro' => 'Operação RJ/SP - Pedido Futuro',
            'rj_x_sp_triangular_futuro' => 'Operação RJ/SP - Triangular - Pedido Futuro',
            'pre_pago_rj_x_sp_futuro' => 'Pré-Pago - Operação RJ/SP  - Pedido Futuro',
        ];
        $this->tipo_vendas_interno = [
            'remessa_faturamento' => 'Remessa de faturamento antecipado',
        ];
        $this->tipo_vendas_futuro = [
            'pedido_futuro_venda', 'pedido_futuro_triangular', 'pre_pago_futuro', 'producao', 'producao_triangular', 'rj_x_sp_futuro', 'rj_x_sp_triangular_futuro', 'pre_pago_rj_x_sp_futuro', 'pre_pago_producao', 'pre_pago_producao_triangular'
        ];
        $this->tipo_vendas_triangular = [
            'pronta_entrega_triangular', 'pedido_futuro_triangular', 'pre_pago_triangular', 'rj_x_sp_triangular', 'producao_triangular', 'rj_x_sp_triangular_futuro'
        ];
        $this->tipo_venda_pronta_entrega = [
            'pronta_entrega_venda', 'pronta_entrega_triangular', 'pre_pago', 'pre_pago_triangular'
        ];

        $this->tipo_frete = [
            '' => 'Selecione',
            'P' => "PAGO - CIF",
            'A' => "A PAGAR - FOB",
            'C' => "COBRADO - FOB",
            'T' => "TERCEIRO - FOB",
            'S' => "SEM FRETE - FOB"
        ];

        $this->estabelecimentos_pecas = [5, 8];
        $this->quantidade_pecas = 40;

        $this->frete_fob_valor_minimo = 3000;

        $this->quantidade_limite_pilotagem = 5;

        $this->estabelecimentos_venda_presencial_cartao = [3, 4, 5, 8];

		$this->estabelecimentos_venda_observacao = [5, 8];
    }

    public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\PedidoPortal") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\PedidoPortal');

        $statusObj = StatusPedido::where('exibir', 1)->get();

        $status_pedido = [];
        foreach ($statusObj as $value) {
            $status_pedido[$value->id] = $value->status;
        }
        foreach($this->status_nasajon as $index => $value){
            $status_pedido[$index] = $value;
        }
        $status_pedido['todos'] = 'Todos';
        $status_pedido['cancelados'] = 'Cancelados';
        
        $dropdown_usuarios = [];
        $dropdown_gerentes = [];
        $dropdown_diretores = [];
        if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false || strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
            if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::id()))->orderBy('name', 'asc')->get();
            }
            else if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador'){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->orderBy('name', 'asc')->get();
            }
            else if (strtolower(Auth::user()->tipo_usuario->nome) === "supervisor"){
                $users = User::with('tipo_usuario')->select('id', 'name', 'tipo_usuario_id', 'codigo_representante')->whereIn('id', UserController::varreSubordinados(Auth::user()->responsavel))->orderBy('name', 'asc')->get();
            }
            foreach ($users as $user) {
                if(is_null($user->tipo_usuario)){
                    continue;
                }
                if (!is_null($user->codigo_representante)){
                    $dropdown_usuarios[$user->id] = strtoupper($user->name);
                }
                if (strpos(strtolower($user->tipo_usuario->nome), "gerente") !== false){
                    $dropdown_gerentes[$user->id] = strtoupper($user->name);
                }
                else if (strpos(strtolower($user->tipo_usuario->nome), "diretor") !== false){
                    $dropdown_diretores[$user->id] = strtoupper($user->name);
                }
            }

        }
        $estabelecimentos = $this->estabelecimentos;
		if(!in_array(Auth::user()->tipo_usuario_id, [12, 16])){
			foreach($this->estabelecimentos_not_pedido_interno as $estabelecimento){
				unset($estabelecimentos[$estabelecimento]);
			}
		}else{
			foreach($this->estabelecimentos_not_pedido as $estabelecimento){
				unset($estabelecimentos[$estabelecimento]);
			}
		}

        return view('programs.pedido_portal.index')->with(['dropdown_usuarios' => $dropdown_usuarios, 'status_pedido' => $status_pedido,  'dropdown_gerentes' => $dropdown_gerentes, 'dropdown_diretores' => $dropdown_diretores, 'estabelecimentos' => $estabelecimentos]);
    }

    public function formAdd(Request $request){
        $estabelecimentos = $this->estabelecimentos;
        $estabelecimentos = array_merge_recursive(['' => "Selecione um estabelecimento"], $estabelecimentos);
		if(!in_array(Auth::user()->tipo_usuario_id, [12, 16])){
			foreach($this->estabelecimentos_not_pedido_interno as $estabelecimento){
				unset($estabelecimentos[$estabelecimento]);
			}
		}else{
			foreach($this->estabelecimentos_not_pedido as $estabelecimento){
				unset($estabelecimentos[$estabelecimento]);
			}
		}
		
        if(Auth::user()->tipo_usuario_id == 12){
            unset($estabelecimentos[6]);
        }

        if(
            !in_array(Auth::id(), [45, 91])
        ){
            unset($estabelecimentos[1]);
            unset($estabelecimentos[2]);
        }
        
        $tipo_venda_lista = [
            '' => 'Selecione um tipo de venda'
        ];
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas);
        if(Auth::user()->tipo_usuario_id != '12'){
            $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_interno);
        }

        $condicao_pagamento_balcao = ['' => strtoupper('CondiÇÃo de pagamento')];
        $CondicoesPagamentoWebObj = CondicoesPagamentoWeb::select('id', 'descricao')->orderby('descricao');
        $CondicoesPagamentoWebObj->where('ativo', true);
        $CondicoesPagamentoWebObj->where('nasajon', true);
        $CondicoesPagamentoWebObj->where(function($query){
            $query->orWhere('descricao', 'ilike', 'cartao%');
            $query->orWhere('descricao', 'ilike', 'dinheiro');
            $query->orWhere('descricao', 'ilike', 'mercado pago');
        });
        foreach($CondicoesPagamentoWebObj->get() as $condicao_pagamento){
            $condicao_pagamento_balcao[$condicao_pagamento->id] = strtoupper($condicao_pagamento->descricao);
        }
		unset($CondicoesPagamentoWebObj);
		
        $condicao_pagamento_blacklist = ['' => strtoupper('CondiÇÃo de pagamento')];
        $CondicoesPagamentoWebObj = CondicoesPagamentoWeb::select('id', 'descricao')->orderby('descricao');
        $CondicoesPagamentoWebObj->where('ativo', true);
        $CondicoesPagamentoWebObj->where('nasajon', true);
        $CondicoesPagamentoWebObj->where(function($query){
            $query->orWhere('descricao', 'ilike', 'cartao%');
            $query->orWhere('descricao', 'ilike', 'dinheiro');
            $query->orWhere('descricao', 'ilike', 'usar credito');
        });
        foreach($CondicoesPagamentoWebObj->get() as $condicao_pagamento){
            $condicao_pagamento_blacklist[$condicao_pagamento->id] = strtoupper($condicao_pagamento->descricao);
        }
        unset($CondicoesPagamentoWebObj);

        $pedido = [
            'lista_estabelecimentos' => $estabelecimentos,
            'tipo_venda_lista' => $tipo_venda_lista,
            'id' => '',
            'id_cliente_encriptada' => '',
            'motivo_rejeicao' => '',
            'estabelecimento' => '',
            'cliente_codigo' => '',
            'cliente_descricao' => '',
            'cliente_conta_e_ordem_codigo' => '',
            'cliente_conta_e_ordem_descricao' => '',
            'tipo_venda' => '',
            'pedido_futuro' => '',
            'data_previsao_entrega' => date('d/m/Y'),
            'transportadora_codigo' => '',
            'transportadora_descricao' => '',
            'transportadora_frete' => '',
            'tipo_frete' => '',
            'valor_frete' => '',
            'transportadora_redespacho_codigo' => '',
            'transportadora_redespacho_descricao' => '',
            'transportadora_redespacho_frete' => '',
            'tipo_frete_redespacho' => '',
            'valor_frete_redespacho' => '',
            'condicao_pagamento_codigo' => '',
            'condicao_pagamento_descricao' => '',
            'nome_comprador' => '',
            'email_comprador' => '',
            'no_pedido_compra' => '',
            'observacao' => '',
            'message_limite' => '',
            'media_condicao_pagamento' => '',
            'estado_destino' => '',
            'cif_fob' => '',
            'preco_cif_fob' => '',
            'aliquota_usada' => '',
            'itens' => [],
            'total_itens' => '',
            'valor_total_produtos' => '',
            'valor_total_frete' => '',
            'valor_desconto' => '',
            'valor_total_pedido' => '',
            'peso_total' => '',
            "condicao_especial" => [],
            "cliente_balcao" => false,
            "producao" => false,
            'condicao_pagamento_balcao' => $condicao_pagamento_balcao,
            'condicao_pagamento_blacklist' => $condicao_pagamento_blacklist,
            'cartao' => false,
            'presencial' => false,
            'transferencia' => false,
            'blacklist' => false,

            'enfestar' => false,
            'bater_amostra' => false,
            'incluir_cartelas' => false,
            'metragem_exata' => false,
            'transportadora_retira' => false,
            'transportadora_retira_horario' => '',
            'transportadora_retira_imediato' => false,
			'cliente_sem_telefone' => false,
			'cliente_telefone' => '',
			'rj_x_sp' => false
        ];

        $tipo_frete = [
            '' => 'Selecione',
            'P' => "PAGO - CIF",
            'A' => "A PAGAR - FOB",
            'C' => "COBRADO - FOB",
            'T' => "TERCEIRO - FOB",
            'S' => "SEM FRETE - FOB"
        ];
        
        $dados_analise = [
            "pedidos" => [
                "orcamentos"  => "",
                "carteira"  => "",
                "total"     => ""
            ],
            "notas_debito" => [
                "a_vencer" 	=> "",
                "vencidas" 	=> "",
                "total"		=> ""
            ],
            "notas_credito" => "",
            "titulos_faturados" =>[
                "a_vencer" 	=> "",
                "vencidas" 	=> "",
                "total"		=> ""
            ],
            "cheques_a_receber" =>[
                "a_vencer" 	=> "",
                "vencidas" 	=> "",
                "total"		=> ""
            ],
            "atraso" => [
                "ultima" => [
                    "data"         => "",
                    "quantidade"   => ""
                ],
                "maior" =>  [
                    "data"         => "",
                    "quantidade"   => ""
                ]
            ],
            "vendas" => [
                "ultima" => [
                    "data"     => "",
                    "valor"    => ""
                ],
                "maior" =>  [
                    "data"     => "",
                    "valor"    => ""
                ]
            ],
            "total" => [
                "a_vencer" 	=> "",
                "vencidas" 	=> "",
                "total"		=> ""
            ],
            "pago_ultimo_12_meses" => '',
            "cliente_desde" => '',
            "limite_credito" => '',
            "mensagem_alerta" => '',
            "messagem_agrupada" => '',
            "messagem_agrupada_cnpjs" => '',
            'cliente' => [
                'codigo' => '',
                'nome' => '',
                'unico' => ''
            ],
            "cnpj_array" => '',

            "titulos_pagos" => '',

            "titulos" => '',
            "valor_total" => '',
            'em_atraso' => '',
            "dias_total_atraso" => '',
            "media_atraso" => '',
            'vencimento_credito' => '',
            "informacoes_cliente_link" => '',

            "titulo_modal" => '',

            "consulta_serasa" => '',
			"motivo_reavaliacao" => '',
			
			'blacklist' => ''

        ];

        $condicoes_especiais = [
            'producao' => 'Produção',
            'producao_triangular' => 'Produção - Triangular',
            'pre_pago_producao' => 'Pré-Pago - Produção',
            'pre_pago_producao_triangular' => 'Pré-Pago - Produção - Triangular',
        ];

        $data_previsao_entrega_ultima = '';
        $data_previsao_entrega_original = '';
        if (!empty($request->id)){
            $pedidoObj = PedidoPortal::findOrFail($request->id);
            if(Auth::user()->tipo_usuario_id == 12 && $pedidoObj->estabelecimento == 6){
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                        'error' => [
                            'msg' => 
                            [
                                'user' => 'Não é permitido pedido de venda para este estabelecimento'
                            ],
                        ],
                            'response' => []
                        ], 
                    422);
                exit();
            }

            if(in_array($pedidoObj->tipo_venda, $this->tipo_pronta_entrega)){
                $data_previsao_entrega_ultima = $pedidoObj->data_previsao_entrega_ultima;
            }

            $data_previsao_entrega_original = $pedidoObj->data_previsao_entrega_original;
            
            $retorno = $this->processaDadosDigitacaoNasajon($pedidoObj);

            if($pedidoObj->pedido_futuro == false && in_array($pedidoObj->tipo_venda , ['pedido_futuro_venda', 'pedido_futuro_triangular'])){
                $pedido['tipo_venda_lista']['pedido_futuro_venda'] = 'Pronta Entrega';
                $pedido['tipo_venda_lista']['pedido_futuro_triangular'] = 'Pronta Entrega - Triangular';
            }

            if(isset($retorno['erro'])){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [
                        'msg' => 
                        [
                            'user' => $retorno['erro']
                        ],
                    ],
                        'response' => []
                    ], 
                422);
            }
            $pedido['status_pedido_id'] = $pedidoObj->status_pedido;
            $pedido['revisar_mensagem'] = $pedidoObj->revisar_mensagem;
            $pedido = array_merge($pedido, $retorno['pedido']);
            $dados_analise = array_merge($dados_analise, $retorno['dados_analise']);

            unset($retorno);

        }
        else {
            if(PedidoPortal::where('status_pedido', 1)->where('usuario', Auth::id())->exists()){
                $pedido['mensagem'] = 'Já há um ou mais pedidos sendo digitados. Favor verificar.';
            }
        }

        $dados = [
            'pedido' => $pedido,
            'tipo_frete' => $tipo_frete,
            'dados' => $dados_analise,
            'condicoes_especiais' => $condicoes_especiais,
            'tipo_pronta_entrega' => $this->tipo_venda_pronta_entrega,
            'tipo_vendas_futuro' => $this->tipo_vendas_futuro,
            'estabelecientos_pecas' => $this->estabelecimentos_pecas,
            'quantidade_ver_pecas' => $this->quantidade_pecas,
            'data_previsao_entrega_ultima' => $data_previsao_entrega_ultima,
            'data_previsao_entrega_original' => $data_previsao_entrega_original,
        ];
        
        $carrinho = $request->only('carrinho');


        if(!empty($carrinho)){
            return $dados;
        }else{
            return view('programs.pedido_portal.pedido')->with($dados);
        }
    }

    private function processaDadosDigitacaoNasajon(PedidoPortal $pedidoObj){
        $pedido = [];
        $dados_analise = [];

        $pedido['id'] = $pedidoObj->id;
        $pedido['motivo_rejeicao'] = (!empty($pedidoObj->motivo_rejeicao)) ? $pedidoObj->motivo_rejeicao : '';

        $pedido['estabelecimento'] = $pedidoObj->estabelecimento;

        if(
            !array_key_exists($pedidoObj->tipo_venda, $this->tipo_vendas) &&
            !array_key_exists($pedidoObj->tipo_venda, $this->tipo_vendas_especial) &&
            !array_key_exists($pedidoObj->tipo_venda, $this->tipo_vendas_interno)
        ){
            $tipo = 'pronta_entrega';
            if($pedidoObj->pedido_futuro == true){
                $tipo = 'pedido_futuro';
            }
            $pedidoObj->tipo_venda = $tipo . '_' . $pedidoObj->tipo_venda;
        }

        $pedido['tipo_venda'] = $pedidoObj->tipo_venda;

        $pedido['pedido_futuro'] = empty($pedidoObj->pedido_futuro) ? '' : $pedidoObj->pedido_futuro;
        $pedido['data_previsao_entrega'] = empty($pedidoObj->data_previsao_entrega) ? '' : date('d/m/Y', strtotime($pedidoObj->data_previsao_entrega));
        
        if(!is_null($pedidoObj->transportadora)){
            $transportadoraObj = TransportadorNasajon::with(['transportadoraEstabelecimento' => function ($query) use($pedidoObj){
                $query->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, 0, STR_PAD_LEFT));
                if(empty($pedidoObj->cod_cliente_conta_e_ordem)){
                    $query->where('uf_destino', $pedidoObj->cliente->uf);
                }else{
                    $query->where('uf_destino', $pedidoObj->cliente_conta_e_ordem->uf);
                }                
            }])->where('codigo', $pedidoObj->transportadora)->first();

            if(!is_null($transportadoraObj)){
                $pedido['transportadora_codigo'] = $transportadoraObj->codigo;
                $pedido['transportadora_descricao'] = str_replace(' - __.___.___/____-__', '', trim($transportadoraObj->nome) . ' - ' . trim($transportadoraObj->cnpj));
                $pedido['transportadora_frete'] = empty($transportadoraObj->transportadoraEstabelecimento)? 'FOB' : $transportadoraObj->transportadoraEstabelecimento->tipo_frete;
            }
            unset($transportadoraObj);
        }

        $pedido['tipo_frete'] = $pedidoObj->tipo_frete;
        $pedido['valor_frete'] = !empty($pedidoObj->valor_frete) ? parserValor($pedidoObj->valor_frete) : '';
        
        if(!is_null($pedidoObj->transportadora_redespacho)){
            $transportadoraObj = TransportadorNasajon::with(['transportadoraEstabelecimento' => function ($query) use($pedidoObj){
                $query->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, 0, STR_PAD_LEFT));
                if(empty($pedidoObj->cod_cliente_conta_e_ordem)){
                    $query->where('uf_destino', $pedidoObj->cliente->uf);
                }else{
                    $query->where('uf_destino', $pedidoObj->cliente_conta_e_ordem->uf);
                } 
            }])->where('codigo', $pedidoObj->transportadora_redespacho)->first();

            if(!is_null($transportadoraObj)){
                $pedido['transportadora_redespacho_codigo'] = $transportadoraObj->codigo;
                $pedido['transportadora_redespacho_descricao'] = str_replace(' - __.___.___/____-__', '', trim($transportadoraObj->nome) . ' - ' . trim($transportadoraObj->cnpj));
                $pedido['transportadora_redespacho_frete'] = empty($transportadoraObj->transportadoraEstabelecimento)? 'FOB' : $transportadoraObj->transportadoraEstabelecimento->tipo_frete;
            }
            unset($transportadoraObj);
        }
        $pedido['tipo_frete_redespacho'] = $pedidoObj->tipo_frete_redespacho;
        $pedido['valor_frete_redespacho'] = !empty($pedidoObj->valor_frete_redespacho) ? parserValor($pedidoObj->valor_frete_redespacho) : '';
        
        if(!is_null($pedidoObj->cod_cliente_conta_e_ordem)){
            $ClienteContaEOrdem = ClienteNasajon::where('codigo', $pedidoObj->cod_cliente_conta_e_ordem)->where('bloqueado', 'false')->first();
            if(!is_null($ClienteContaEOrdem)){
                $pedido['cliente_conta_e_ordem_codigo'] = $ClienteContaEOrdem->codigo;
                $pedido['cliente_conta_e_ordem_descricao'] = trim($ClienteContaEOrdem->nome) . " - " . $ClienteContaEOrdem->cpf_cnpj;
                unset($ClienteContaEOrdem);
            }else{
                $pedido['cliente_conta_e_ordem_codigo'] = '';
                $pedido['cliente_conta_e_ordem_descricao'] = '';
            }
        }
        
        $CondicoesPagamentoWeb = CondicoesPagamentoWeb::where('id', $pedidoObj->condicao_pagamento)->where('ativo', true)->first();
        $pedido['condicao_pagamento_codigo'] = is_null($CondicoesPagamentoWeb) ? '' : $CondicoesPagamentoWeb->id;
        $pedido['condicao_pagamento_descricao'] = is_null($CondicoesPagamentoWeb) ? '' : $CondicoesPagamentoWeb->descricao;

        $pedido['nome_comprador'] = $pedidoObj->nome_comprador;
        $pedido['email_comprador'] = $pedidoObj->email_comprador;
        $pedido['no_pedido_compra'] = $pedidoObj->no_pedido_compra;
        $pedido['observacao'] = $pedidoObj->observacao;

        $Cliente = ClienteNasajon::with(['blacklist'])->where('codigo', $pedidoObj->cod_cliente)->where('bloqueado', false)->first();
        $pedido['cliente_codigo'] = $Cliente->codigo;
        $pedido['cliente_descricao'] = trim($Cliente->nome) . " - " . $Cliente->cpf_cnpj;
        $pedido["message_limite"] = "";

        $transferencia = false;

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $Cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        if(
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $transferencia = true;
        }
        $pedido['transferencia'] = $transferencia;

        $pedido["limite_credito"] = "";
        $pedido["data_valida_limite"] = "";
        $pedido["limite_credito_disponivel"] = "";
        $pedido["pedido_pronta_entrega"] = ["valor" => "", "quantidade" => ""];
        $pedido["pedidos_futuros"] = ["valor" => "", "quantidade" => ""];
        $pedido["titulos_em_aberto"] = ["a_vencer" => "", "vencidas" => "", "total" => ""];
        $pedido["notas_credito"] = "";
        $pedido["notas_debito"] = ["a_vencer" => "", "vencidas" => "", "total" => ""];
        $pedido["alerta"] = '';

        if(!empty($pedido['cliente_codigo']) && !in_array($pedido['cliente_codigo'], $this->codigo_cliente_balcao)){
            $pedido["id_cliente_encriptada"] = encrypt($pedido['cliente_codigo']);
        }else{
            $pedido["id_cliente_encriptada"] = '';
        }

        $pedido['media_condicao_pagamento'] = is_null($CondicoesPagamentoWeb) ? '' : $CondicoesPagamentoWeb->media;

        $pedido['estado_destino'] = is_null($pedidoObj->cliente) ? '' :  $pedidoObj->cliente->uf;

		$pedido['enfestar'] = $pedidoObj->enfestar;
		$pedido['bater_amostra'] = $pedidoObj->bater_amostra;
		$pedido['incluir_cartelas'] = $pedidoObj->incluir_cartelas;
		$pedido['metragem_exata'] = $pedidoObj->metragem_exata;
		$pedido['transportadora_retira'] = $pedidoObj->transportadora_retira;
		$pedido['transportadora_retira_horario'] = $pedidoObj->transportadora_retira_horario;
		$pedido['transportadora_retira_imediato'] = $pedidoObj->transportadora_retira_imediato == true ? 'true' : 'false';
		$pedido['cliente_sem_telefone'] = $pedidoObj->cliente_sem_telefone == true ? 'true' : 'false';
		$pedido['cliente_telefone'] = $pedidoObj->cliente_telefone;

        $frete = '';
        $preco_cif_fob = '';

        $preco_frete = $this->fretePreco($pedidoObj);
        $preco_cif_fob = $preco_frete['preco'];
        $frete = $preco_frete['frete'];
        
        $pedido['cif_fob'] = $frete;
        $pedido['preco_cif_fob'] = $preco_cif_fob;

        if(!empty($pedidoObj->cod_cliente)){
            switch ($pedidoObj->estabelecimento) {
                case '3':
                    $origem = 'RO';
                    break;
                case '4':
                    $origem = 'TO';
                    break;            
                default:
                    $origem = 'SP';
                    break;
            }

            if(is_null($pedidoObj->cliente)){
                return [
                    'erro' => 'Cliente não encontrado no Nasajon'
                ];
            }

            if(empty($pedidoObj->cliente->uf)){
                return [
                    'erro' => 'Cliente sem UF no cadastro'
                ];
            }

            $aliquotas = AliquotaPreco::where('origem', $origem)
                ->where('estado', $pedidoObj->cliente->estado_detalhe->uf)
                ->where('internacional', 'false')
                ->first();

            $pedido['aliquota_usada'] = $aliquotas->icms_venda." %";
        }else{
            $pedido['aliquota_usada'] = "";
        }

        $pedido['itens'] = [];

		$pedido['peso_total'] = 0;

        $itens = $pedidoObj->itens_pedido;
        $total_valor_item_ipi = 0;
        foreach ($itens as $key => $item) {
            $produto = $item->info_produtoNasjon;
            if(empty($produto)){
                $produto = $item->infoProdutoUsoConsumo;
            }
            $promocao = '';
            if($item->preco_promocao === true){
                $promocao = ' <div><div class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço promocinal" data-content="">*</div></div> ';
            }

            $ipi = '';
            if($item->tem_ipi === true){
                $ipi = ' <span class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço com IPI" data-content="">*</span>';
            }
            if(in_array($item->cod_produto, $this->codigo_uso_consumo)){
                $descricao = $this->codigo_uso_consumo_descricao[$item->cod_produto];
            }else{
                $descricao = $produto->especificacao;
            }
            
            if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao_triangular', 'pre_pago_producao_triangular'])){
                $desenho = '<b>Desenho: </b> ' . $item->desenho->codigo_produto. ' - ' . $item->desenho->descricao;
                $base = '<b>Tecido Base: </b> ' . $item->tecidosBase->codigo_produto. ' - ' . $item->tecidosBase->descricao;
                $descricao = '<div><div class="producao" data-toggle="popover" data-placement="left" data-html="true" title="Produção" data-content="'.$base.'<br>'.$desenho.'">' . $descricao . '</div></div>';
            }

            $pedido['itens'][$key]['campanha_informativo'] = '';

            if(isset($item->campanha)){
                if($pedidoObj->usuario_detalhes->tipo_usuario_id == 12){
                    if($item->tipo_comissao_campanha == 1){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.$item['comissao_original'].'% + '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                    if($item->tipo_comissao_campanha == 2){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                }
                if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [16, 13])){
                    if($item->tipo_comissao_campanha == 1){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.$item['comissao_original'].'% + '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                    if($item->tipo_comissao_campanha == 2){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                }
                if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [14, 19])){
                    if($item->tipo_comissao_campanha == 1){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.$item['comissao_original'].'% + '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                    if($item->tipo_comissao_campanha == 2){
                        $pedido['itens'][$key]['campanha_informativo'] = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                    }
                }
            }
            
            $pedido['itens'][$key]['id'] = $item->id;
            $pedido['itens'][$key]['codigo'] = in_array($item->cod_produto, $this->codigo_uso_consumo)? $item->cod_produto : $produto->codigo;
            $pedido['itens'][$key]['descricao'] = $descricao;
            $pedido['itens'][$key]['descricao_pecas'] = empty($item->especificacoes->produtoGrupo->pecas)? $descricao : $descricao.' - Pecas: '.$item->especificacoes->produtoGrupo->pecas;
            $pedido['itens'][$key]['quantidade'] = "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($item->quantidade) . "</div></div>";
            $preco_unitario_item = $pedidoObj->estabelecimento === 3 && $item->tem_ipi? parserValor(($item->preco_unitario * ( 1 + ($item->ipi_produto/100)))) : parserValor($item->preco_unitario);
            $pedido['itens'][$key]['preco_unitario'] = "<div><div class='preco' data-preco_original='".parserValor($item->preco_original)."' data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" .  $preco_unitario_item . $ipi . "</div></div>";
            $pedido['itens'][$key]['valor_total'] = $pedidoObj->estabelecimento === 3 && $item->tem_ipi? parserValor(($item->preco_unitario * ( 1 + ($item->ipi_produto/100))) * $item->quantidade) : parserValor($item->quantidade*$item->preco_unitario);
            $pedido['itens'][$key]['coluna'] = $item->coluna;
            $pedido['itens'][$key]['comissao'] = parserValor($item['comissao']).'% ';
            $pedido['itens'][$key]['peso_total'] = parserValor($item->quantidade * 0) . " kg";
            $pedido['peso_total'] += $item->quantidade * 0;
            $pedido['itens'][$key]['token_promocional']  = $item->token_promocional;
            $pedido['itens'][$key]['grupo_id']  = $item->especificacoes->produtoGrupo->id;
            $pedido['itens'][$key]['grupo_descricao']  = $item->especificacoes->produtoGrupo->descricao;
            $pedido['itens'][$key]['campanha_nome']  = isset($item->campanha) ? $item->campanha->nome : '';
            $total_valor_item_ipi += $pedidoObj->estabelecimento === 3 && $item->tem_ipi? ($item->preco_unitario * ( 1 + ($item->ipi_produto/100))) * $item->quantidade : $item->quantidade*$item->preco_unitario; 
        }

        $pedido['peso_total'] = parserValor($pedido['peso_total']) . " kg";
        $pedido['total_itens'] = $pedidoObj->itens_pedido->count();
        $pedido['valor_total_produtos'] = !empty($pedidoObj->valor_total->total) ? parserValor($pedidoObj->valor_total->total) : '';
        $pedido['valor_total_frete'] = !empty($pedidoObj->valor_total_frete_produto) && !empty($pedidoObj->valor_total_frete_produto->total)  ? parserValor($pedidoObj->valor_total_frete_produto->total) : '';
        $pedido['valor_desconto'] = !empty($pedidoObj->valor_desconto) ? parserValor($pedidoObj->valor_desconto) : '';
        $pedido['valor_total_pedido'] = !empty($pedidoObj->valor_total) ? parserValor($pedidoObj->valor_total->total + ($pedidoObj->tipo_frete == 'C'?$pedidoObj->valor_frete:0) - $pedidoObj->valor_desconto) : 0;

        if($pedidoObj->estabelecimento === 3){
            $pedido['valor_total_pedido'] = empty($total_valor_item_ipi)? '' : parserValor($total_valor_item_ipi);
            $pedido['valor_total_produtos'] = empty($total_valor_item_ipi)? '' : parserValor($total_valor_item_ipi);
        }
        
        $dados_analise = [];

        $condicao_especial = [];
        if(!empty($Cliente)){
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $Cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $condicao_especial['pre_pago'] = 'Pré-Pago - Pronta Entrega';
                $condicao_especial['pre_pago_triangular'] = 'Pré-Pago - Pronta Entrega - Triangular';
                $condicao_especial['pre_pago_futuro'] = 'Pré-Pago - Pedido Futuro';
			}
			$pedido['blacklist'] = $this->blacklistPedido($Cliente);
        }

        if(in_array(intval($pedidoObj->estabelecimento), $this->estabelecimentos_producao)){
            $condicao_especial['producao'] = 'Produção';
            $condicao_especial['producao_triangular'] = 'Produção - Triangular';
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $Cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $condicao_especial['pre_pago_producao'] = 'Pré-Pago - Produção';
                $condicao_especial['pre_pago_producao_triangular'] = 'Pré-Pago - Produção - Triangular';
			}
        }
		if(
			in_array(intval($pedidoObj->estabelecimento), [3, 4]) &&
			in_array($Cliente->uf, ['RJ', 'CE', 'PE'])
		){
			$condicao_especial['rj_x_sp'] = 'Operação RJ/SP';
			$condicao_especial['rj_x_sp_triangular'] = 'Operação RJ/SP - Triangular';
            $condicao_especial['rj_x_sp_futuro'] = 'Operação RJ/SP - Pedido Futuro';
            $condicao_especial['rj_x_sp_triangular_futuro'] = 'Operação RJ/SP - Triangular - Pedido Futuro';
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $Cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $condicao_especial['pre_pago_rj_x_sp'] = 'Pré-Pago - Operação RJ/SP  ';
                $condicao_especial['pre_pago_rj_x_sp_futuro'] = 'Pré-Pago - Operação RJ/SP  - Pedido Futuro';
            }
			
		}
        
        $pedido['condicao_especial'] = $condicao_especial;

        $producao = false;
        if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $producao = true;
        }
        $pedido['producao'] = $producao;

        $cliente_balcao = false;
        if(in_array($pedidoObj->cod_cliente, $this->codigo_cliente_balcao)){
            $cliente_balcao = true;
        }
        $pedido['cliente_balcao'] = $cliente_balcao;
        
        $cliente_pedido = 'Contribuinte';
        if (
            $Cliente->inscricaoestadual == 'ISENTO' ||
            intval($Cliente->indicadorinscricaoestadual) == 2 ||
            intval($Cliente->indicadorinscricaoestadual) == 9
        ){
            $cliente_pedido = 'Isento';
        }
        $ClienteBionexoObj = ClienteBionexo::where('cpf_cnpj', $Cliente->cpf_cnpj)->exists();
        if($ClienteBionexoObj == true){
            $cliente_pedido .= ' - Bionexo';
        }
        $pedido['cliente_pedido'] = $cliente_pedido;

		$pedido['rj_x_sp'] = $pedidoObj->rj_x_sp;
   
        return ["pedido" => $pedido, "dados_analise" => $dados_analise];
    }
    
    public function salvar(PedidoSalvarRequest $request){
        $fields = $request->all();
        return $this->salvarNasajon($fields);
    }

    public function salvarNasajon($fields){
        $vefica_presencial = false;
        $cartao = false;
        $pedido_novo = false;
        $recalcular_itens = false;
        $ClienteObj = ClienteNasajon::where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($fields['nome_cliente']))->where('bloqueado', 'false')->first();
		if(!empty($fields['id'])){
            $PedidoPortalObj = PedidoPortal::find($fields['id']);
		}else{
            $pedido_novo = true;
			$PedidoPortalObj = new PedidoPortal();
            $PedidoPortalObj->created_by = Auth::user()->id;
            if(!empty(Auth::user()->codigo_representante)){
                $PedidoPortalObj->usuario = Auth::user()->id;
            }else{
                if(in_array($fields['codigo_cliente'], $this->codigo_grupo_textil_mn)){
                    $PedidoPortalObj->usuario = 1;
                }
                else{
                    $user = User::where("codigo_representante", $ClienteObj->vendedor_codigo)->first();
                    if(!is_null($user)){
                        $PedidoPortalObj->usuario = $user->id;
                    }else{
                        $user = User::where("codigo_representante", '999')->first();
                        $PedidoPortalObj->usuario = $user->id;
                    }
                }
            }
            $vefica_presencial = true;
        }

        $transferencia = false;

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $ClienteObj->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        if(
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $transferencia = true;
        }

        $PedidoPortalObj->data_pedido = date('Y-m-d');
		$data_previsao_entrega = !empty($fields['data_previsao_entrega']) ? Carbon::createFromFormat('d/m/Y', $fields['data_previsao_entrega']) : null;

        $errors = [];
        $natop = '';

        if ($fields['transportadora_tipo_frete'] != 'C'){
            $fields['valor_frete'] = 0;
        }

        if ($fields['transportadora_redespacho_tipo_frete'] != 'C'){
            $fields['valor_frete_redespacho'] = 0;
        }

        $fields['valor_frete'] = floatval(str_replace(',', '.', str_replace('.', '', $fields['valor_frete'])));
        $fields['valor_frete_redespacho'] = floatval(str_replace(',', '.', str_replace('.', '', $fields['valor_frete_redespacho'])));

        if(!in_array($fields['tipo_venda'], $this->tipo_vendas_triangular)){
            $fields['codigo_cliente_conta_e_ordem'] = '';
        }
        $pedido_futuro = false;
        if(in_array($fields['tipo_venda'], $this->tipo_vendas_futuro)){
            if(in_array($PedidoPortalObj->tipo_venda , ['pedido_futuro_venda', 'pedido_futuro_triangular']) && $PedidoPortalObj->pedido_futuro == false){
                $pedido_futuro = false;
            }else{
                $pedido_futuro = true;
            }
        }
        if(in_array($fields['tipo_venda'], ['pedido_pilotagem'])){
            $fields['condicao_pagamento'] = null;
        }
        
        if($fields['condicao_pagamento'] != $PedidoPortalObj->condicao_pagamento){
            $vefica_presencial = true;            
        }
		if($PedidoPortalObj->status_pedido == 2){
			$PedidoPortalObj->aprovacao->delete();
		}

        if($PedidoPortalObj->tipo_frete_redespacho != $fields['transportadora_redespacho_tipo_frete']){
            $recalcular_itens = true;
        }

		$PedidoPortalObj->updated_by = Auth::id();
		$PedidoPortalObj->status_pedido = 1;
		$PedidoPortalObj->tipo_venda = $fields['tipo_venda'];
        $PedidoPortalObj->cod_cliente = $ClienteObj->codigo;
		$PedidoPortalObj->nome_comprador = $fields['nome_contato'];
        $PedidoPortalObj->email_comprador = $fields['email_contato'];
		$PedidoPortalObj->no_pedido_compra = $fields['no_pedido_compra'];
		$PedidoPortalObj->estabelecimento = $fields['estabelecimento'];
		$PedidoPortalObj->pedido_futuro = $pedido_futuro;
		$PedidoPortalObj->condicao_pagamento = $fields['condicao_pagamento'];
		$PedidoPortalObj->data_previsao_entrega = $data_previsao_entrega;
		$PedidoPortalObj->transportadora = $fields['transportadora'];
		$PedidoPortalObj->tipo_frete = $fields['transportadora_tipo_frete'];
		$PedidoPortalObj->valor_frete = $fields['valor_frete'];
		$PedidoPortalObj->transportadora_redespacho = $fields['transportadora_redespacho'];
		$PedidoPortalObj->tipo_frete_redespacho = $fields['transportadora_redespacho_tipo_frete'];
		$PedidoPortalObj->valor_frete_redespacho = $fields['valor_frete_redespacho'];
		$PedidoPortalObj->codigo_operacao = $natop;
		$PedidoPortalObj->conta_e_ordem = (!empty($fields['codigo_cliente_conta_e_ordem']) ? true : false);
        $PedidoPortalObj->cod_cliente_conta_e_ordem = $fields['codigo_cliente_conta_e_ordem'];
        $PedidoPortalObj->nasajon = true;
        if($fields['presencial'] != ''){
            $PedidoPortalObj->presencial = !empty($fields['presencial']) && $fields['presencial'] == 'true' ? true : false;
        }
        if($fields['cartao'] != ''){
            $PedidoPortalObj->cartao = !empty($fields['cartao']) ? true : false;
        }
		$cliente_sem_telefone = false;
		if(isset($fields['cliente_sem_telefone'])){
			$cliente_sem_telefone = $fields['cliente_sem_telefone'];
		}
		$PedidoPortalObj->cliente_sem_telefone = $cliente_sem_telefone;
		$PedidoPortalObj->cliente_telefone = $fields['cliente_telefone'] ?? '';

		$PedidoPortalObj->metragem_exata = !empty($fields['metragem_exata']) ? true : false;
		$venda_observacao = false;
		if(in_array($fields['estabelecimento'], $this->estabelecimentos_venda_observacao)){
			$venda_observacao = true;
		}else{
			$fields['observacao'] = '';
		}
		if($venda_observacao == true){
			$PedidoPortalObj->enfestar = !empty($fields['enfestar']) ? true : false;
			$PedidoPortalObj->bater_amostra = !empty($fields['bater_amostra']) ? true : false;
			$PedidoPortalObj->incluir_cartelas = !empty($fields['incluir_cartelas']) ? true : false;
			$detalhesTransportador = TransportadorNasajon::where('codigo', $fields['transportadora'])->first();
			if(!empty($fields['transportadora_retira_horario'])){

				$horario = Carbon::createFromFormat('H:i', $fields['transportadora_retira_horario'])->format('H:i:00');
				$PedidoPortalObj->transportadora_retira_horario = $horario;
			}else{
				$PedidoPortalObj->transportadora_retira_horario = null;
			}
			$transportadora_retira = false;
			if(strtoupper($detalhesTransportador->nome) == 'RETIRA'){
				$PedidoPortalObj->transportadora_retira = true;
				$transportadora_retira = true;
				$PedidoPortalObj->tipo_frete = 'S';
			}else{
				$PedidoPortalObj->transportadora_retira = false;
			}
		}else{
			$PedidoPortalObj->enfestar = false;
			$PedidoPortalObj->bater_amostra = false;
			$PedidoPortalObj->incluir_cartelas = false;
			$PedidoPortalObj->transportadora_retira = false;
			$PedidoPortalObj->transportadora_retira_horario = null;
			$transportadora_retira = false;
		}
		$PedidoPortalObj->observacao = $fields['observacao'];
        
        $preco_frete = $this->fretePreco($PedidoPortalObj);
        $preco_cif_fob = $preco_frete['preco'];
        $frete = $preco_frete['frete'];

        $PedidoPortalObj->frete_preco = strtolower($preco_cif_fob);

        $cliente_pedido = 'Contribuinte';
        if (
            $ClienteObj->inscricaoestadual == 'ISENTO' ||
            intval($ClienteObj->indicadorinscricaoestadual) == 2 ||
            intval($ClienteObj->indicadorinscricaoestadual) == 9
        ){
            $cliente_pedido = 'Isento';
        }
        $ClienteBionexoObj = ClienteBionexo::where('cpf_cnpj', $ClienteObj->cpf_cnpj)->exists();
        if($ClienteBionexoObj == true){
            $PedidoPortalObj->bionexo = true;
            $cliente_pedido .= ' - Bionexo';
        }
		$blacklist = $this->blacklistPedido($ClienteObj);

        $PedidoPortalObj->cielo->each(function($cielo){
            if($cielo->cielo_status_id != 5){
                $HistoricoPedidoObj = new HistoricoPedido();
                $HistoricoPedidoObj->pedido = $cielo->pedido_id;
                $HistoricoPedidoObj->natureza = 'cancelamento_pagamento_pedido';
                $HistoricoPedidoObj->antigo = 'Pedido cancelado por alteração';
                $HistoricoPedidoObj->created_by = 1;
                $HistoricoPedidoObj->save();

                $cielo->cielo_status_id = 5;
                $cielo->save();
            }
        });

		if(
			in_array($PedidoPortalObj->tipo_venda, ['rj_x_sp', 'rj_x_sp_triangular', 'pre_pago_rj_x_sp', 'rj_x_sp_futuro', 'rj_x_sp_triangular_futuro', 'pre_pago_rj_x_sp_futuro'])
		){
			$PedidoPortalObj->rj_x_sp = true;
		}else{
			$PedidoPortalObj->rj_x_sp = false;
		}

        if($PedidoPortalObj->save()){
            $mensagem_pedido = '';

            AprovacaoDePedido::where('pedido_id', $PedidoPortalObj->id)->delete();

            $cliente_balcao = false;
            if(in_array($PedidoPortalObj->cliente->codigo, $this->codigo_cliente_balcao)){
                $cliente_balcao = true;
            }
            $mensagem_presencial = false;
            if($transferencia == false){
                $this->condicaoPagamentoCielo = $this->getIdCondicoesPagamentoCartcao();
				
                if(in_array($PedidoPortalObj->condicao_pagamento, $this->condicaoPagamentoCielo)){
                    $cartao = true;
                    $PedidoPortalObj->cartao = true;
                    
                    if($cliente_balcao == true){
                        $PedidoPortalObj->presencial = true;
                    }elseif(
                        in_array($PedidoPortalObj->estabelecimento, $this->estabelecimentos_venda_presencial_cartao)
                    ){
                        if($vefica_presencial == true){
                            $mensagem_presencial = true;
                        }
                    }else{
                        $PedidoPortalObj->presencial = false;
                    }
                    $PedidoPortalObj->save();
                }else{
                    $PedidoPortalObj->presencial = false;
                    $PedidoPortalObj->cartao = false;
                    $PedidoPortalObj->save();
                }

                if(
                    $PedidoPortalObj->cartao === true &&
                    $PedidoPortalObj->presencial === false &&
                    empty($PedidoPortalObj->email_comprador)
                ){
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [
                            "email_contato" => [__('validation.required', ['attribute' => 'e-mail'])]
                        ]
                    ];
                    return response()->json($error, $this->errorStatus);
                }
                if(
                    $PedidoPortalObj->cartao === true &&
                    $PedidoPortalObj->presencial === false &&
                    !empty($PedidoPortalObj->email_comprador) &&
                    filter_var($PedidoPortalObj->email_comprador, FILTER_VALIDATE_EMAIL) === false
                ){
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [
                            "email_contato" => [__('validation.email', ['attribute' => 'e-mail'])]
                        ]
                    ];
                    return response()->json($error, $this->errorStatus);
                }
            }else{
                $PedidoPortalObj->presencial = false;
                $PedidoPortalObj->cartao = false;
                $PedidoPortalObj->save();
            }

            if(in_array($PedidoPortalObj->tipo_venda, ['pedido_pilotagem']) && $pedido_novo === true){
                $mensagem_pedido = $this->mensagemPilotagem($PedidoPortalObj);
			}
            $estabelecimento = $this->estabelecimentos[$fields['estabelecimento']];
			$response = [
				"error" => [
			    	"error" => false,
				    "msg" => [
				      "dev" => "",
				      "user" => ""
				    ]
				],
			    "request" => $fields,
			    "response" => [
			    	'pedido' => $PedidoPortalObj->id,
                    'media_condicao_pagamento' => $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0,
                    'cif_fob' => $frete,
                    'preco_cif_fob' => $preco_cif_fob,
                    'estado_destino' => $ClienteObj->uf,
                    'pedido_futuro_status' => $PedidoPortalObj->pedido_futuro ? 'Venda futura' : 'Pronta entrega',
                    'cliente_balcao' => $cliente_balcao,
                    'cliente_pedido' => $cliente_pedido,
                    'mensagem_presencial' => $mensagem_presencial,
                    'cartao' => $cartao,
                    'estabelecimento' => $estabelecimento,
                    'mensagem_pedido' => $mensagem_pedido,
					'transferencia' => $transferencia,
					'blacklist' => $blacklist,
					'transportadora_retira' => $transportadora_retira,
					'venda_observacao' => $venda_observacao,
					'rj_x_sp' => $PedidoPortalObj->rj_x_sp,
                    'recalcular_itens' => $recalcular_itens
			    ]
            ];

            return response()->json($response, $this->successStatus);

        }else{
			$error = [
				'error' =>[
					"error" => true,
					"msg" => [
						"dev" => "Ocorreu uma instabilidade!",
						"user" => "Ocorreu uma instabilidade!"
					]
				],
				'request' => $fields,
				'response' => []
			];
			return response()->json($error, $this->errorStatus); 
        }
    }

    public function recuperaUltimosDadosCliente(Request $request, $response_json = true){

        $fields = $request->only(['cod_cliente','estabelecimento']);

        $cliente_balcao = false;
        $blacklist = false;

        $clienteObj = ClienteNasajon::where('codigo', $fields['cod_cliente'])->where('bloqueado', 'false')->first();
        $response = [];
        $condicao_especial = [];
        if(in_array($fields['cod_cliente'], $this->codigo_cliente_balcao)){
            $cliente_balcao = true;

            $transportadoraObj = TransportadorNasajon::where('nome', 'ilike', 'retira')->first();

            $response['email_contato'] = '';
            $response['transportadora_tipo_frete'] = 'S';
            $response['transportadora_codigo'] = $transportadoraObj->codigo??'';
            $response['transportadora_descricao'] =  !is_null($transportadoraObj) ? str_replace(' - __.___.___/____-__', '', trim($transportadoraObj->nome) . ' - ' . trim($transportadoraObj->cnpj)) : '';
            $response['tipo_venda'] = 'pronta_entrega_venda';
        }
        else{
			if(!empty($clienteObj)){
				$cliente_prepago = ClientePrePago::where('cpf_cnpj', $clienteObj->cpf_cnpj)->count();
				if($cliente_prepago > 0){
					$condicao_especial['pre_pago'] = 'Pré-Pago - Pronta Entrega';
					$condicao_especial['pre_pago_triangular'] = 'Pré-Pago - Pronta Entrega - Triangular';
					$condicao_especial['pre_pago_futuro'] = 'Pré-Pago - Pedido Futuro';
				}
				$blacklist = $this->blacklistPedido($clienteObj);
			}
			$PedidoPortalObj = PedidoPortal::where('cod_cliente', $fields['cod_cliente'])->where('nasajon', true)->orderby('updated_at', 'desc')->first();
            // $transportadoraObj = TransportadorNasajon::where('codigo', $PedidoPortalObj->transportadora??'0')->first();
			// $condicaoPagamentoObj = CondicoesPagamentoWeb::find($PedidoPortalObj->condicao_pagamento??'0');

            $response['email_contato'] = $PedidoPortalObj->email_comprador ?? $clienteObj->email ?? '';
            // $response['transportadora_tipo_frete'] = $PedidoPortalObj->tipo_frete ?? '';
            // $response['transportadora_codigo'] = $transportadoraObj->codigo??'';
            // $response['transportadora_descricao'] =  !is_null($transportadoraObj) ? str_replace(' - __.___.___/____-__', '', trim($transportadoraObj->nome) . ' - ' . trim($transportadoraObj->cnpj)) : '';
            $response['tipo_venda'] = '';
			if(
				in_array(intval($fields['estabelecimento']), [3, 4]) &&
				in_array($clienteObj->uf, ['RJ', 'CE', 'PE'])
			){
				$condicao_especial['rj_x_sp'] = 'Operação RJ/SP';
				$condicao_especial['rj_x_sp_triangular'] = 'Operação RJ/SP - Triangular';
                $condicao_especial['rj_x_sp_futuro'] = 'Operação RJ/SP - Pedido Futuro';
                $condicao_especial['rj_x_sp_triangular_futuro'] = 'Operação RJ/SP - Triangular - Pedido Futuro';
                $cliente_prepago = ClientePrePago::where('cpf_cnpj', $clienteObj->cpf_cnpj)->count();
				if($cliente_prepago > 0){
                    $condicao_especial['pre_pago_rj_x_sp'] = 'Pré-Pago - Operação RJ/SP ';
                    $condicao_especial['pre_pago_rj_x_sp_futuro'] = 'Pré-Pago - Operação RJ/SP  - Pedido Futuro';
				}
			}

        }
        if(in_array(intval($fields['estabelecimento']), $this->estabelecimentos_producao)){
			$condicao_especial['producao'] = 'Produção';
            $condicao_especial['producao_triangular'] = 'Produção - Triangular';
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $clienteObj->cpf_cnpj)->count();
				if($cliente_prepago > 0){
                    $condicao_especial['pre_pago_producao'] = 'Pré-Pago - Produção';
                    $condicao_especial['pre_pago_producao_triangular'] = 'Pré-Pago - Produção - Triangular';
				}
        }
        $response['condicao_especial'] = $condicao_especial;

        $response['cliente_balcao'] = $cliente_balcao;
        $response['blacklist'] = $blacklist;

        $razao_cnpj_textil = $this->razao_cnpj_textil;

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        if(
            !in_array(str_pad($fields['estabelecimento'], 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $response['transferencia'] = true;
            $response['condicao_pagamento_codigo'] = '';
            $response['condicao_pagamento_descricao'] = '';
        }else{
            $response['transferencia'] = false;
            // $response['condicao_pagamento_codigo'] = $condicaoPagamentoObj->id??'';
            // $response['condicao_pagamento_descricao'] = (!empty($condicaoPagamentoObj->descricao)) ? $condicaoPagamentoObj->descricao : '';
        }
		$response['cliente_sem_telefone'] = false;
		$response['cliente_telefone'] = '';
		if(empty($clienteObj->telefones)){
			$response['cliente_sem_telefone'] = true;
		}else{
			$telefone = explode(',', $clienteObj->telefones)[0];
			$response['cliente_telefone'] = $telefone;
		}
        if($response_json === true){
            return response()->json($response);
        }else{
            return $response;
        }

    }

    public function filter(Request $request){
        ini_set('memory_limit', '1024M');

        $busca = $request->only("cliente", 'usuario', 'estabelecimento', 'status', 'cliente_id', 'data_inicio', 'data_fim', 'diretor', 'gerente', 'id_filtro', 'pedido_gerado', 'cpf_balcao');
        

        $pedidosObj = PedidoPortal::with('status_pedido_detalhes', 'cliente', 'condicao_pagamento_detalhes', 'usuario_detalhes', 'valor_total', 'pedidoNasajonAberto','pagamentosStone', 'pedidoNasajon');

        if( Auth::user()->username == 'nelson.namura'){
            $pedidosObj->whereIn('estabelecimento', [1, 2]);
        }

        if(isset($busca['estabelecimento']) && !empty($busca['estabelecimento'])){
            $pedidosObj->where('estabelecimento', $busca['estabelecimento']);
        }

        if(isset($busca['status']) && !empty($busca['status'])){
            if(
                $busca['status'] != 'todos' && 
                $busca['status'] != 'cancelados' && !in_array($busca['status'], $this->status_nasajon_indice)
            ){
                $pedidosObj->where('status_pedido', $busca['status']);
            }
            if(
                $busca['status'] == 'todos' || 
                $busca['status'] == 'cancelados'
            ){
                if(
                    $busca['status'] == 'cancelados'
                ){
                    $pedidosObj->whereNotNull('deleted_at');
                }
                $pedidosObj->withTrashed();
            }
        }
        else{
            $pedidosObj->whereIn('status_pedido', [1, 2, 5, 9]);
        }

        $users = [];

        if(isset($busca['diretor']) && !empty($busca['diretor'])){
                $users = UserController::varreSubordinados($busca['diretor']);
        }
        else if(isset($busca['gerente']) && !empty($busca['gerente'])){
            $users = UserController::varreSubordinados($busca['gerente']);
        }
        else if(strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false){
            // $users = UserController::varreSubordinados(Auth::user()->responsavel);

        }
        else if (
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false ||
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false ||
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") !== false
        ){
            $users = UserController::varreSubordinados(Auth::id());
        }

        if(isset($busca['usuario']) && !empty($busca['usuario'])){
            $pedidosObj->where(function($query) use ($busca){
                $query->where('usuario', $busca['usuario'])
                    ->OrWhere('created_by', $busca['usuario']);
            });
        }
        else{
            if (
                (
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") !== false ||
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false ||
                    // strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") !== false ||
                    strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") !== false
                ) && (
                    empty($busca['gerente']) &&
                    empty($busca['diretor'])
                ) && (strtolower(Auth::user()->tipo_usuario->nome) !== "diretor" && strtolower(Auth::user()->tipo_usuario->nome) !== 'administrador')
            ) {
                $pedidosObj->where(function ($query) use ($users){
                    $query->whereIn('usuario', $users)
                        ->orWhereIn('created_by', $users);
                });
            }
            else if (
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "gerente") === false &&
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") === false &&
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") === false &&
                strpos(strtolower(Auth::user()->tipo_usuario->nome), "supervisor") === false

            ) {
                $pedidosObj->where(function ($query) use ($users) {
                    $query->where('usuario', Auth::id())
                        ->orWhere('created_by', Auth::id());
                });
            }
        }
        if (isset($busca['cliente']) && !empty($busca['cliente'])){

			$pedidosObj->where(function($query) use ($busca){
				$cliente_busca = ClienteNasajon::select('codigo')->where(DB::raw('TRIM(CONCAT(TRIM(nome), \' - \', cpf_cnpj))'), 'ILIKE', trim($busca['cliente']))->where('bloqueado', 'false')->get();
				$query->orWhereIn('cod_cliente', $cliente_busca->pluck('codigo'));
			});

        }
        
        if (isset($busca['data_inicio']) && !empty($busca['data_inicio']) && isset($busca['data_fim']) && !empty($busca['data_fim'])){
            $pedidosObj->whereBetween('data_pedido', [date("Y-m-d", strtotime(str_replace("/", "-", $busca['data_inicio']))), date("Y-m-d", strtotime(str_replace("/", "-", $busca['data_fim'])))]);
        }
        
        else if (isset($busca['data_inicio']) && !empty($busca['data_inicio']) && (!isset($busca['data_fim']) || empty($busca['data_fim']))) {
            $pedidosObj->where('data_pedido', ">=", [date("Y-m-d", strtotime(str_replace("/", "-", $busca['data_inicio'])))]);
        }

        else if ((!isset($busca['data_inicio']) || empty($busca['data_inicio'])) && isset($busca['data_fim']) && !empty($busca['data_fim'])){
            $pedidosObj->where('data_pedido', "<=", [date("Y-m-d", strtotime(str_replace("/", "-", $busca['data_fim'])))]);
        }

        if(isset($busca['id_filtro']) && is_numeric($busca['id_filtro'])){
            $pedidosObj->where('id', $busca['id_filtro']);
        }

        if(isset($busca['pedido_gerado']) && is_numeric($busca['pedido_gerado'])){
            $pedidosObj->where('pedido_gerado', $busca['pedido_gerado']);
        }

        if(isset($busca['cpf_balcao']) && !empty($busca['cpf_balcao'])){
            $pedidosObj->where(function($query) use ($busca){
                $query->where('no_pedido_compra', $busca['cpf_balcao']);
                $query->whereIn('cod_cliente', $this->codigo_cliente_balcao);
            });
        }

        $pedidos = $pedidosObj->get();
        $estabelecimento = returnEmpresasNasajonView();

        $return = [];
        foreach ($pedidos as $key => $pedido) {
            if(!in_array($busca['status'], $this->status_nasajon_indice)){
                $cliente = $pedido->cliente;
            
                $status_pedido = $pedido->status_pedido;
                $status_pedido_detalhes = $pedido->status_pedido_detalhes->status;
                $status_pedido_cartao = 0;
                if($status_pedido == 2){
                    $aprovacaoQuery = AprovacaoDePedido::where('pedido_id', $pedido->id)->first();
                    if(!empty($aprovacaoQuery->credito) && !empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_credito_user_id) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                        $status_pedido_detalhes = 'Aprovação Crédito / Comercial';
                    }else if(!empty($aprovacaoQuery->credito) && empty($aprovacaoQuery->aprovacao_credito_user_id)){
                        $status_pedido_detalhes = 'Aprovação Crédito';
                    }else if(!empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                        $status_pedido_detalhes = 'Aprovação Comercial';
                    }
                }
    
                if(!empty($pedido->cielo)){
                    $pedido->cielo->each(function($cielo) use (&$status_pedido_cartao){
                        if($cielo->cielo_status_id == 8){
                            $status_pedido_cartao = 1;
                        }
                    });
                }
    
                $data_pedido = '';
                $data_pedido = (isset($pedido->pedido_futuro) && $pedido->pedido_futuro == "true")? 'Futuro' :(is_null($pedido->data_pedido)?'': date('d/m/Y', strtotime($pedido->data_pedido)));
                if($pedido->rj_x_sp == true && $status_pedido > 1){
                    $data_pedido .= ' - Operação RJ/SP';
                }
                
                if(!is_null($pedido->deleted_at)){
                    $status_pedido = 0;
                    $status_pedido_detalhes = 'Cancelado';
                }
                $return[$key]["cliente"] = "";
                if(!empty($cliente)){
                    if(!empty($cliente['NOME'])){
                        $return[$key]["cliente"] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . utf8_encode($cliente['NOME']) . "'>" . utf8_encode($cliente['NOME']) . "</div></div>";
                    }elseif(!empty($cliente['nome'])){
                        $return[$key]["cliente"] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $cliente['nome'] . "'>" . $cliente['nome'] . "</div></div>";
                    }
                }

                $pago_presencial_cartao = false;

                $pedido->pagamentosStone->each(function($query) use (&$pago_presencial_cartao){
                    if($query->pago === true){
                        $pago_presencial_cartao = true;
                    }
                });

                $return[$key]["id"] = $pedido->id; 
                $return[$key]["pedido_gerado"] = $pedido->pedido_gerado;
                $return[$key]["presencial"] = $pedido->presencial;
                $return[$key]["status_pedido"] = $status_pedido;
                $return[$key]["presencial"] = $pedido->presencial;
                $return[$key]["stone"] = (!empty($pedido->pagamentosStone[0])) ? true : false;
                $return[$key]["pago_presencial_cartao"] = $pago_presencial_cartao;
                $return[$key]["status_pedido_cartao"] = $status_pedido_cartao;
                $return[$key]["status_pedido_detalhes"] = $status_pedido_detalhes;
                $return[$key]["status_pedido_codigo"] = $pedido->status_pedido;
                $return[$key]["condicao_pagamento_detalhes"] =  is_null($pedido->condicao_pagamento_detalhes) ? '' : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $pedido->condicao_pagamento_detalhes->descricao . "'>" . $pedido->condicao_pagamento_detalhes->descricao . "</div></div>";
                $return[$key]["condicao_pagamento_codigo"] =  $pedido->condicao_pagamento;
                $return[$key]["estabelecimento"] = str_pad($pedido->estabelecimento, 2, 0, STR_PAD_LEFT);
                $return[$key]["data_pedido"] = $data_pedido;
                $return[$key]["valor_total"] = !isset($pedido->valor_total->total) ? '' : parserValor($pedido->valor_total->total);
                $return[$key]["usuario_nome"] = !isset($pedido->usuario_detalhes) ? '' : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($pedido->usuario_detalhes->name) . "'>" . ($pedido->usuario_detalhes->name) . "</div></div>";
                $return[$key]["cartao"] = $pedido->cartao;
                $return[$key]["nasajon_cancelar"] = empty($pedido->pedidoNasajonAberto)? false : (in_array($pedido->pedidoNasajonAberto->situacao_descricao, ['Aberto', 'Em separação', 'Em Faturamento'])? true : false);
                $return[$key]["nasajon_id"] = empty($pedido->pedidoNasajonAberto)? '': $pedido->pedidoNasajonAberto->id;
            }else{
                if(!empty($pedido->pedidoNasajon)){
                    $cliente = $pedido->cliente;
            
                    $status_pedido = $pedido->status_pedido;
                    $status_pedido_detalhes = $pedido->pedidoNasajon->situacao_descricao;
                    $status_pedido_cartao = 0;
                    if($status_pedido == 2){
                        $aprovacaoQuery = AprovacaoDePedido::where('pedido_id', $pedido->id)->first();
                        if(!empty($aprovacaoQuery->credito) && !empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_credito_user_id) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                            $status_pedido_detalhes = 'Aprovação Crédito / Comercial';
                        }else if(!empty($aprovacaoQuery->credito) && empty($aprovacaoQuery->aprovacao_credito_user_id)){
                            $status_pedido_detalhes = 'Aprovação Crédito';
                        }else if(!empty($aprovacaoQuery->preco) && empty($aprovacaoQuery->aprovacao_preco_user_id)){
                            $status_pedido_detalhes = 'Aprovação Comercial';
                        }
                    }
        
                    if(!empty($pedido->cielo)){
                        $pedido->cielo->each(function($cielo) use (&$status_pedido_cartao){
                            if($cielo->cielo_status_id == 8){
                                $status_pedido_cartao = 1;
                            }
                        });
                    }
        
                    $data_pedido = '';
                    $data_pedido = (isset($pedido->pedido_futuro) && $pedido->pedido_futuro == "true")? 'Futuro' :(is_null($pedido->data_pedido)?'': date('d/m/Y', strtotime($pedido->data_pedido)));
                    if($pedido->rj_x_sp == true && $status_pedido > 1){
                        $data_pedido .= ' - Operação RJ/SP';
                    }
                    
                    if(!is_null($pedido->deleted_at)){
                        $status_pedido = 0;
                        $status_pedido_detalhes = 'Cancelado';
                    }
                    $return[$key]["cliente"] = "";
                    if(!empty($cliente)){
                        if(!empty($cliente['NOME'])){
                            $return[$key]["cliente"] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . utf8_encode($cliente['NOME']) . "'>" . utf8_encode($cliente['NOME']) . "</div></div>";
                        }elseif(!empty($cliente['nome'])){
                            $return[$key]["cliente"] = "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $cliente['nome'] . "'>" . $cliente['nome'] . "</div></div>";
                        }
                    }

                    $pago_presencial_cartao = false;
    
                    $pedido->pagamentosStone->each(function($query) use (&$pago_presencial_cartao){
                        if($query->pago === true){
                            $pago_presencial_cartao = true;
                        }
                    });
    
                    $return[$key]["id"] = $pedido->id; 
                    $return[$key]["pedido_gerado"] = $pedido->pedido_gerado;
                    $return[$key]["presencial"] = $pedido->presencial;
                    $return[$key]["status_pedido"] = $status_pedido;
                    $return[$key]["presencial"] = $pedido->presencial;
                    $return[$key]["stone"] = (!empty($pedido->pagamentosStone[0])) ? true : false;
                    $return[$key]["pago_presencial_cartao"] = $pago_presencial_cartao;
                    $return[$key]["status_pedido_cartao"] = $status_pedido_cartao;
                    $return[$key]["status_pedido_detalhes"] = $status_pedido_detalhes;
                    $return[$key]["status_pedido_codigo"] = $pedido->status_pedido;
                    $return[$key]["condicao_pagamento_detalhes"] =  is_null($pedido->condicao_pagamento_detalhes) ? '' : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . $pedido->condicao_pagamento_detalhes->descricao . "'>" . $pedido->condicao_pagamento_detalhes->descricao . "</div></div>";
                    $return[$key]["condicao_pagamento_codigo"] =  $pedido->condicao_pagamento;
                    $return[$key]["estabelecimento"] = str_pad($pedido->estabelecimento, 2, 0, STR_PAD_LEFT);
                    $return[$key]["data_pedido"] = $data_pedido;
                    $return[$key]["valor_total"] = !isset($pedido->valor_total->total) ? '' : parserValor($pedido->valor_total->total);
                    $return[$key]["usuario_nome"] = !isset($pedido->usuario_detalhes) ? '' : "<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" . ($pedido->usuario_detalhes->name) . "'>" . ($pedido->usuario_detalhes->name) . "</div></div>";
                    $return[$key]["cartao"] = $pedido->cartao;
                    $return[$key]["nasajon_cancelar"] = in_array($pedido->pedidoNasajon->situacao_descricao, ['Aberto', 'Em separação', 'Em Faturamento'])? true : false;
                    $return[$key]["nasajon_id"] = $pedido->pedidoNasajon->id;
                }
            }             
        }
        
        return response()->json($return);
    }

    public function modalDesconto(Request $request){

        $pedido = PedidoPortal::find($request->id);

        $info = [
            'id' => $pedido->id,
            'valor_desconto' => parserValor($pedido->valor_desconto??0)
        ];

        return view('programs.pedido_portal.modal-mudar-desconto')->with(['info' => $info]);
        
    }

    public function mudarDesconto(Request $request){

        $pedido = PedidoPortal::find($request->id);
        $pedido->valor_desconto = floatval(str_replace(",", ".", str_replace(".", "", $request->valor_desconto)));
        $pedido->save();

        $response = [
            'ta' => 'ta'
        ];

        return response()->json($response, 200);

    }

    public function totalizadores(Request $request){
        
        $pedido = PedidoPortal::with(['itens_pedido', 'itens_pedido.info_produtoNasjon'])->find($request->id);

        $return = [];
        
        if(!empty($pedido)){

            $total_produtos = 0;
            $total_frete = ($pedido->tipo_frete == 'C') ? $pedido->valor_frete : 0;
            $valor_desconto = $pedido->valor_desconto ?? 0;

            $peso_total = 0; 

            foreach($pedido->itens_pedido as $item){
                $peso_total += $item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0);
                $total_produtos +=  !empty($item->tem_ipi) && intval($pedido->estabelecimento) == 3? round($item->preco_unitario * (1 + ($item->ipi_produto/100)) * $item->quantidade, 2) : round($item->quantidade * $item->preco_unitario, 2);
            }

            $peso_total = parserValor($peso_total) . ' kg';

            $return = [
                'total_itens' => $pedido->itens_pedido->count(),
                'valor_total_produtos' => parserValor($total_produtos),
                'peso_total' => $peso_total,
                'valor_total_frete' => parserValor($total_frete),
                'valor_desconto' => parserValor($valor_desconto),
                'valor_total_pedido' => parserValor(($total_produtos + ($pedido->tipo_frete == 'C'?$total_frete:0) - $valor_desconto)),
            ];
        }

        return response()->json($return, 200);
        
    }


    public function modalExcluir(Request $request){

        $pedido = PedidoPortal::with('status_pedido_detalhes')->findOrFail($request->id);
        $estabelecimentos = returnEmpresasNasajonView();

        $return['estabelecimento'] = $estabelecimentos[$pedido->estabelecimento];
        $return['data_pedido'] = date("d/m/Y", strtotime($pedido->data_pedido));
        $return['valor_total'] = isset($pedido->valor_total->total)?parserValor($pedido->valor_total->total): '';
        $return['observacao'] = $pedido->observacao;
        $return['status'] = $pedido->status_pedido_detalhes->status;
        $return['condicao_pagamento'] = $pedido->condicao_pagamento_detalhes->descricao??'';

        if(!empty($pedido->cliente)){
            if($pedido->nasajon == false){
                $return['cliente'] = utf8_encode($pedido->cliente->NOME);
            }
            else{
                $return['cliente'] = $pedido->cliente->nome;
            }
        }
        else{
            $return['cliente'] = '';
        }
        return view('programs.pedido_portal.excluir')->with(['pedido' => $return]);

    }
    public function excluir(Request $request){

        $pedidoObj = PedidoPortal::with(
            'aprovacao',
            'carrinhoCompras', 
            'carrinhoCompras.carrinhoCompraItens', 
            'carrinhoCompras.dadosClientePedido',
            'pagamentosStone'
        )
        ->find($request->id);
        
        if(empty($pedidoObj)){
            return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
        }
        if(!in_array($pedidoObj->status_pedido, [1, 5, 7, 8, 9, 10, 20])){
            return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
        }
        if (!is_null($pedidoObj->aprovacao)){
            $pedidoObj->aprovacao->delete();
        }

        if (!empty($pedidoObj->usarCredito)){
            foreach($pedidoObj->usarCredito as $credito){
                $credito->delete();
                $credito->save();
            }
        }

        if(!empty($pedidoObj->pagamentosStone[0])){
            $pedidoObj->pagamentosStone->each(function($query) use ($pedidoObj){
                $transacao_controller = new StoneTransacaoController();
                $transacao_controller->finalizarTransacao(null,$query->pre_transaction_id,'canceled');
            });
        }

        $pedidoObj->deleted_by = Auth::id();
        $pedidoObj->save();
        
        $pedidoObj->delete();

        $HistoricoPedidoObj = new HistoricoPedido();
		$HistoricoPedidoObj->pedido = $pedidoObj->id;
		$HistoricoPedidoObj->natureza = 'cancelamento';
        $HistoricoPedidoObj->antigo = 'Pedido cancelado pelo usuario';
		$HistoricoPedidoObj->created_by = Auth::id();
		$HistoricoPedidoObj->save();

        $carrinho = $request->only('carrinho');

        if(empty($carrinho)){
            if(isset($pedidoObj->carrinhoCompras)){
                $usuarioCarrinhoCompras = CarrinhoCompra::where('created_by', $pedidoObj->carrinhoCompras->created_by)
                ->where('finalizado', false)
                ->count();

                if($usuarioCarrinhoCompras == 1){
                    $pedidoObj->carrinhoCompras->dadosClientePedido->deleted_by = Auth::id(); 
                    $pedidoObj->carrinhoCompras->dadosClientePedido->save();
                    $pedidoObj->carrinhoCompras->dadosClientePedido->delete();
                }
                $pedidoObj->carrinhoCompras->deleted_by = Auth::id();
                $pedidoObj->carrinhoCompras->save();
                $pedidoObj->carrinhoCompras->delete();
    
                foreach($pedidoObj->carrinhoCompras->carrinhoCompraItens as $value){
                    $value->deleted_by = 1;
                    $value->save();
                    $value->delete();
                }
            }
        }
        return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
    }

    public function recalcularTodosItensPedido(Request $request){

        $fields = $request->only('id', 'mudanca', 'transportadora_codigo');

        $pedidoPortalObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes', 'cliente', 'usuario_detalhes', 'condicao_pagamento_detalhes')->find($fields['id']);

        $produtoController = new ProdutoController;
        $pedidoItem = new PedidoItemPortalController;

        if (!is_null($fields['mudanca'])){
            $hoje = Carbon::Now()->format('Y-m-d');
            
            $cotacaoDoDiaObj = Cotacoes::where('data', $hoje)
            ->whereHas('moeda', function($query){
                $query->where('codigo', 220);
            })
            ->orderBy('lastupdate')
            ->first();

            if(is_null($cotacaoDoDiaObj)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ocorreu uma instabilidade contate o setor responsavel!',
                    'error' => [
                        'msg' => 
                        [
                            'pedido_futuro' => 'Cotação do dólar do dia não cadastrada'
                        ],
                    ],
                        'response' => []
                ], 422);
            }

        }

                
        $preco_frete = $this->fretePreco($pedidoPortalObj, $fields['transportadora_codigo']);
        $frete = $preco_frete['preco'];
        $estadoObj = CepEstado::find($pedidoPortalObj->cliente->uf);

        switch ($pedidoPortalObj->estabelecimento) {
            case "03":
            case 3:
                $origem  = "RO";
                break;
            case "04":
            case 4:
                $origem = "TO";
                break;
            default:
                $origem = 'SP';
                break;
        }
		if($pedidoPortalObj->rj_x_sp == true){
			$origem = 'SP';
		}
        $paramostr_preco = [];
        $paramostr_preco['origem'] = $origem;
        $paramostr_preco['estado'] = $pedidoPortalObj->cliente->uf;
        if ($pedidoPortalObj->pedido_futuro === false || intval($pedidoPortalObj->estabelecimento) != 3){
            $paramostr_preco['moeda'] = 'real';
        }
        else if($pedidoPortalObj->pedido_futuro === true && intval($pedidoPortalObj->estabelecimento) == 3){
            $paramostr_preco['moeda'] = 'dolar';
        }
        $paramostr_preco['promocao'] = false;
        $paramostr_preco['cliente'] = $pedidoPortalObj->cod_cliente;
        $paramostr_preco['codigo_vendedor'] = $pedidoPortalObj->usuario_detalhes->codigo_representante;
        $paramostr_preco['estabelecimento'] = $pedidoPortalObj->estabelecimento;

        $paramostr_preco['frete'] = $frete;
        $paramostr_preco['regiao'] = $estadoObj->regiao;
        $paramostr_preco['tipo_cliente'] = (
            $pedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($pedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        
        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoPortalObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $paramostr_preco['prazo_medio'] = 0;
            $preco_custo = true;
        } else {
            $paramostr_preco['prazo_medio'] = $pedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;
        }

        $listaDePrecosObj = new ListagemDePrecosController();
        
        $pedidoPortalObj->itens_pedido->each(function($item) use($paramostr_preco, $listaDePrecosObj, $preco_custo, &$itens, $pedidoPortalObj) {
            $paramostr_preco['produto'] = $item->cod_produto;
            $filter_request = new ListaDePrecosRequest($paramostr_preco);
            $lista_preco_result = $listaDePrecosObj->filter($filter_request, true, false, true);
            $lista_preco_result = reset($lista_preco_result);
            $preco_unitario = parserNumber($lista_preco_result['coluna_a']);
            if($item->token_promocional == 'ultra_black'){
                $preco_unitario = parserNumber(parserValor(parserNumber($lista_preco_result['coluna_a']) * 0.75));
            }
			
			$preco_original = 0;
			if($pedidoPortalObj->metragem_exata === true){
				$preco_original = $preco_unitario;
				$preco_unitario = $preco_unitario * (1 + ($this->porcentagem_metragem_exata / 100));
			}

            if($preco_custo !== true){
                $item->preco_unitario = $preco_unitario;
                $item->coluna_a = parserNumber($lista_preco_result['coluna_a']);
                $item->coluna_b = parserNumber($lista_preco_result['coluna_b']);
                $item->coluna_c = parserNumber($lista_preco_result['coluna_c']);
                $item->comissao = $pedidoPortalObj->usuario_detalhes->comissao_a;
                $item->valor_total = $item->preco_unitario * $item->quantidade;
                $item->preco_original = $preco_original;
            }else{
                $item->comissao = 0;
            }
            $item->save();
            $itens[] = [
                'id' => $item->id,
                'codigo' => $item->especificacoes->codigo_produto,
                'descricao' => $item->especificacoes->descricao,
                'preco_unitario' => "<div><div class='preco' data-preco_original='".parserValor($preco_original)."'  data-toggle='popover' data-placement='right' data-html='true' title='' data-content=''>" . parserValor($item->preco_unitario) . '</div></div>',
                'quantidade' => "<div><div class='estoque' data-toggle='popover' data-placement='left' data-html='true' title='' data-content=''>" . parserValor($item->quantidade) . '</div></div>',
                'valor_total' => parserValor($item->valor_total),
                'coluna' => $item->coluna,
                'comissao' => $item->comissao . "%",
                'token_promocional' => $item->token_promocional,
                'preco' => $item->preco_unitario
            ];

        });

        if(empty($erros)){
            if(isset($fields['editar_transportadora'])){
                return $itens??[];
            }else{
                return response()->json(['status' => 'success', 'message' => '', 'response' => ['itens' => $itens??[]]], 200);
            }
        }else{
            return response()->json(['status' => 'error', 'message' => '', 'error' => $erros, 'response' => []], 422);
        }
    }

    public function apagarTodosItensPedido(Request $request){
        $fields = $request->only('id');
        $items = PedidoItemPortal::where('pedido', '=', $fields['id'])->delete();
        return response()->json(['status' => 'success', 'message' => '', 'error' => [], 'response' => []], 200);
    }

    public function processarPedido(PedidoSalvarRequest $request){
        return $this->processarPedidoPortal($request->id);

    }

    public function processarPedidoPortal($id, $carrinho = null){
        $pedidoObj = PedidoPortal::with( 
            'carrinhoCompras', 
            'carrinhoCompras.carrinhoCompraItens', 
            'carrinhoCompras.dadosClientePedido',
            'condicao_pagamento_detalhes.forma_pagamento',
            'condicao_pagamento_detalhes.parcelas',
            'valor_total',
            'status_pedido_detalhes'
        )
        ->findOrFail($id);
        switch ($pedidoObj->estabelecimento) {
            case '3':
                $origem = 'RO';
                break;
            case '4':
                $origem = 'TO';
                break;            
            default:
                $origem = 'SP';
                break;
        }
		if($pedidoObj->rj_x_sp == true){
			$origem = 'SP';
		}
        
		// if($pedidoObj->tipo_venda == 'producao'){
		// 	return response()->json(['status' => 'error', 'message' => 'Consultar Ingrid para Fechamento do Pedido', 'error' => [], 'response' => []], 422);
		// }

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            !in_array(str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $this->razao_cnpj_textil === $raiz_cnpj
        ){
            $preco_custo = true;
        }


        if($preco_custo == false){
            $media_prazo = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;
        }else{
            $media_prazo = 0;
        }

        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;

        $natop = $aprovacaoDePedidoControllerObj->getCodigoOperacao($pedidoObj);

        if($media_prazo < 15){
            $coluna = 'prazo_vista';
        }
        else if($media_prazo >= 15 && $media_prazo < 30){
            $coluna = 'prazo_15';
        }
        else if($media_prazo >= 30 && $media_prazo < 45){
            $coluna = 'prazo_30';
        }
        else if($media_prazo >= 45 && $media_prazo < 60){
            $coluna = 'prazo_45';
        }
        else if($media_prazo >= 60){
            $coluna = 'prazo_60';
		}

        $requestTotalizadores = new Request(['id' => $pedidoObj->id]);
        $totalizadores = json_decode($this->totalizadores($requestTotalizadores)->content(), TRUE);
        if ($totalizadores['valor_total_pedido'] < 0){
            $errors[] = ['total_pedido' => ["O valor do pedido deve ser maior que zero"]];
		}
		
		$margem_prazo = MargemPrazo::where('estabelecimento', $pedidoObj->estabelecimento)->first();

        if($pedidoObj->estabelecimento === 3){
            $valor_total_nota_ipi = round($pedidoObj->valor_total_ipi->total, 2) + floatval($pedidoObj->tipo_frete == 'C'? $pedidoObj->valor_frete : 0) - floatval($pedidoObj->valor_desconto);
        }else{
            $valor_total_nota_ipi = $pedidoObj->itens_pedido->sum('valor_total') + floatval($pedidoObj->tipo_frete == 'C'? $pedidoObj->valor_frete : 0) - floatval($pedidoObj->valor_desconto);
        }

        $pedidoObj->fill([
            'codigo_operacao' => $natop,
            'base_icms' => 0,
            'data_pedido' => date('Y-m-d'),
            'valor_icms' => $pedidoObj->itens_pedido->sum('valor_icms'),
            'base_icmsst' => $pedidoObj->itens_pedido->sum('base_icmsst'),
            'valor_icmsst' => $pedidoObj->itens_pedido->sum('valor_icmsst'),
            'valor_frete' => floatval($pedidoObj->tipo_frete == 'C' ? $pedidoObj->valor_frete : 0),
            'valor_seguro' => 0,
            'outros_valores' => 0,
            'valor_ipi' => $pedidoObj->itens_pedido->sum('base_ipi'),
            'valor_total_produtos' => $pedidoObj->valor_total->total,
            'valor_total_nota' => $valor_total_nota_ipi,
            'total_pedido' => $pedidoObj->itens_pedido->sum('valor_total'),
        ]);
        $pedidoObj->save();

        $erro_estoque = [];
        $erro_preco = [];
        $clienteObj = ClienteNasajon::where('codigo', $pedidoObj->cod_cliente)->where('bloqueado', 'false')->first();
        
        $preco_frete = $this->fretePreco($pedidoObj);
        $preco_cif_fob = $preco_frete['preco'];
        $frete = $preco_frete['frete'];

        if((boolval($pedidoObj->rj_x_sp) == true)){
            $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', '07')->get()->first();
        }else{
            $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $pedidoObj->estabelecimento)->get()->first();
        }
        
        $media = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;
        foreach($pedidoObj->itens_pedido as $key => $value){
            $produto = ProdutoEspecificacao::where('codigo_produto', strtoupper($value->cod_produto))->first();
            if(in_array($produto->grupo, $this->grupos_especiais) &&  $media > $this->media_especial){
                $erro_estoque[$value->cod_produto] = "Condição média para este produto tem que ser menor ou igual a {$this->media_especial} dias";
            }
            $requestObj = new Request([
                'cod_cliente' => $pedidoObj->cod_cliente, 
                'codigo' => $value->cod_produto, 
                'estabelecimento' => $pedidoObj->estabelecimento, 
                'prazo' => $media_prazo,  
                'tipo_frete' => $frete, 
                'estabel' => str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT),
                'pedido_futuro' => $pedidoObj->pedido_futuro, 
                'data_previsao_entrega' => $pedidoObj->data_previsao_entrega,
                'estado' => $pedidoObj->cliente['estado'],
                'cod_exato' => true,
                'pedido' => $pedidoObj->id
            ]);
            $infoProduto = new ProdutoController();
            try {
                $result_estoque = $infoProduto->retornarDadosEstoqueNasajon($produto, $pedidoObj->estabelecimento, $pedidoObj, false, $value->codigo_tecidos_base);
            } catch (Exception $e) {
				return response()
					->json(
						[
							'status' => 'error', 
							'message' => 'Erro na execução: ' . $e->message . ' - ' . var_dump($result_estoque),
							'error' => [],
							'response' => []
						],
					422);
            }
            if(
                empty($result_estoque)
			){
                $erro_estoque[$value->cod_produto] = 'O produto não tem estoque para completar o pedido. Favor verificar.';
            }
            else {
                if ($pedidoObj->pedido_futuro === true){
                    $coluna_quinzena = date('\k_Y_m_', strtotime($pedidoObj->data_previsao_entrega)) . (date('j', strtotime($pedidoObj->data_previsao_entrega)) <= 15? "1": "2");
                    if (str_replace(',', '.', str_replace('.', '', $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) <= 0){
                        $erro_estoque[$value->cod_produto] = 'O produto não tem nenhum estoque disponível na compra da quinzena. Favor verificar.';
                    }
                    else if (str_replace(',', '.', str_replace('.', '', $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) < $value->quantidade){
                        $erro_estoque[$value->cod_produto] = 'O produto só tem '. $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'] .' ' . $result_estoque['unidade'] . ' disponível na compra da quinzena e não atenderá o pedido. Favor verificar.';
                    }

                    $numero_compra = '';
                    if(isset($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'])){
                        foreach($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'] as $verificacao_pedido){
                            if($verificacao_pedido['quantidade'] >= $value->quantidade){
                                $numero_compra = $verificacao_pedido['pedido'];
                            }
                        }
                        if(empty($numero_compra)){
                            $verificacao_total = 0;
                            foreach($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'] as $verificacao_pedido){
                                $verificacao_total += $verificacao_pedido['quantidade'];
                                if($verificacao_total < $value->quantidade){
                                    if(!empty($numero_compra)){
                                        $numero_compra .= '-'.$verificacao_pedido['pedido'].':'.$verificacao_pedido['quantidade'];
                                    }else{
                                        $numero_compra = $verificacao_pedido['pedido'].':'.$verificacao_pedido['quantidade'];
                                    }                                
                                }
                            }
                        }
                        $value->numero_compra = $numero_compra;
                        $value->save();
                    }
                }
                else if($pedidoObj->pedido_futuro === false){
                    if((str_replace(',', '.', str_replace('.', '', $result_estoque['pronta_entrega']))) <= 0){
                        $erro_estoque[$value->cod_produto] = 'O produto não tem nenhum estoque disponível no estabelecimento. Favor verificar.';
                    }
                    else if((str_replace(',', '.', str_replace('.', '', $result_estoque['pronta_entrega'])))  < $value->quantidade && $pedidoObj->pedido_futuro === false){
                        $erro_estoque[$value->cod_produto] = 'O produto só tem '. $result_estoque['pronta_entrega'] .' ' . $result_estoque['unidade'] . ' em estoque e não atenderá o pedido. Favor verificar.';
                    }
                }
            }
            if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
                $erro_estoque = [];
            }

            if(!in_array($pedidoObj->cod_cliente, $this->codigo_grupo_textil_mn) && $preco_custo === false){
				
                $internacional = 'false';
                $estadoObj = CepEstado::find($clienteObj->uf);

                $aliquotaObj = AliquotaPreco::where('origem', $origem)
                    ->where('estado', $clienteObj->uf)
                    ->where('internacional', $internacional)
                    ->first();


                $arr['cidade'] = $pedidoObj->clienteSemBloqueio->uf??null;

                $arr['origem'] = $origem;
                $arr['produto'] = $value->cod_produto;
                $arr['estado'] = $clienteObj->uf;
                $arr['aliquota'] = $pedidoObj->tipo_venda == 'isento'? $aliquotaObj->icms_venda_cliente_isento : $aliquotaObj->icms_venda;
                $arr['moeda'] = ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
                $arr['frete'] = $preco_cif_fob;
                $arr['regiao'] = $estadoObj->regiao;
                $arr['tipo_cliente'] = (
                    $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico';
                
                $promocao = false;
                if($pedidoObj->pedido_futuro === false){
                    $promocao = true;
                }
                $arr['promocao'] = $promocao;
                $arr['cliente'] = $pedidoObj->cod_cliente;
                $arr['codigo_vendedor'] = $pedidoObj->usuario_detalhes->codigo_representante;
                $arr['prazo_medio'] = $media_prazo;

                if (strtolower(Auth::user()->tipo_usuario->nome) != 'vendedor interno'){
                    $arr['coluna'] = $coluna;
                }
                else{
                    $arr['coluna'] = 'coluna_a';
                }
                if((boolval($pedidoObj->rj_x_sp) == true)){
                    $arr['estabelecimento'] = 7;
                }else{
                    $arr['estabelecimento'] = $pedidoObj->estabelecimento;
                }

                $listaDePrecosObj = new ListagemDePrecosController();
                $filter_request = new ListaDePrecosRequest($arr);
                $use_auth = true;
                $salvar_pesquisa = false;
                $retornar_array = true;
                $aprovacao = false;
                $exportacao = false;
                $deletados = false;
                $olharEstoque = false;

                $arr_result = $listaDePrecosObj->filter(
                    $filter_request,
                    $use_auth,
                    $salvar_pesquisa,
                    $retornar_array,
                    $aprovacao,
                    $exportacao,
                    $deletados,
                    $olharEstoque
                );
                if(empty($arr_result)){
                    continue;
                }

                if (strtolower(Auth::user()->tipo_usuario->nome) != 'vendedor interno'){
                    $valor_minimo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0]['coluna_a'])) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                    $valor_maximo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0]['coluna_c'])) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;
                }
                else{
                    $valor_minimo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0][$coluna])) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                    $valor_maximo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0][$coluna])) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;            
                }

                if (floatval($valor_minimo) > floatval($value->preco_unitario)){
                    $erro_preco[$value->cod_produto] = 'O preço unitário do item está abaixo do mínimo permitido de ' . parserValor($valor_minimo) . '. Favor verificar';
                }
                if (floatval($valor_maximo) < floatval($value->preco_unitario)){
                    $erro_preco[$value->cod_produto] = 'O preço unitário do item está acima do máximo permitido de ' . parserValor($valor_maximo) . '. Favor verificar';
                }

                if(in_array($pedidoObj->tipo_venda, ['pedido_pilotagem'])){
                    $ProdutoNasajonObj = ProdutoNasajon::where('codigo', strtoupper($value->cod_produto))->first();
                    if(empty($ProdutoNasajonObj)){
                        $ProdutoNasajonObj = ProdutoUsoConsumoNasajon::where('codigo', strtoupper($value->cod_produto))->first();
                    }
                    $codigo_uso_consumo = ['056200900AM01', '056200300AM01'];
                    if(empty($ProdutoNasajonObj) && in_array($value->cod_produto, $this->codigo_uso_consumo)){
                        if(
                            $this->quantidade_undiade_limite_pilotagem < $value->quantidade
                        ){
                            $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_undiade_limite_pilotagem."");
                        }
                    }else{
                        if(
                            !in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS', 'Kg', 'KG', 'PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                        ){
                            $erro_estoque[$value->cod_produto] = ("Unidade do produto inválida<br>Unidades aceitas para produção:<br>Kilo = kg<br>Metros = m<br>Unideade = un");
                        }
                        if(
                            $this->quantidade_metros_limite_pilotagem < $value->quantidade &&
                            in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS'])
                        ){
                            $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_metros_limite_pilotagem."m");
                        }
                        if(
                            $this->quantidade_kg_limite_pilotagem < $value->quantidade &&
                            in_array($ProdutoNasajonObj->unidade, ['Kg', 'KG'])
                        ){
                            $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_kg_limite_pilotagem."Kg");
                        }
                        if(
                            $this->quantidade_undiade_limite_pilotagem < $value->quantidade &&
                            in_array($ProdutoNasajonObj->unidade, ['PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                        ){
                            $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_undiade_limite_pilotagem."");
                        }
                    }
                }
            }
        }
		$pedidoObj->observacao = $this->criandoObservacaoPedido($pedidoObj);

		$errors = [];
        if(count($erro_estoque) > 0){
            $errors['estoque'] = $erro_estoque;
        }

        if(count($erro_preco) > 0){
            $errors['preco'] = $erro_preco;
        }

        if (count($errors) > 0){
            return response()->json([
                'status' => 'error', 
                'message' => 'Informações inválidas',
                'errors' => $errors,
                'reponse' => []
            ], 422);
        }
        if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $data = Carbon::now();
            $data->addDays(2);
            if($data->format('N') == 6){
                $data->addDays(2);
            }else if($data->format('N') == 7){
                $data->addDays(1);
            }
            $pedidoObj->data_previsao_entrega = $data->format('Y-m-d');
        }
        // if(
        //     $pedidoObj->itens_pedido->sum('valor_total') < $this->frete_fob_valor_minimo &&
        //     $pedidoObj->tipo_frete != 'P'
        // ){
        //     return response()->json([
        //         'status' => 'error', 
        //         'message' => 'Informações inválidas',
        //         'errors' => [
        //             'transportadora_tipo_frete' => 'Valor minimo para frete FOB é R$ '.parserValor($this->frete_fob_valor_minimo)
        //         ],
        //         'reponse' => []
        //     ], 422);
        // }


		if(
			!empty($pedidoObj->cliente_telefone)
		){
			$ClienteEdicaoControllerObj = new ClienteEdicaoController();
			$atulizacao_telefone = $ClienteEdicaoControllerObj->atualizaTefoneCliente($pedidoObj->cliente, $pedidoObj->cliente_telefone);
		}
        if(!in_array($pedidoObj->tipo_venda, $this->tipo_vendas_triangular)){
            $pedidoObj->conta_e_ordem = false;
            $pedidoObj->cod_cliente_conta_e_ordem = '';
        }

        if($pedidoObj->cliente->vendedor_codigo !== $pedidoObj->usuario_detalhes->codigo_representante){
            $validacaoAgenteVenda = UserController::validaAgenteVenda($pedidoObj->cliente->vendedor_codigo);
            if($validacaoAgenteVenda !== true && $validacaoAgenteVenda > 0){
                $pedidoObj->agente_venda_id = $validacaoAgenteVenda;
            }
        }

        $validacaoPromotorVenda = UserController::validaPromotorVenda($pedidoObj->usuario_detalhes->codigo_representante);
        if($validacaoPromotorVenda !== true && $validacaoPromotorVenda > 0){
            if($pedidoObj->cliente->vendedor_codigo !== '001'){
                $user = User::where("codigo_representante", $pedidoObj->cliente->vendedor_codigo)->first();
                $pedidoObj->promotor_venda_id = $user->id;
            }
        }

        if(in_array($pedidoObj->status_pedido, [1, 5, 7, 8, 14, 15, 20])){
            $pedidoObj->motivo_rejeicao = null;
            $pedidoObj->status_pedido = 2;
            $pedidoObj->save();

            Artisan::queue('pedido:validacao', ['pedido' => $pedidoObj->id]);
        }else{
            $pedidoObj->save();
        }

        if(empty($carrinho)){
            if(isset($pedidoObj->carrinhoCompras)){
                $usuarioCarrinhoCompras = CarrinhoCompra::where('created_by', $pedidoObj->carrinhoCompras->created_by)
                ->where('finalizado', false)
                ->count();
    
                if(isset($pedidoObj->carrinhoCompras)){
    
                    if($usuarioCarrinhoCompras == 1){
                        $pedidoObj->carrinhoCompras->dadosClientePedido->deleted_by = Auth::id(); 
                        $pedidoObj->carrinhoCompras->dadosClientePedido->save();
                        $pedidoObj->carrinhoCompras->dadosClientePedido->delete();
                    }
                    $pedidoObj->carrinhoCompras->finalizado = true;
                    $pedidoObj->carrinhoCompras->updated_by = Auth::id();
                    $pedidoObj->carrinhoCompras->save();;
                }
            }
        }
        
        if($pedidoObj->pedido_futuro === true){
            $pedidos_futuros = $this->separacaoPedidoFuturoPorPCMN($pedidoObj);

            return response()
            ->json(
                [
                    'status' => 'success', 
                    'message' => 'Seu(s) pedido(s) está(ão) sendo processado(s) pelo sistema.<br />Pedido(s) gerado(s): ' . $pedidos_futuros . '<br><br><br>',
                    'error' => [],
                    'response' => []
                ],
                200);
        }

        return response()
            ->json(
                [
                    'status' => 'success', 
                    'message' => 'Seu pedido está sendo processado pelo sistema.<br />Pedido gerado: ' . $pedidoObj->id,
                    'error' => [],
                    'response' => []
                ],
                200);
    }

    public function detalhes(Request $request){

        $pedidoObj = PedidoPortal::withTrashed()->with(['cielo', 'itens_pedido', 'itens_pedido.especificacoes', 'itens_pedido.info_produtoNasjon', 'aprovacao', 'aprovacao.aprovador_credito', 'aprovacao.aprovador_preco', 'status_pedido_detalhes', 'detalhesTransportador', 'detalhesTransportadorRedespacho', 'clienteSemBloqueio', 'cliente_conta_e_ordem', 'condicao_pagamento_detalhes', 'usuario_detalhes', 'criadoPor', 'atualizadoPor', 'pedidoRjSp', 'pedidoTransferenciaRjSp', 'pedidoNasajon'])->where('id', $request->pedido_id)->first();

        $estabelecimentos = $this->estabelecimentos;

        $peso_total = 0;
        $valor_total_itens = 0;

        $produto = [];
        $pedido_compras = '';
        $pedido_remessa = '';
        if(!empty($pedidoObj->itens_pedido)){
            foreach($pedidoObj->itens_pedido as $key => $item){
                $promocao = '';
                if($item->preco_promocao === true){
                    $promocao = ' <div><div class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço promocinal" data-content="">*</div></div> ';
                }

                $campanha_informativo = '';

                if(isset($item->campanha)){
                    if($pedidoObj->usuario_detalhes->tipo_usuario_id == 12){
                        if($item->tipo_comissao_campanha == 1){
                            $campanha_informativo = 'Comissão '.$promocao.str_replace('.', ',', $item->comissao_original) . '%'.' + '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                        if($item->tipo_comissao_campanha == 2){
                            $campanha_informativo = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                    }
                    if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [16, 13])){
                        if($item->tipo_comissao_campanha == 1){
                            $campanha_informativo = 'Comissão '.$promocao.str_replace('.', ',', $item->comissao_original) . '%'.' + '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                        if($item->tipo_comissao_campanha == 2){
                            $campanha_informativo = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                    }
                    if(in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [14, 19])){
                        if($item->tipo_comissao_campanha == 1){
                            $campanha_informativo = 'Comissão '.$promocao.str_replace('.', ',', $item->comissao_original) . '%'.' + '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                        if($item->tipo_comissao_campanha == 2){
                            $campanha_informativo = 'Comissão '.parserValor($item->incentivo_campanha).'% incentivo';
                        }
                    }
                }

                $produto[] = [
                    'cod_produto' => $item->cod_produto,
                    'descricao' => $item->especificacoes->descricao,
                    'comissao' => $promocao.str_replace('.', ',', parserValor($item->comissao)) .'%',                
                    'coluna' => $item->coluna,
                    'quantidade' => parserValor($item->quantidade),
                    'peso_total' => parserValor($item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0)) . ' kg',
                    'preco_unitario' => !empty($item->tem_ipi) && intval($pedidoObj->estabelecimento) == 3? parserValor($item->preco_unitario * (1 + ($item->ipi_produto / 100))) : parserValor($item->preco_unitario),
                    'preco_original' => parserValor($item->preco_original),
                    'valor_total' => !empty($item->tem_ipi) && intval($pedidoObj->estabelecimento) == 3? parserValor($item->preco_unitario * (1 + ($item->ipi_produto / 100)) * $item->quantidade) : parserValor($item->preco_unitario * $item->quantidade),
                    'tem_ipi' => $item->tem_ipi,
                    'campanha_nome' => isset($item->campanha) ? $item->campanha->nome : '',
                    'campanha_informativo' => $campanha_informativo
                ];
    
                $peso_total += $item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0);
                $valor_total_itens += !empty($item->tem_ipi) && intval($pedidoObj->estabelecimento) == 3? round($item->preco_unitario * (1 + ($item->ipi_produto / 100)) * $item->quantidade, 2) : round($item->quantidade * $item->preco_unitario, 2);

                $pedido_compras = empty($item->detalhesCompras)? '' : $item->detalhesCompras->numero_pedido;
                $pedido_remessa = empty($item->detalhesRemessa)? '' : $item->detalhesRemessa->pedido_compra_numero;
            }
        }

        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento',
        ];
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_especial);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_interno);
        $status = empty($pedidoObj->pedidoNasajon) || empty($pedidoObj->pedido_gerado)? $pedidoObj->status_pedido_detalhes->status : $pedidoObj->pedidoNasajon->situacao_descricao;
        $deleted_at = '';
        if(!is_null($pedidoObj->deleted_at)){
            $deleted_at = Carbon::parse($pedidoObj->deleted_at)->format('d/m/Y H:i:s');
            $status = 'Cancelado';
        }

        if($pedidoObj->status_pedido == 2){
            if (isset($pedidoObj->aprovacao) && $pedidoObj->aprovacao->credito === true){
                $aprovador_array = [];
                if(is_null($pedidoObj->aprovacao->aprovacao_preco_user_id) && $pedidoObj->aprovacao->preco === true){
                    $aprovador_array[] = $pedidoObj->aprovacao->nivelaprovacaoPreco->descricao;
                }
                if(empty($pedidoObj->aprovacao->aprovacao_credito_user_id) && $pedidoObj->aprovacao->credito === true){
                    $aprovador_array[] = 'Crédito';
                }
                $aprovador = implode('/', $aprovador_array);
            }
            else if(isset($pedidoObj->aprovacao) && !is_null($pedidoObj->aprovacao->nivelaprovacao->descricao)){
                $aprovador = $pedidoObj->aprovacao->nivelaprovacao->descricao;
            }
        }

        $tipo_venda = $tipo_venda_lista[$pedidoObj->tipo_venda];
            
        if($pedidoObj->nasajon === false){
            $transportadora = str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportador->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportador->CGC)));
        }else{
            $transportadora = str_replace(' - __.___.___/____-__', '', empty($pedidoObj->detalhesTransportador)? '' : trim($pedidoObj->detalhesTransportador->nome) . ' - ' . trim($pedidoObj->detalhesTransportador->cnpj));
        }

        $transportadora_redespacho = '';
        if(!empty($pedidoObj->transportadora_redespacho)){
            if($pedidoObj->nasajon === false){
                $transportadora_redespacho = str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->CGC)));
            }else{
                $transportadora_redespacho = '';
                if(!empty(trim($pedidoObj->detalhesTransportadorRedespacho))){
                    $transportadora_redespacho = trim($pedidoObj->detalhesTransportadorRedespacho->nome);   
                    if(!empty(trim($pedidoObj->detalhesTransportadorRedespacho->cnpj))){
                        $transportadora_redespacho .= ' - ' . trim($pedidoObj->detalhesTransportadorRedespacho->cnpj);
                    }
                }
            }
        }

        $cidade_uf = '';
        if($pedidoObj->nasajon === false){
            if(!in_array($pedidoObj->cliente->CODCAD, $this->codigo_cliente_balcao)){
                $cidadeObj = CepEndereco::where('cep', str_replace('-', '', str_replace('_', '', $pedidoObj->cliente['CEP'])))->first();

                if(!empty($cidadeObj)){
                    $cidade_uf = $cidadeObj->cidadeBusca->cidade . ' - ' . $cidadeObj->cidadeBusca->uf;
                }
                else{
                    $cidade_uf = '';
                }
            }
            $cliente_nome = $pedidoObj->cliente->NOME;
        }
        else{
            if(!in_array($pedidoObj->clienteSemBloqueio->codigo, $this->codigo_cliente_balcao)){
                $cidade_uf = $pedidoObj->clienteSemBloqueio->cidade . ' - ' . $pedidoObj->clienteSemBloqueio->uf;
            }
            $cliente_nome = $pedidoObj->clienteSemBloqueio->nome . ' - ' . $pedidoObj->clienteSemBloqueio->cpf_cnpj;
        }
        
        $cliente_conta_e_ordem_nome = '';
        if($pedidoObj->nasajon === false){
            $cliente_conta_e_ordem_nome = $pedidoObj->cliente_conta_e_ordem['NOME'];
        }else{
            if(!empty($pedidoObj->cliente_conta_e_ordem)){
                $cliente_conta_e_ordem_nome = $pedidoObj->cliente_conta_e_ordem->nome . ' - ' . $pedidoObj->cliente_conta_e_ordem->cpf_cnpj;
            }
        }
        
		if(boolval($pedidoObj->pedido_futuro) == true){
			$tipo_venda = $tipo_venda . ' - Pedido Futuro';
		}else{
			$tipo_venda = $tipo_venda . ' - Pronta Entrega';
        }

		if(boolval($pedidoObj->rj_x_sp) == true){
			$tipo_venda .= ' - Operação RJ/SP';
		}

		if(boolval($pedidoObj->bionexo) == true){
			$tipo_venda = $tipo_venda . ' - <b>Bionexo</b>';
        }
        $link_pagamento = '';
        $tipo_pagamento = '';
		$motivo_recusa = '';
		$retorno_log_pagarme = '';
        if(
            $pedidoObj->cartao == true
        ){
			if(
				$pedidoObj->presencial == false
			){
				$tipo_pagamento = 'Venda não presencial';
				if(isset($pedidoObj->cielo) && $pedidoObj->cielo->isNotEmpty() && is_null($pedidoObj->deleted_at)){
					if(!empty($pedidoObj->cielo) && $pedidoObj->status_pedido != 7){
						$pedidoObj->cielo->each(function($cielo) use(&$link_pagamento, &$status, &$motivo_recusa, &$verifica_pagamento){
							if($cielo->cielo_status_id == 1|| $cielo->cielo_status_id == 8){
								if(empty($link_pagamento) || $link_pagamento == 'pago'){
									if(!empty($cielo->pedido_nasajon_id)){
										if($cielo->pago != true){
											$link_pagamento = route("pedido_online.pagamento_restante", ["token" => $cielo->link_pedido]);
										} else {
											$link_pagamento = 'pago';
										}
									} else {
										if($cielo->pago != true){
											$link_pagamento = route("pedido_online.pagamento", ["token" => $cielo->link_pedido]);
										}else{
											$link_pagamento = 'pago';
										}
									}
								}
								if($cielo->cielo_status_id == 8){
									$status = "Recusado - ".$status;
									$erro = $cielo->erros->first();
                                    if(!empty($erro)){
                                        switch($erro->json_retorno['transaction']['refuse_reason']){
                                            case 'antifraud':
                                                $motivo_recusa = 'Antifraude';
                                            break;
                                            case 'acquirer':
                                                $PagarmeCodigoErroObj = PagarmeCodigoErro::where('codigo', $erro->json_retorno['transaction']['acquirer_response_code'])->first();
                                                $motivo_recusa = $PagarmeCodigoErroObj->descricao;
                                            break;
                                            
                                        }
                                    }									
								}
							} elseif($cielo->cielo_status_id == 4){
								if(empty($link_pagamento)){
									$link_pagamento = 'pago';
								}
							}
						});

						$pedidoObj->cielo->each(function($cielo) use(&$link_pagamento, &$status, &$motivo_recusa, &$retorno_log_pagarme){
							$cielo->logEnvio->each(function($log) use (&$retorno_log_pagarme){
								$retorno_log_pagarme .= $log->datahora_envio->format('d/m/y H:i') . ' - ' . $log->descricao . '<br />';
							});
						});
					}
				}
			} else {
				$tipo_pagamento = 'Venda presencial';
				if(!empty($pedidoObj->cielo) && $pedidoObj->status_pedido != 7){
					$pedidoObj->cielo->each(function($cielo) use(&$link_pagamento){
						if($cielo->cielo_status_id == 1){
							if($cielo->pago == true){
								$link_pagamento = 'pago';
							} else {
								$link_pagamento = '';
							}
						}
					});
				}
			}
		}

        $credito_do_cliente = 0;
        $exibir_credito = false;

        if($pedidoObj->status_pedido == 9 && in_array($pedidoObj->condicao_pagamento, $this->condicao_de_pagamento_usar_credito)){
            $query_credito = NotasCreditoReceberNasajon::select();
            $query_credito->where('cod_cliente', $pedidoObj->cod_cliente);
            $result_credito = $query_credito->get();
            
            $credito_do_cliente = $query_credito->sum('valor');
            $exibir_credito = true;
        }
		$observacao = nl2br($pedidoObj->observacao)??'&nbsp;';
		if(!empty($pedidoObj->pedidoRjSp)){
			$observacao .= "Pedido transferência: ".$pedidoObj->pedidoRjSp->pedido_transferencia_id;
		}
		if(!empty($pedidoObj->pedidoTransferenciaRjSp)){
			$observacao .= "Pedido Operação RJ/SP: ".$pedidoObj->pedidoTransferenciaRjSp->pedido_id;
		}

        $pedido_assinatura = '';

        $ClicksignDocumentoObj = ClicksignDocumento::select()->where('caminho_arquivo', 'ilike', '%/'.$pedidoObj->id.'/%')->first();
        if(!empty($ClicksignDocumentoObj)){
            if($ClicksignDocumentoObj->status == 'closed'){
				$pedido_assinatura = Storage::url(substr_replace($ClicksignDocumentoObj->caminho_arquivo, '_assinado.pdf', -4));
			}
        }

        if($pedidoObj->status_pedido == 14){
            $link_proposta = route("pedido_portal.aprovacao_proposta",$this->encriptaLinkProposta($pedidoObj->id));
        }else{
            $link_proposta = '';
        }

        $response = [
            'id' => $pedidoObj->id,
            'tipo_venda' => $tipo_venda,
            'estabelecimento' => $estabelecimentos[$pedidoObj->estabelecimento],
            'cliente' => [
                'codigo' => $pedidoObj->cod_cliente,
                'nome' => $cliente_nome,
                'cidade_uf' => $cidade_uf
            ],
            'condicao_pagamento_descr' => empty($pedidoObj->condicao_pagamento_detalhes['descricao'])? '' : $pedidoObj->condicao_pagamento_detalhes['descricao'],
            'transportadora' => [
                'nome' => $transportadora,
                'tipo_frete' => parserFrete($pedidoObj->tipo_frete),
                'valor_frete' => parserValor($pedidoObj->valor_frete)??''
            ],
            'transportadora_redespacho' =>[
                'nome' => $transportadora_redespacho,
                'tipo_frete' => parserFrete($pedidoObj->tipo_frete_redespacho),
                'valor_frete' => parserValor($pedidoObj->valor_frete_redespacho)??''
            ],
            'frete_preco' => strtoupper($pedidoObj->frete_preco),
            'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
            'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
            'pedido_futuro' => $pedidoObj->pedido_futuro,
            'conta_e_ordem' => $pedidoObj->conta_e_ordem?'Sim':'Não',
            'cliente_conta_e_ordem' => $cliente_conta_e_ordem_nome,
            'data_pedido' => date("d/m/Y", strtotime($pedidoObj->data_pedido)),
            'vendedor' => $pedidoObj->usuario_detalhes->codigo_representante . ' - ' . $pedidoObj->usuario_detalhes->name,
            'supervisor' => empty($pedidoObj->usuario_detalhes->supervisor)? '' : $pedidoObj->usuario_detalhes->supervisor->name,
            'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? date("d/m/Y", strtotime($pedidoObj->data_previsao_entrega)) : '&nbsp;',
            'observacao' => $observacao,
            'itens' => $produto??[],
            'aprovacao' => $aprovador??'',
            'peso_total' => parserValor($peso_total) . ' kg',
            'valor_total_itens' => parserValor($valor_total_itens),
            'valor_desconto' => parserValor($pedidoObj->valor_desconto),
            'valor_frete' => parserValor($pedidoObj->valor_frete),
            'valor_total_nota' => parserValor($valor_total_itens + ($pedidoObj->tipo_frete == 'C'?$pedidoObj->valor_frete:0) - $pedidoObj->valor_desconto),
            'pedido_gerado' => $pedidoObj->pedido_gerado,

            'aprovador_credito' => $pedidoObj->aprovacao->aprovador_credito->name??'',
            'aprovador_preco' => $pedidoObj->aprovacao->aprovador_preco->name??'',
            'created_at' => $pedidoObj->created_at->format('d/m/Y H:i:s'),
            'created_by' => $pedidoObj->criadoPor->name,
            'updated_at' => $pedidoObj->updated_at->format('d/m/Y H:i:s'),
            'updated_by' => $pedidoObj->atualizadoPor->name??'',
            'status' => $status,
            'nasajon' => $pedidoObj->nasajon,
            'tipo_venda' => $tipo_venda,
            'no_pedido_compra' => $pedidoObj->no_pedido_compra,
            'agente_venda' => !empty($pedidoObj->agente_venda_id) ? $pedidoObj->agenteVendas->name : '',
            'link_pagamento' => $link_pagamento,
            'tipo_pagamento' => $tipo_pagamento,

            'deleted_at' => $deleted_at,
            'deleted_by' => $pedidoObj->excluidoPor->name??'',
            'credito_do_cliente' => $credito_do_cliente <= 0? '' : parserValor($credito_do_cliente),
            'status_numero' => $pedidoObj->status_pedido,
            'exibir_credito' => $exibir_credito,
            'metragem_exata' => $pedidoObj->metragem_exata,
			'motivo_recusa' => $motivo_recusa,
			'retorno_log_pagarme' => $retorno_log_pagarme,
            'pedido_assinatura' => $pedido_assinatura,
            'pedido_compras' => $pedido_compras,
            'pedido_remessa' => $pedido_remessa,
            'link_proposta' => $link_proposta,
        ];

        return view("programs.pedido_portal.modal.detalhe")->with(['pedido' => $response]);

    }

    public function detalhesDeslogado(Request $request, $hash){
        try{
            $pedido = decrypt($hash);
        }catch(\Exception $e){
            return abort(404);
		}
        $estabelecimentos = returnEmpresasNasajonView();

        $pedidoObj = PedidoPortal::with(['condicao_pagamento_detalhes', 'itens_pedido', 'cliente', 'usuario_detalhes', 'itens_pedido.especificacoes', 'detalhesTransportador', 'status_pedido_detalhes'])->where('id', $pedido['pedido_id'])->get()->first();

        if (empty($pedidoObj)){
            return abort(404);
        }

        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento'
        ];

        if($pedidoObj->nasajon){
            if(!empty($pedidoObj->pedido_venda)){
                $PedidoVendaObj = PedidosVendaNasajon::with(['itens_pedido', 'vendedor_detalhes', 'cliente_detalhes', 'forma_pagamento', 'nota'])
                    ->where("numero", $pedidoObj->pedido_gerado)
                    ->where('estabelecimento_codigo', str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT))
                    ->where('operacao_codigo', $pedidoObj->codigo_operacao)
                    ->first();

                foreach($PedidoVendaObj->itens_pedido as $key => $item_produto){
                    $produto_especificacao = ProdutoEspecificacao::where('codigo_produto',$item_produto->produto_codigo)->first();
                    $produto[] = [
                        'grupo' => $produto_especificacao->grupo,
                        'cod_produto' => $item_produto->produto_codigo,
                        'descricao' => $produto_especificacao->descricao,
                        'marca' => $produto_especificacao->marca,
                        'linha' => $produto_especificacao->linha,
                        'quantidade' => $item_produto->quantidadecomercial,
                        'preco_unitario' => ($item_produto->valortotal / $item_produto->quantidadecomercial),
                        'valor_total' => $item_produto->valortotal,
                    ];
                }
                $response = [
                    'id' => $PedidoVendaObj->numero,
                    'vendedor' => empty($PedidoVendaObj->vendedor_detalhes)? '': ($PedidoVendaObj->vendedor_detalhes->codigo) . ' - ' . ($PedidoVendaObj->vendedor_detalhes->nome),
                    'status' =>[
                        "id" => '',
                        "descricao" => $PedidoVendaObj->situacao_descricao
                    ],
                    'pedido_futuro' => '',
                    'estabelecimento' => $estabelecimentos[intval($PedidoVendaObj->estabelecimento_codigo)],
                    'cod_cliente' => $PedidoVendaObj->cliente_detalhes->codigo,
                    'cliente' => ($PedidoVendaObj->cliente_detalhes->nome),
                    'cpf_cnpj' => $PedidoVendaObj->cliente_detalhes->cpf_cnpj,
                    'condicao_pagamento_descr' => empty($PedidoVendaObj->forma_pagamento)? ($pedidoObj->condicao_pagamento_detalhes->descricao) : ($PedidoVendaObj->forma_pagamento->formapagamento_descricao),
                    'transportadora' =>
                    [
                        'nome' => !empty($PedidoVendaObj->transportadora_nome) ? str_replace(' - __.___.___/____-__', '', ($PedidoVendaObj->transportadora_nome)) : '',
                        'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete],
                        'valor_frete' => parserValor($PedidoVendaObj->frete)
                    ],
                    'tipo_frete' => $tipo_frete[$pedidoObj->frete],
                    'nome_comprador' => $PedidoVendaObj->nome_comprador??'&nbsp;',
                    'email_comprador' => $PedidoVendaObj->email_comprador??'&nbsp;',
                    'data_pedido' => date("d/m/Y", strtotime($PedidoVendaObj->emissao)),
                    'data_previsao_entrega' => !empty($PedidoVendaObj->dataprevisao_entradasaida) ? date("d/m/Y", strtotime($PedidoVendaObj->dataprevisao_entradasaida)) : '&nbsp;',
                    'observacao' => ($PedidoVendaObj->anotacoes_manuais)??'&nbsp;',
                    'itens' => $produto,
                    'valor_total_itens' => empty($PedidoVendaObj->nota)? parserValor($PedidoVendaObj->valor - (empty($pedidoObj->valor_desconto)? 0.0 : $pedidoObj->valor_desconto) - $pedidoObj->valor_seguro - $pedidoObj->valor_frete) :parserValor($PedidoVendaObj->nota->total_produto_sem_desconto),
                    'valor_desconto' => empty($PedidoVendaObj->nota)? empty($pedidoObj->valor_desconto)? '0,00' : parserValor($pedidoObj->valor_desconto):parserValor($PedidoVendaObj->nota->total_desconto),
                    'valor_frete' => parserValor($PedidoVendaObj->frete),
                    'valor_total_nota' => parserValor($PedidoVendaObj->valor),
                ];
            }else{
                $total = 0;
                $pedidoObj->itens_pedido->each(function($item) use(&$produto, &$total, $pedidoObj){
                    $produto[] = [
                        'grupo' => $item->especificacoes->grupo,
                        'cod_produto' => $item->cod_produto,
                        'descricao' => $item->especificacoes->descricao,
                        'marca' => $item->especificacoes->marca,
                        'linha' => $item->especificacoes->linha,
                        'quantidade' => $item->quantidade,
                        'preco_unitario' => $pedidoObj->estabelecimento === 3 && $item->tem_ipi?  ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) : $item->preco_unitario,
                        'valor_total' => $pedidoObj->estabelecimento === 3 && $item->tem_ipi? ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) * $item->quantidade : $item->valor_total,
                    ];

                    $valor_total_item = $pedidoObj->estabelecimento === 3 && $item->tem_ipi? ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) * $item->quantidade : $item->valor_total;
                    $total = $total + $valor_total_item;
                });
                $response = [
                    'id' => $pedidoObj->id,
                    'vendedor' => empty($pedidoObj->usuario_detalhes)? '': ($pedidoObj->usuario_detalhes->codigo_representante) . ' - ' . ($pedidoObj->usuario_detalhes->name),
                    'status' =>[
                        "id" => '',
                        "descricao" => $pedidoObj->status_pedido_detalhes->status
                    ],
                    'pedido_futuro' => '',
                    'estabelecimento' => $estabelecimentos[intval($pedidoObj->estabelecimento)],
                    'cod_cliente' => $pedidoObj->cliente->codigo,
                    'cliente' => ($pedidoObj->cliente->nome),
                    'cpf_cnpj' => $pedidoObj->cliente->cpf_cnpj,
                    'condicao_pagamento_descr' => $pedidoObj->condicao_pagamento_detalhes->descricao ?? '',
                    'transportadora' =>
                    [
                        'nome' => $pedidoObj->detalhesTransportador->nome .' - '. $pedidoObj->detalhesTransportador->cnpj,
                        'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete],
                        'valor_frete' => parserValor($pedidoObj->valor_frete)
                    ],
                    'tipo_frete' => $tipo_frete[$pedidoObj->frete],
                    'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
                    'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
                    'data_pedido' => Carbon::parse($pedidoObj->data_pedido)->format('d/m/Y'),
                    'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? Carbon::parse($pedidoObj->data_previsao_entrega)->format('d/m/Y') : '&nbsp;',
                    'observacao' => ($pedidoObj->observacao)??'&nbsp;',
                    'itens' => $produto,
                    'valor_total_itens' => parserValor($total),
                    'valor_desconto' => parserValor(0),
                    'valor_frete' => parserValor($pedidoObj->frete_preco),
                    'valor_total_nota' => parserValor($pedidoObj->valor_total_nota),
                ];
            }
        }else{
            return abort(404);
        }

        return view("programs.pedido_portal.visualizar-cliente")->with(['pedido' => $response]);
    }

    public function imprimir(Request $request){
        $field = $request->only(["pedido"]);
        $pedidoObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes')->withTrashed()->find($field['pedido']);
        if(is_null($pedidoObj)){
            return abort(404);
        }
        $estabelecimentos = returnEmpresasNasajonView();

        $produto = [];
        $total_itens_pedido = 0;

        foreach($pedidoObj->itens_pedido as $key => $value){
            $total_itens_pedido += round($value->quantidade * $value->preco_unitario, 2);
            $produto[] = [
                'grupo' => $value->especificacoes->grupo,
                'cod_produto' => $value->cod_produto,
                'descricao' => $value->especificacoes->descricao,
                'marca' => $value->especificacoes->marca,
                'linha' => $value->especificacoes->linha,
                'comissao' => str_replace('.', ',', $value->comissao) .'%',
                'coluna' => $value->coluna,
                'quantidade' => parserValor($value->quantidade),
                'preco_unitario' => parserValor($value->preco_unitario),
                'valor_total' => parserValor($value->quantidade * $value->preco_unitario),
            ];
        }
        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento',
            'pronta_entrega_venda' => 'Pronta Entrega',
            'pronta_entrega_triangular' => 'Pronta Entrega - Triangular',
            'pedido_futuro_venda' => 'Pedido Futuro',
            'pedido_futuro_triangular' => 'Pedido Futuro - Triangular',
            'producao' => 'Produção',
            'producao_triangular' => 'Produção - Triangular',
            'pre_pago_producao' => 'Pré-Pago - Produção',
            'pre_pago_producao_triangular' => 'Pré-Pago - Produção - Triangular',
            'pre_pago' => 'Pré-Pago - Pronta Entrega',
            'pre_pago_triangular' => 'Pré-Pago - Pronta Entrega - Triangular',
            'pre_pago_futuro' => 'Pré-Pago - Pedido Futuro',
            'pedido_orgaopublico' => 'Pedido Orgão Publico',
			'rj_x_sp' => 'Operação RJ/SP',
			'rj_x_sp_triangular' => 'Operação RJ/SP - Triangular',
            'pre_pago_rj_x_sp' => 'Pré-Pago - Operação RJ/SP ',
            'rj_x_sp_futuro' => 'Operação RJ/SP - Pedido Futuro',
            'rj_x_sp_triangular_futuro' => 'Operação RJ/SP - Triangular - Pedido Futuro',
            'pre_pago_rj_x_sp_futuro' => 'Pré-Pago - Operação RJ/SP  - Pedido Futuro',
        ];
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_especial);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_interno);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas);
        $nome_cliente = '';
        $transportadora = '';
        $transportadora_redespacho = '';
        if($pedidoObj->nasajon === false){
            $nome_cliente = $pedidoObj->cliente['NOME'];
            $transportadora = (!empty($pedidoObj->detalhesTransportador) ? (str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportador->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportador->CGC)))):'');
            $transportadora_redespacho = !is_null($pedidoObj->detalhesTransportadorRedespacho) ? str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->CGC))) : '';
            $cliente_conta_e_ordem = '';
        }else{
            $nome_cliente = $pedidoObj->cliente->nome . ' - ' . $pedidoObj->cliente->cpf_cnpj;
            $transportadora = (!empty($pedidoObj->detalhesTransportador) ? (str_replace(' - __.___.___/____-__', '', trim(($pedidoObj->detalhesTransportador->nome)) . ' - ' . trim(($pedidoObj->detalhesTransportador->cnpj)))):'');

            $transportadora_redespacho = !is_null($pedidoObj->detalhesTransportadorRedespacho) ? str_replace(' - __.___.___/____-__', '', trim(($pedidoObj->detalhesTransportadorRedespacho->nome)) . ' - ' . trim($pedidoObj->detalhesTransportadorRedespacho->cnpj)) : '';
            $cliente_conta_e_ordem = '';
            if(!empty($pedidoObj->cliente_conta_e_ordem)){
                $cliente_conta_e_ordem = $pedidoObj->cliente_conta_e_ordem->nome . ' - ' . $pedidoObj->cliente_conta_e_ordem->cpf_cnpj;
            }
        }
        $response = [
            'id' => $pedidoObj->id,
            'tipo_venda' => $tipo_venda_lista[$pedidoObj->tipo_venda],
            'estabelecimento' => $estabelecimentos[$pedidoObj->estabelecimento],
            'cliente' => [
                'codigo' =>$pedidoObj->cod_cliente,
                'nome' => $nome_cliente
            ],
            'condicao_pagamento_descr' => $pedidoObj->condicao_pagamento_detalhes['descricao'],
            'transportadora_redespacho' =>[
                'nome' => $transportadora_redespacho,
                'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete_redespacho],
                'valor_frete' => parserValor($pedidoObj->valor_frete_redespacho)??''
            ],
            'transportadora' => [
                'nome' => $transportadora,
                'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete],
                'valor_frete' => parserValor($pedidoObj->valor_frete)??''
            ],
            'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
            'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
            'pedido_futuro' => $pedidoObj->pedido_futuro?'Sim':'Não',
            'conta_e_ordem' => $pedidoObj->conta_e_ordem?'Sim':'Não',
            'cliente_conta_e_ordem' => $cliente_conta_e_ordem,
            'data_pedido' => date("d/m/Y", strtotime($pedidoObj->data_pedido)),
            'vendedor' => $pedidoObj->usuario_detalhes->codigo_representante . ' - ' . $pedidoObj->usuario_detalhes->name,
            'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? date("d/m/Y", strtotime($pedidoObj->data_previsao_entrega)) : '&nbsp;',
            'observacao' => $pedidoObj->observacao??'&nbsp;',
            'itens' => $produto,
            'aprovacao' => $pedidoObj->aprovacao['nivelaprovacao']['descricao'] ?? '',
            'valor_total_itens' => parserValor($total_itens_pedido),
            'valor_desconto' => parserValor($pedidoObj->valor_desconto),
            'valor_frete' => parserValor($pedidoObj->valor_frete),
            'valor_total_nota' => parserValor($total_itens_pedido + ($pedidoObj->tipo_frete == 'C'?$pedidoObj->valor_frete:0) - $pedidoObj->valor_desconto),
            'pedido_gerado' => $pedidoObj->pedido_gerado,
            'no_pedido_compra' => $pedidoObj->no_pedido_compra
        ];

        return view("programs.pedido_portal.imprimir")->with(['pedido' => $response]);
    }


    public function duplicar(Request $request){
        $field = $request->only(['pedido']);
        $PedidoPortalObj = PedidoPortal::withTrashed()->find($field['pedido']);
        if(is_null($PedidoPortalObj)){
            return abort(404);
        }
        $pedido = $PedidoPortalObj;
        if(isset($PedidoPortalObj->cliente->CODCAD)){
            $clienteNasajon = ClienteNasajon::where('cpf_cnpj', $PedidoPortalObj->cliente->CGC_CPF)->first();
            if(!is_null($clienteNasajon)){
                $codigo_cliente = $clienteNasajon->codigo;
                $nome_cliente = $clienteNasajon->nome.' - '.$clienteNasajon->cpf_cnpj;
            }else{
                $codigo_cliente = '';
                $nome_cliente = '';
            }
        }else{
            if(!empty($PedidoPortalObj->cliente)){
                $codigo_cliente = $PedidoPortalObj->cliente->codigo;
                $nome_cliente = $PedidoPortalObj->cliente->nome.' - '.$PedidoPortalObj->cliente->cpf_cnpj;
            }else{
                $codigo_cliente = '';
                $nome_cliente = '';
            }
        }
        $data_pedido = '';
        if(!empty($PedidoPortalObj->data_pedido)){
            $data_pedido = Carbon::parse($PedidoPortalObj->data_pedido)->format('d/m/Y');
        }
        $futuro = $PedidoPortalObj->pedido_futuro;
        return view('programs.pedido_portal.duplicar')->with(['pedido' => $pedido, 'codigo_cliente' => $codigo_cliente, 'nome_cliente' => $nome_cliente,'data_pedido' => $data_pedido,'futuro' => $futuro]);
    }

    public function duplicarSalvar(PedidoPortalDuplicarRequest $request){
        set_time_limit(3600);
        ini_set('memory_limit','4096M');
        $fields = $request->only(['pedido', 'codigo_cliente_duplicar', 'verificar_produto_sem_estoque','previsao_entrega']);

        if(!isset($fields['verificar_produto_sem_estoque'])){
            $verificar_produto_sem_estoque = true;
        }else{
            $verificar_produto_sem_estoque = false;
        }

        $PedidoPortalBuscaObj = PedidoPortal::withTrashed()->with('itens_pedido', 'itens_pedido.especificacoes')->find($fields['pedido'])->toArray();
        $PedidoPortalNewObj = new PedidoPortal;
        $PedidoPortalNewObj->status_pedido = 1;
        $PedidoPortalNewObj->cod_cliente = $fields['codigo_cliente_duplicar'];
        $PedidoPortalNewObj->created_by = Auth::id();

        foreach($PedidoPortalBuscaObj as $key => $value){
            if(!in_array($key, ['id', 'status_pedido', 'cod_cliente', 'itens_pedido', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'motivo_rejeicao', 'pedido_gerado', 'data_previsao_entrega', 'bionexo'])){
                $PedidoPortalNewObj->$key = $value;
            }
        }

        $PedidoPortalNewObj->data_pedido = date('Y-m-d');
        $PedidoPortalNewObj->nasajon = true;

        if($PedidoPortalBuscaObj['nasajon'] == false){

            if(!empty($PedidoPortalBuscaObj['condicao_pagamento'])){
                $condicaoPagamentoAntiga = CondicoesPagamentoWeb::find($PedidoPortalBuscaObj['condicao_pagamento']);
                $condicaoPagamentoNova = CondicoesPagamentoWeb::where('descricao', $condicaoPagamentoAntiga->descricao)->where('nasajon', true)
                ->where('ativo', true)->first();
            }
            
            if($PedidoPortalBuscaObj['transportadora']){

                $transportadorAntigo = Transportador::find(str_pad($PedidoPortalBuscaObj['transportadora'], 4, '0', STR_PAD_LEFT));                
                $transportadorNovo = TransportadorNasajon::where('cnpj', $transportadorAntigo->CGC)->first();
            }

            if($PedidoPortalBuscaObj['transportadora_redespacho']){
                $transportadorRedespachoAntigo = Transportador::find(str_pad($PedidoPortalBuscaObj['transportadora_redespacho'], 4, '0', STR_PAD_LEFT));
                $transportadorRedespachoNovo = TransportadorNasajon::where('cnpj', $transportadorRedespachoAntigo->CGC)->first();
            }
            
            if(!empty($condicaoPagamentoNova) && !is_null($condicaoPagamentoNova)){
                $PedidoPortalNewObj->condicao_pagamento = $condicaoPagamentoNova->id;
            }
            
            if(!empty($transportadorNovo) && !is_null($transportadorNovo)){
                $PedidoPortalNewObj->transportadora = $transportadorNovo->codigo;
            }
    
            if(!empty($transportadorRedespachoNovo) && !is_null($transportadorRedespachoNovo)){
                $PedidoPortalNewObj->transportadora_redespacho = $transportadorRedespachoNovo->codigo;
            }
        }else{
            $verificacao_transportadora = TransportadoraEstabelecimento::select()->where('transportadora_codigo', $PedidoPortalNewObj->transportadora)->where('estabelecimento', str_pad($PedidoPortalNewObj->estabelecimento, 2, 0, STR_PAD_LEFT))->first();
            if(empty($verificacao_transportadora)){
                $PedidoPortalNewObj->transportadora = '';
                $PedidoPortalNewObj->save();
            }
        }
        $erro_estoque = [];
        $ProdutoControllerObj = new ProdutoController();
        foreach($PedidoPortalBuscaObj['itens_pedido'] as $item){
            $produtoObj = ProdutoEspecificacao::where('codigo_produto', $item['cod_produto'])->first();
            $estoque = $ProdutoControllerObj->retornarDadosEstoqueNasajon($produtoObj,$PedidoPortalBuscaObj['estabelecimento'], new PedidoPortal, false);
            if ($PedidoPortalNewObj->pedido_futuro === true){
                $PedidoPortalNewObj->data_previsao_entrega = Carbon::createFromFormat('d/m/Y',$fields['previsao_entrega'])->format('Y-m-d');
                $coluna_quinzena = date('\k_Y_m_', strtotime($PedidoPortalNewObj->data_previsao_entrega)) . (date('j', strtotime($PedidoPortalNewObj->data_previsao_entrega)) <= 15? "1": "2");
                if (str_replace(',', '.', str_replace('.', '', $estoque['quinzenas'][$coluna_quinzena]['quantidade'])) <= 0){
                    $erro_estoque[$item['especificacoes']['codigo_produto']] = $item['especificacoes']['codigo_produto']. ' - '.$item['especificacoes']['descricao'];
                }
                else if (str_replace(',', '.', str_replace('.', '', $estoque['quinzenas'][$coluna_quinzena]['quantidade'])) < $item['quantidade']){
                    $erro_estoque[$item['especificacoes']['codigo_produto']] = $item['especificacoes']['codigo_produto']. ' - '.$item['especificacoes']['descricao'];
                }
            }
            else if($PedidoPortalNewObj->pedido_futuro === false){
                if((str_replace(',', '.', str_replace('.', '', $estoque['pronta_entrega']))) <= 0){
                    $erro_estoque[$item['especificacoes']['codigo_produto']] = $item['especificacoes']['codigo_produto']. ' - '.$item['especificacoes']['descricao'];
                }
                else if((str_replace(',', '.', str_replace('.', '', $estoque['pronta_entrega'])))  < $item['quantidade'] && $PedidoPortalNewObj->pedido_futuro === false){
                    $erro_estoque[$item['especificacoes']['codigo_produto']] = $item['especificacoes']['codigo_produto']. ' - '.$item['especificacoes']['descricao'];
                }
            }
        }
        
        $produtos_sem_estoque = [];
        if(!empty($erro_estoque) && $verificar_produto_sem_estoque){
            if(count($erro_estoque) == count($PedidoPortalBuscaObj['itens_pedido'])){
                $continuar = false;
                $message = 'Não há produto contem estoque disponivel para que possa ser duplicado. Os produtos do pedido:<br>';
                foreach($erro_estoque as $erro){
                    $message .= $erro.'<br>';
                }
            }else{
                $continuar = true;
                $message = 'Os produtos abaixo não contem estoque disponivel e não serão duplicado:<br>';
                foreach($erro_estoque as $erro){
                    $message .= $erro.'<br>';
                }
                $message .= '<br>';
                $message .= 'Deseja continuar? <br>';
            }
            $message .= '<br><br>';

            return response()
                ->json(
                    [
                        'status' => 'error', 
                        'message' => $message,
                        'error' => [],
                        'response' => ['continuar' => $continuar]
                    ],
                    422);
        }else if(!empty($erro_estoque)){
            foreach($erro_estoque as $codigo_produto_erro => $erro){
                $produtos_sem_estoque[] = $codigo_produto_erro;
            }
        }

        $PedidoPortalNewObj->save();

        $historicoPedidoObj = new HistoricoPedido();
        $historicoPedidoObj->pedido = $PedidoPortalNewObj->id;
        $historicoPedidoObj->natureza = 'duplicado';
        $historicoPedidoObj->novo = 'pedido original: '.$fields['pedido'];
        $historicoPedidoObj->created_by = Auth::id();
        $historicoPedidoObj->save();

        $preco_frete = $this->fretePreco($PedidoPortalNewObj);
        $frete = $preco_frete['preco'];
        $estadoObj = CepEstado::find($PedidoPortalNewObj->cliente->uf);

        switch ($PedidoPortalNewObj->estabelecimento) {
            case "03":
            case 3:
                $origem  = "RO";
                break;
            case "04":
            case 4:
                $origem = "TO";
                break;
            default:
                $origem = 'SP';
                break;
        }
        $paramostr_preco = [];
        $paramostr_preco['origem'] = $origem;
        $paramostr_preco['estado'] = $PedidoPortalNewObj->cliente->uf;
        if ($PedidoPortalNewObj->pedido_futuro === false || intval($PedidoPortalNewObj->estabelecimento) != 3){
            $paramostr_preco['moeda'] = 'real';
        }
        else if($PedidoPortalNewObj->pedido_futuro === true && intval($PedidoPortalNewObj->estabelecimento) == 3){
            $paramostr_preco['moeda'] = 'dolar';
        }
        $paramostr_preco['promocao'] = false;
        $paramostr_preco['cliente'] = $PedidoPortalNewObj->cod_cliente;
        $paramostr_preco['codigo_vendedor'] = $PedidoPortalNewObj->usuario_detalhes->codigo_representante;
        $paramostr_preco['estabelecimento'] = $PedidoPortalNewObj->estabelecimento;

        $paramostr_preco['frete'] = $frete;
        $paramostr_preco['regiao'] = $estadoObj->regiao;
        $paramostr_preco['tipo_cliente'] = (
            $PedidoPortalNewObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($PedidoPortalNewObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($PedidoPortalNewObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        
        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $PedidoPortalNewObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $paramostr_preco['prazo_medio'] = 0;
            $preco_custo = true;
        } else {
            $paramostr_preco['prazo_medio'] = $PedidoPortalNewObj->condicao_pagamento_detalhes->media ?? 0;
        }
        $paramostr_preco['estabelecimento'] = $PedidoPortalNewObj->estabelecimento;

        $listaDePrecosObj = new ListagemDePrecosController();

        foreach($PedidoPortalBuscaObj['itens_pedido'] as $item){
            if(!in_array($item['cod_produto'], $produtos_sem_estoque)){
                $paramostr_preco['produto'] = $item['cod_produto'];
                $filter_request = new ListaDePrecosRequest($paramostr_preco);
                $lista_preco_result = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false);
                $lista_preco_result = reset($lista_preco_result);
    
                $PedidoItemPortal = new PedidoItemPortal();
                $PedidoItemPortal->pedido = $PedidoPortalNewObj->id;
                $PedidoItemPortal->usuario = $item['usuario'];
                $PedidoItemPortal->cod_produto = $item['cod_produto'];
                $PedidoItemPortal->quantidade = $item['quantidade'];
                $PedidoItemPortal->preco_unitario = $preco_custo? parserNumber($lista_preco_result['custo']) : parserNumber($lista_preco_result['coluna_a']);
                $PedidoItemPortal->valor_icms = $item['valor_icms'];
                $PedidoItemPortal->base_calculo_icms = $item['base_calculo_icms'];
                $PedidoItemPortal->valor_ipi = $item['valor_ipi'];
                $PedidoItemPortal->aliquota_icms = $item['aliquota_icms'];
                $PedidoItemPortal->aliquota_ipi = $item['aliquota_ipi'];
                $PedidoItemPortal->valor_frete = $item['valor_frete'];
                $PedidoItemPortal->valor_total = $item['valor_total'];
                $PedidoItemPortal->coluna = $item['coluna'];
                if($preco_custo !== true){
                    $PedidoItemPortal->comissao = $PedidoPortalNewObj->usuario_detalhes->comissao_a;
                }else{
                    $PedidoItemPortal->comissao = 0;
                }
    
                $PedidoItemPortal->coluna_a = parserNumber($lista_preco_result['coluna_a']);
                $PedidoItemPortal->coluna_b = parserNumber($lista_preco_result['coluna_b']);
                $PedidoItemPortal->coluna_c = parserNumber($lista_preco_result['coluna_c']);
                $PedidoItemPortal->preco_base = $item['preco_base'];
                $PedidoItemPortal->ipi_produto = $item['ipi_produto'];
    
                $PedidoItemPortal->created_by = Auth::id();
                $PedidoItemPortal->save();
            }
        }
        
        $this->condicaoPagamentoCielo = $this->getIdCondicoesPagamentoCartcao();
        if(in_array($PedidoPortalNewObj->condicao_pagamento, $this->condicaoPagamentoCielo)){
            $PedidoPortalNewObj->cartao = true;
            if(in_array($PedidoPortalNewObj->estabelecimento, $this->estabelecimentos_venda_presencial_cartao)){
                $PedidoPortalNewObj->presencial = true;
            }else{
                $PedidoPortalNewObj->presencial = false;
            }
            $PedidoPortalNewObj->save();
        }else{
            $PedidoPortalNewObj->presencial = false;
            $PedidoPortalNewObj->cartao = false;
            $PedidoPortalNewObj->save();
        }

        $message = 'O pedido foi duplicado , os valores dos itens foram atualizados, o pedido esta em digitação é necessario verificação de todos os itens!';
        if($PedidoPortalNewObj->cartao === true){
            $message .= "<br>Pedido gerado para pagamento ";
            if($PedidoPortalNewObj->presencial !== true){
                $message .= "não presencial";
            }else{
                $message .= "presencial";
            }
        }
        return response()
            ->json(
                [
                    'status' => 'success', 
                    'message' => $message,
                    'error' => [],
                    'response' => []
                ],
                200);
    }

    public function fretePreco(PedidoPortal $PedidoPortal){
        $return = [
            'preco' => '',
            'frete' => ''
        ];

        if (!empty($PedidoPortal->transportadora_redespacho)){

            $return['preco'] = 'FOB';

            if ($PedidoPortal->tipo_frete =='P'){
                $return['frete'] = 'CIF';
            }
            else{
                $return['frete'] = 'FOB';
            }
        }
        else if(!empty($PedidoPortal->tipo_frete) && empty($PedidoPortal->transportadora_redespacho)){
            if(empty($PedidoPortal->cod_cliente_conta_e_ordem)){
                $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::where('uf', $PedidoPortal->cliente->uf);
                $estabelecimentoCidadeFobObj->where('cidade', $PedidoPortal->cliente->cidade);
                if(!empty($PedidoPortal->estabelecimento)){
                    if($PedidoPortal->rj_x_sp == true){
                        $estabelecimentoCidadeFobObj->where('estabelecimento', '07');
                    }else{
                        $estabelecimentoCidadeFobObj->where('estabelecimento', str_pad($PedidoPortal->estabelecimento, 2, 0, STR_PAD_LEFT));
                    }                    
                }
                $estabelecimentoCidadeFobObj = $estabelecimentoCidadeFobObj->first();
            }else{
                $estabelecimentoCidadeFobObj = EstabelecimentoCidadeFob::where('uf', $PedidoPortal->cliente_conta_e_ordem->uf);
                $estabelecimentoCidadeFobObj->where('cidade', $PedidoPortal->cliente_conta_e_ordem->cidade);
                if(!empty($PedidoPortal->estabelecimento)){
                    if($PedidoPortal->rj_x_sp == true){
                        $estabelecimentoCidadeFobObj->where('estabelecimento', '07');
                    }else{
                        $estabelecimentoCidadeFobObj->where('estabelecimento', str_pad($PedidoPortal->estabelecimento, 2, 0, STR_PAD_LEFT));
                    }                    
                }
                $estabelecimentoCidadeFobObj = $estabelecimentoCidadeFobObj->first();
            }

            if ($PedidoPortal->tipo_frete =='P' && is_null($estabelecimentoCidadeFobObj))  {
                $return['frete'] = 'CIF';
                $return['preco'] = 'CIF';
            }
            else if ($PedidoPortal->tipo_frete == 'P' && !is_null($estabelecimentoCidadeFobObj))  {
                $return['frete'] = 'CIF';
                $return['preco'] = 'FOB';
            }
            else{
                $return['frete'] = 'FOB';
                $return['preco'] = 'FOB';
            }
        }
        return $return;
    }


    public function gerarPedidoProgramadoAtravesProjeto(LancamentoProjeto $Projeto){
        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;

        $pedido_existe = PedidoPortal::select();
        $pedido_existe->where('projeto_id', $Projeto->id);
        $pedido_existe = $pedido_existe->first();

        if(empty($pedido_existe)){
            $natop = "";

            $tipo_venda = 'pedido_futuro_venda';
    
            $transportadora = PedidoPortal::select('transportadora')->where('cod_cliente', $Projeto->cliente->codigo)->first();
            
            if(empty($transportadora)){
                $transportadora = TransportadorNasajon::select('codigo')->where('nome', 'RETIRA')->first()->codigo;
            }else{
                $transportadora = $transportadora->transportadora;
            }
    
            if(empty($Projeto->estabelecimento)){
                $estabelecimento = 4;
                $Projeto->estabelecimento = 4;
                $Projeto->save();
            }else{
                $estabelecimento = $Projeto->estabelecimento;
            }
    
            $pedidoPortalObj = new PedidoPortal;
            $pedidoPortalObj->data_pedido = date('Y-m-d');
            $pedidoPortalObj->usuario = $Projeto->detalhes_representante->id;
            $pedidoPortalObj->cod_cliente = $Projeto->cliente->codigo;
            $pedidoPortalObj->nome_comprador = substr($Projeto->cliente->nome, 0, 49);
            $pedidoPortalObj->email_comprador = substr($Projeto->cliente->email, 0, 49);
            $pedidoPortalObj->status_pedido = 8;
            $pedidoPortalObj->estabelecimento = $Projeto->estabelecimento;
            $pedidoPortalObj->pedido_futuro = true;
            $pedidoPortalObj->condicao_pagamento = $Projeto->condicoes_pagamento_web_id;
            $pedidoPortalObj->data_previsao_entrega = $Projeto->data_previsao_entrega;
            $pedidoPortalObj->tipo_frete = 'P';
            $pedidoPortalObj->observacao = "Pedido gerado automaticamente pelo projeto: ". $Projeto->id." - ".$Projeto->nome_projeto;
            $pedidoPortalObj->transportadora = $transportadora;
            $pedidoPortalObj->comissao = 0;
            $pedidoPortalObj->created_by = Auth::id();
            $pedidoPortalObj->base_icms = 0;
            $pedidoPortalObj->valor_icms = 0;
            $pedidoPortalObj->base_icmsst = 0;
            $pedidoPortalObj->valor_icmsst = 0;
            $pedidoPortalObj->valor_frete = 0;
            $pedidoPortalObj->valor_seguro = 0;
            $pedidoPortalObj->valor_desconto = 0;
            $pedidoPortalObj->outros_valores = 0;
            $pedidoPortalObj->valor_ipi = 0;
            $pedidoPortalObj->valor_total_produtos = 0;
            $pedidoPortalObj->valor_total_nota = 0;
            $pedidoPortalObj->codigo_operacao = $natop;
            $pedidoPortalObj->tipo_venda = $tipo_venda;
            $pedidoPortalObj->nasajon = true;
            $pedidoPortalObj->projeto_id = $Projeto->id;
            $pedidoPortalObj->no_pedido_compra = $Projeto->pedido;
            $pedidoPortalObj->save();
    
        
            $pedido = $pedidoPortalObj->id;
    
            switch(strtoupper($Projeto->estabelecimento)){
                case 5:
                    $origem = "SP";
                    break;
                default:
                    $origem = "TO";
                    break;
            }
    
            switch(strtoupper($Projeto->cliente->inscricaoestadual)){
                case "INSENTA":
                    $tipo_cliente = "isento";
                    break;
                case "INSENTO":
                    $tipo_cliente = "isento";
                    break;
                default:
                    $tipo_cliente = "normal";
                    break;
            }
    
            $return = [];
    
            if ($Projeto->condicoes_pagamento_web->media < 15){
                $coluna = 'prazo_vista';
            }
            else if($Projeto->condicoes_pagamento_web->media >= 15 && $Projeto->condicoes_pagamento_web->media < 30){
                $coluna = 'prazo_15';
            }
            else if($Projeto->condicoes_pagamento_web->media >= 30 && $Projeto->condicoes_pagamento_web->media < 45){
                $coluna = 'prazo_30';
            }
            else if($Projeto->condicoes_pagamento_web->media >= 45 && $Projeto->condicoes_pagamento_web->media < 60){
                $coluna = 'prazo_45';
            }
            else if($Projeto->condicoes_pagamento_web->media >= 60){
                $coluna = 'prazo_60';
            }
            unset($arr);
    
            $arr['origem'] = $origem;
            
            $arr['moeda'] = 'real';
            $arr['prazo_medio'] = $Projeto->condicoes_pagamento_web->media;
            $arr['frete'] = strtolower($Projeto->tipo_frete);
            $arr['estado'] = $Projeto->cliente->uf;
            $arr['tipo_cliente'] = $tipo_cliente;
            $arr['coluna'] = $coluna;
            $arr['estabelecimento'] = $Projeto->estabelecimento;
            foreach($Projeto['itens'] as $produto){
                $arr['produto'] = $produto->codigo_produto;
    
                $precoObj = new ListagemDePrecosController;
        
                $items = new ListaDePrecosRequest($arr);
        
                $precos = $precoObj->filter($items, false, false, true, true, false, false, false);
    
                $pedido_item = new PedidoItemPortal;
    
                $pedido_item->pedido = $pedido;
                $pedido_item->usuario = Auth::user()->id;
                $pedido_item->cod_produto = strtoupper($produto->codigo_produto);
                $pedido_item->quantidade = $produto->quantidade;
                $pedido_item->preco_unitario = $produto->preco_venda;
                $pedido_item->created_by = Auth::id();
                $pedido_item->valor_icms = 0;
                $pedido_item->base_calculo_icms = 0;
                $pedido_item->valor_ipi = 0;
                $pedido_item->aliquota_icms = 0;
                $pedido_item->aliquota_ipi = 0;
                $pedido_item->valor_frete = 0;
                $pedido_item->valor_total = round($produto->preco_venda * $produto->quantidade,2);
                $pedido_item->coluna = '0';
                $pedido_item->comissao = $Projeto->comissao;
                $pedido_item->ipi_produto = 0;
                $pedido_item->preco_base = empty($produto->produto_detalhes->preco)? $produto->preco_venda : $produto->produto_detalhes->preco->preco_real;
                $pedido_item->coluna_a = empty($precos[0])? $produto->preco_venda : floatval(str_replace(",", ".", str_replace(".", "", $precos[0]['coluna_a'])));
                $pedido_item->coluna_b = empty($precos[0])? $produto->preco_venda : floatval(str_replace(",", ".", str_replace(".", "", $precos[0]['coluna_b'])));
                $pedido_item->coluna_c = empty($precos[0])? $produto->preco_venda : floatval(str_replace(",", ".", str_replace(".", "", $precos[0]['coluna_c'])));
                //$pedido_item->numero_compra = $produto->servico_detalhes->pedido_compras_gerado_nasajon; 
    
                $pedido_item->save();
    
            }
    
            $Projeto->pedido_id = $pedido;
            $Projeto->save();
    
            $historicoProjetoObj = new HistoricoProjeto();
            $historicoProjetoObj->lancamento_projetos_id = $Projeto->id;
            $historicoProjetoObj->natureza = 'pedido_venda_programado';
            $historicoProjetoObj->motivo = 'Pedido: '.$pedido;
            $historicoProjetoObj->users_id = Auth::id();
            $historicoProjetoObj->created_by = Auth::id();
            $historicoProjetoObj->save();
        }
    }


    public function detalhesProgramado(Request $request){

        if(
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false ||
            strpos(strtolower(Auth::user()->tipo_usuario->nome), "administrador") !== false
        ){
            $pedidoObj = PedidoPortal::withTrashed()->where('id', $request->pedido_id)->first();
        }else{
            $pedidoObj = PedidoPortal::with(['detalhesTransportador'])->where('id', $request->pedido_id)->first();
        }

        $estabelecimentos = returnEmpresasNasajonView();

        $peso_total = 0;
        $valor_total_itens = 0;

        $produto = [];
        if(!empty($pedidoObj->itens_pedido)){
            foreach($pedidoObj->itens_pedido as $key => $value){
                $infoProduto = ProdutoEspecificacao::select()->where('codigo_produto', strtoupper($value->cod_produto))->first();
                $estoque = ProdutosEstoque::select()->where('codigo_produto', strtoupper($value->cod_produto))->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT))->first();
    
                $PedidosVendaNasajon = PedidosReservaProdutoNasajon::where('codigo_produto', $value->cod_produto)->where('codigo_estabelecimento', str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT))->first();
                $promocao = '';
                if($value->preco_promocao === true){
                    $promocao = ' <div><div class="text-danger" data-toggle="tooltip" data-placement="left" data-html="true" title="Preço promocinal" data-content="">*</div></div> ';
                }
                $linha = '';
                $estoque_disponivel = 0;
                if(!empty($estoque)){
                    $estoque_disponivel = $estoque->estoque;
                }
                if(!empty($PedidosVendaNasajon)){
                    $estoque_disponivel -= $PedidosVendaNasajon->quantidade;
                }
                if($value->quantidade > $estoque_disponivel){
                    $linha = 'error-tr';
                }
                $produto[] = [
                    'codigo' => $value->cod_produto,
                    'descricao' => $infoProduto->descricao,
                    'comissao' => $promocao.str_replace('.', ',', $value->comissao) .'%',                
                    'coluna' => $value->coluna,
                    'quantidade' => parserValor($value->quantidade),
                    'estoque' => parserValor($estoque_disponivel),
                    'peso_total' => parserValor($value->quantidade * (!empty($value->info_produtoNasjon->pesoliquido) ? $value->info_produtoNasjon->pesoliquido : 0)) . ' kg',
                    'preco_unitario' => parserValor($value->preco_unitario),
                    'valor_total' => parserValor($value->preco_unitario * $value->quantidade),
                    'linha' => $linha
                ];
    
                $peso_total += $value->quantidade * (!empty($value->info_produtoNasjon->pesoliquido) ? $value->info_produtoNasjon->pesoliquido : 0);
                $valor_total_itens += round($value->quantidade * $value->preco_unitario, 2);
            }
        }
        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento',
            'pronta_entrega_venda' => 'Pronta Entrega',
            'pronta_entrega_triangular' => 'Pronta Entrega - Triangular',
            'pedido_futuro_venda' => 'Pedido Futuro',
            'pedido_futuro_triangular' => 'Pedido Futuro - Triangular',
            'producao' => 'Produção',
            'producao_triangular' => 'Produção - Triangular',
            'pre_pago_producao' => 'Pré-Pago - Produção',
            'pre_pago_producao_triangular' => 'Pré-Pago - Produção - Triangular',
            'pre_pago' => 'Pré-Pago - Pronta Entrega',
            'pre_pago_triangular' => 'Pré-Pago - Pronta Entrega - Triangular',
            'pre_pago_futuro' => 'Pré-Pago - Pedido Futuro',
            'pedido_orgaopublico' => 'Pedido Orgão Publico',
			'rj_x_sp' => 'Operação RJ/SP',
			'rj_x_sp_triangular' => 'Operação RJ/SP - Triangular',
            'pre_pago_rj_x_sp' => 'Pré-Pago - Operação RJ/SP ',
        ];
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_especial);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_interno);
        $status = $pedidoObj->status_pedido_detalhes->status;
        if(!is_null($pedidoObj->deleted_at)){
            $status = 'Cancelado';
        }

        if($pedidoObj->status_pedido == 2){
            if (isset($pedidoObj->aprovacao) && $pedidoObj->aprovacao->credito === true){
                $aprovador_array = [];
                if(is_null($pedidoObj->aprovacao->aprovacao_preco_user_id) && $pedidoObj->aprovacao->preco === true){
                    $aprovador_array[] = $pedidoObj->aprovacao->nivelaprovacaoPreco->descricao;
                }
                if(empty($pedidoObj->aprovacao->aprovacao_credito_user_id) && $pedidoObj->aprovacao->credito === true){
                    $aprovador_array[] = 'Crédito';
                }
                if(empty($pedidoObj->aprovacao->user_aprovacao_pre_1auth) && empty($pedidoObj->aprovacao->user_aprovacao_pre_2auth) && $pedidoObj->aprovacao->retaguarda === true){
                    $aprovador_array[] = 'Retaguarda';
                }
                $aprovador = implode('/', $aprovador_array);
            }
            else if(isset($pedidoObj->aprovacao) && !is_null($pedidoObj->aprovacao->nivelaprovacao->descricao)){
                $aprovador = $pedidoObj->aprovacao->nivelaprovacao->descricao;
            }
        }

		if($pedidoObj->estabelecimento == 1 && $pedidoObj->cod_cliente == '0050758840002' || $pedidoObj->estabelecimento == 2 && $pedidoObj->cod_cliente == '0050758840001'){
            $tipo_venda = "Transferência entre unidades";		
		}
        else{
            $tipo_venda = $tipo_venda_lista[$pedidoObj->tipo_venda];
        }

        if($pedidoObj->nasajon === false){
            $transportadoraObj = Transportador::find(str_pad($pedidoObj->transportadora, 4, "0", STR_PAD_LEFT));
            $transportadora = str_replace(' - __.___.___/____-__', '', trim(utf8_encode($transportadoraObj->NOME)) . ' - ' . trim(utf8_encode($transportadoraObj->CGC)));
        }else{
            $transportadora = str_replace(' - __.___.___/____-__', '', empty($pedidoObj->detalhesTransportador)? '' : trim($pedidoObj->detalhesTransportador->nome) . ' - ' . trim($pedidoObj->detalhesTransportador->cnpj));
        }

        $transportadora_redespacho = '';
        if(!empty($pedidoObj->transportadora_redespacho)){
            if($pedidoObj->nasajon === false){
                $transporadoraRedespachoObj = Transportador::find(str_pad($pedidoObj->transportadora_redespacho, 4, "0", STR_PAD_LEFT));
                $transportadora_redespacho = str_replace(' - __.___.___/____-__', '', trim(utf8_encode($transporadoraRedespachoObj->NOME)) . ' - ' . trim(utf8_encode($transporadoraRedespachoObj->CGC)));
            }else{
                $transportadora_redespacho = '';
                if(!empty(trim($pedidoObj->detalhesTransportadorRedespacho))){
                    $transportadora_redespacho = trim($pedidoObj->detalhesTransportadorRedespacho->nome);   
                }
                if(!empty(trim($pedidoObj->detalhesTransportadorRedespacho->cnpj))){
                    $transportadora_redespacho .= ' - ' . trim($pedidoObj->detalhesTransportadorRedespacho->cnpj);
                }
            }
        }

        $cidade_uf = '';
        if(isset($pedidoObj->cliente->CODCAD)){
            if(!in_array($pedidoObj->cliente->CODCAD, $this->codigo_cliente_balcao)){
                $cidadeObj = CepEndereco::where('cep', str_replace('-', '', str_replace('_', '', $pedidoObj->cliente['CEP'])))->first();

                if(!empty($cidadeObj)){
                    $cidade_uf = $cidadeObj->cidadeBusca->cidade . ' - ' . $cidadeObj->cidadeBusca->uf;
                }
                else{
                    $cidade_uf = '';
                }
            }
            $cliente_nome = $pedidoObj->cliente->NOME;
        }
        else{
            if(!in_array($pedidoObj->cliente->codigo, $this->codigo_cliente_balcao)){
                $cidade_uf = $pedidoObj->cliente->cidade . ' - ' . $pedidoObj->cliente->uf;
            }
            $cliente_nome = $pedidoObj->cliente->nome . ' - ' . $pedidoObj->cliente->cpf_cnpj;
        }
        
        $cliente_conta_e_ordem_nome = '';
        if(isset($pedidoObj->cliente_conta_e_ordem->CODCAD)){
            $cliente_conta_e_ordem_nome = $pedidoObj->cliente_conta_e_ordem['NOME'];
        }else{
            if(!empty($pedidoObj->cliente_conta_e_ordem)){
                $cliente_conta_e_ordem_nome = $pedidoObj->cliente_conta_e_ordem->nome . ' - ' . $pedidoObj->cliente_conta_e_ordem->cpf_cnpj;
            }
        }
        
		if(boolval($pedidoObj->pedido_futuro) == true){
			$tipo_venda = $tipo_venda . ' - Pedido Futuro';
		}else{
			$tipo_venda = $tipo_venda . ' - Pronta Entrega';
        }

        $credito_do_cliente = 0;
        $exibir_credito = false;
        if($pedidoObj->status_pedido == 9 && in_array($pedidoObj->condicao_pagamento, $this->condicao_de_pagamento_usar_credito)){
            $query_credito = NotasCreditoReceberNasajon::select();
            $query_credito->where('cod_cliente', $pedidoObj->cod_cliente);
            $result_credito = $query_credito->get();

            $credito_do_cliente = $query_credito->sum('valor');
            $exibir_credito = true;
        }
		
		if(boolval($pedidoObj->rj_x_sp) == true){
			$tipo_venda .= ' - Operação RJ/SP';
		}
		$observacao = nl2br($pedidoObj->observacao)??'&nbsp;';
		if(!empty($pedidoObj->pedidoRjSp)){
			$observacao .= "Pedido transferência: ".$pedidoObj->pedidoRjSp->pedido_transferencia_id;
		}
		if(!empty($pedidoObj->pedidoTransferenciaRjSp)){
			$observacao .= "Pedido Operação RJ/SP: ".$pedidoObj->pedidoTransferenciaRjSp->pedido_id;
		}
        
        $response = [
            'id' => $pedidoObj->id,
            'tipo_venda' => $tipo_venda,
            'estabelecimento' => $estabelecimentos[$pedidoObj->estabelecimento],
            'cliente' => [
                'codigo' => $pedidoObj->cod_cliente,
                'nome' => $cliente_nome,
                'cidade_uf' => $cidade_uf
            ],
            'condicao_pagamento_descr' => $pedidoObj->condicao_pagamento_detalhes['descricao'],
            'transportadora' => [
                'nome' => $transportadora,
                'tipo_frete' => parserFrete($pedidoObj->tipo_frete),
                'valor_frete' => parserValor($pedidoObj->valor_frete)??''
            ],
            'transportadora_redespacho' =>[
                'nome' => $transportadora_redespacho,
                'tipo_frete' => parserFrete($pedidoObj->tipo_frete_redespacho),
                'valor_frete' => parserValor($pedidoObj->valor_frete_redespacho)??''
            ],
            'frete_preco' => strtoupper($pedidoObj->frete_preco),
            'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
            'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
            'pedido_futuro' => $pedidoObj->pedido_futuro,
            'conta_e_ordem' => $pedidoObj->conta_e_ordem?'Sim':'Não',
            'cliente_conta_e_ordem' => $cliente_conta_e_ordem_nome,
            'data_pedido' => date("d/m/Y", strtotime($pedidoObj->data_pedido)),
            'vendedor' => $pedidoObj->usuario_detalhes->codigo_representante . ' - ' . $pedidoObj->usuario_detalhes->name,
            'supervisor' => empty($pedidoObj->usuario_detalhes->supervisor)? '' : $pedidoObj->usuario_detalhes->supervisor->name,
            'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? date("d/m/Y", strtotime($pedidoObj->data_previsao_entrega)) : '&nbsp;',
            'observacao' => $observacao,
            'itens' => $produto??[],
            'aprovacao' => $aprovador??'',
            'peso_total' => parserValor($peso_total) . ' kg',
            'valor_total_itens' => parserValor($valor_total_itens),
            'valor_desconto' => parserValor($pedidoObj->valor_desconto),
            'valor_frete' => parserValor($pedidoObj->valor_frete),
            'valor_total_nota' => parserValor($valor_total_itens + ($pedidoObj->tipo_frete == 'C'?$pedidoObj->valor_frete:0) - $pedidoObj->valor_desconto),
            'pedido_gerado' => $pedidoObj->pedido_gerado,

            'aprovador_credito' => $pedidoObj->aprovacao->aprovador_credito->name??'',
            'aprovador_preco' => $pedidoObj->aprovacao->aprovador_preco->name??'',
            'created_at' => date('d/m/Y H:i:s', strtotime($pedidoObj->created_at)),
            'created_by' => $pedidoObj->criadoPor->name,
            'updated_at' => date('d/m/Y H:i:s', strtotime($pedidoObj->updated_at)),
            'updated_by' => $pedidoObj->atualizadoPor->name??'',
            'status' => $status,
            'nasajon' => $pedidoObj->nasajon,
            'tipo_venda' => $tipo_venda,
            'no_pedido_compra' => $pedidoObj->no_pedido_compra,
            'credito_do_cliente' => $credito_do_cliente <= 0? '' : $credito_do_cliente,
            'status_numero' => $pedidoObj->status_pedido,
            'exibir_credito' => $exibir_credito,
        ];

        return view("programs.pedido_portal.modal.detalhe_futuro")->with(['pedido' => $response]);

    }

    public function modalEdicaoFuturo(Request $request){
        $field = $request->only(['id']);

        $PedidoPortalObj = PedidoPortal::with(['itens_pedido', 'itens_pedido.especificacoes','cliente', 'cliente_conta_e_ordem', 'condicao_pagamento_detalhes', 'detalhesTransportador', 'detalhesTransportadorRedespacho'])->find($field['id']);

        if(empty($PedidoPortalObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado',
                'error' => [],
                'response' => []
            ], 422);
        }
        if($PedidoPortalObj->status_pedido != 8){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não está mais para aprovação',
                'error' => [],
                'response' => []
            ], 422);
        }

        $cliente = '';
        $cliente_destino = '';
        if(!empty($PedidoPortalObj->cliente)){
            $cliente = $PedidoPortalObj->cliente->nome;
            $cliente_destino = $PedidoPortalObj->cliente->cidade . ' / ' . $PedidoPortalObj->cliente->uf;
            if(!empty(trim(str_replace(['-', '.', '/'], '', $PedidoPortalObj->cliente->cpf_cnpj)))){
                $cliente .= ' - '.$PedidoPortalObj->cliente->cpf_cnpj;
            }
        }
        $cliente_conta_ordem = '';
        if(!empty($PedidoPortalObj->cliente_conta_e_ordem)){
            $cliente_conta_ordem = $PedidoPortalObj->cliente_conta_e_ordem->nome;
            if(!empty(trim(str_replace(['-', '.', '/'], '', $PedidoPortalObj->cliente_conta_e_ordem->cpf_cnpj)))){
                $cliente_conta_ordem .= ' - '.$PedidoPortalObj->cliente_conta_e_ordem->cpf_cnpj;
            }
        }
        $condicao_pagamento = '';
        if(!empty($PedidoPortalObj->condicao_pagamento_detalhes)){
            $condicao_pagamento = $PedidoPortalObj->condicao_pagamento_detalhes->descricao;
        }
        $previsao_entrega = parserData($PedidoPortalObj->data_previsao_entrega);

        $transportadora = ['codigo' => '', 'nome' => ''];
        if(!empty($PedidoPortalObj->detalhesTransportador)){
            $transportadora['nome'] = trim($PedidoPortalObj->detalhesTransportador->nome);
            $transportadora['codigo'] = $PedidoPortalObj->detalhesTransportador->codigo;
            $transportadora['nome'] .= ' - '.$PedidoPortalObj->detalhesTransportador->cnpj;
        }

        $transportadora_redespacho = ['codigo' => '', 'nome' => ''];
        if(!empty($PedidoPortalObj->detalhesTransportadorRedespacho)){
            $transportadora_redespacho['nome'] = trim($PedidoPortalObj->detalhesTransportadorRedespacho->nome);
            $transportadora_redespacho['codigo'] = $PedidoPortalObj->detalhesTransportadorRedespacho->codigo;
            $transportadora_redespacho['nome'] .= ' - '.$PedidoPortalObj->detalhesTransportadorRedespacho->cnpj;
        }

        $itens = [];

        foreach($PedidoPortalObj->itens_pedido as $item){
            $estoque = '';
            $linha = '';
            $estoque = ProdutosEstoque::where('codigo_produto', strtoupper($item->cod_produto))->where('estabelecimento', $PedidoPortalObj->estabelecimento_pad)->first();
            $PedidosVendaNasajon = PedidosReservaProdutoNasajon::where('codigo_produto', strtoupper($item->cod_produto))->where('codigo_estabelecimento', $PedidoPortalObj->estabelecimento_pad)->first();

            $estoque_disponivel = 0;
            if(!empty($estoque)){
                $reserva = $estoque->reserva - $item->quantidade < 0 ? $item->quantidade : $estoque->reserva - $item->quantidade;
                $estoque_disponivel = $estoque->estoque - $reserva < 0? 0 : $estoque->estoque - $reserva;
            }
            if(!empty($PedidosVendaNasajon)){
                $estoque_disponivel -= $PedidosVendaNasajon->quantidade;
            }
            $estoque_compra = 0;
            $ComprasNasajonObj = ComprasNasajon::query()->
                where('numero_pedido', $item->numero_compra)->
                where('estabelecimento', $PedidoPortalObj->estabelecimento_pad)->
                where('cod_produto', $item->cod_produto)->
                whereIn('situacao', ['Aguardando Documento', 'Parcialmente Liquidado'])->
                first();
            if(!empty($ComprasNasajonObj)){

                $queryFuturo = PedidoPortal::select('id','estabelecimento'); 
                $queryFuturo->where('pedido_futuro', 'true');
                $queryFuturo->where('status_pedido', 8);
                $queryFuturo->where('estabelecimento', $PedidoPortalObj->estabelecimento);
                $queryFuturo->where('id', '!=', $PedidoPortalObj->id);
                $queryFuturo->with(['itens_pedido' => function($query) use($item){
                    $query->select('pedido','cod_produto', 'quantidade');
                    $query->where('cod_produto', $item->cod_produto);
                    $query->where('numero_compra', $item->numero_compra);
                }]);
                $queryFuturo->whereHas('itens_pedido', function($query) use($item){
                    $query->where('cod_produto', $item->cod_produto);
                    $query->where('numero_compra', $item->numero_compra);
                });

                $resultFuturo = $queryFuturo->get()->toArray();
                $quantidade_produto_futuro = 0.0;
                foreach($resultFuturo as $pedido){
                    if(!empty($pedido['itens_pedido'])){
                        $quantidade_produto_futuro += $pedido['itens_pedido'][0]['quantidade']; 
                    }
                }
                if($quantidade_produto_futuro > floatval($ComprasNasajonObj->quantidade)){
                    $quantidade_produto_futuro = floatval($ComprasNasajonObj->quantidade);
                }
                $estoque_compra = floatval($ComprasNasajonObj->quantidade) - $quantidade_produto_futuro;
            }

            if(
                $estoque_disponivel > 0 &&
                $item->quantidade > $estoque_disponivel
            ){
                $linha = 'error-tr';
            }
            $item_retorno = [
                'codigo' => $item->cod_produto,
                'descricao' => $item->especificacoes->descricao,
                'estoque_pronta_entrega' => parserQtd($estoque_disponivel),
                'estoque_compra' => parserQtd($estoque_compra),
                'quantidade' => parserQtd($item->quantidade),
                'id' => $item->id,
                'linha' => $linha,
                'preco_unitario' => parserValor($item->preco_unitario),
                'preco_unitario_total' => parserValor($item->preco_unitario * $item->quantidade),
            ];
            $itens[] = $item_retorno;
            unset($item_retorno);
        }
        $tipo_venda_lista = [];
        if($PedidoPortalObj->rj_x_sp){
            $tipo_venda_lista['rj_x_sp_futuro'] = 'Operação RJ/SP - Pedido Futuro';
            $tipo_venda_lista['rj_x_sp_triangular_futuro'] = 'Operação RJ/SP - Triangular - Pedido Futuro';
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $PedidoPortalObj->cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $tipo_venda_lista['pre_pago_rj_x_sp_futuro'] = 'Pré-Pago - Operação RJ/SP  - Pedido Futuro';
            }
        }else if(in_array($PedidoPortalObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $tipo_venda_lista['producao'] = 'Produção';
            $tipo_venda_lista['producao_triangular'] = 'Produção - Triangular';
            
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $PedidoPortalObj->cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $tipo_venda_lista['pre_pago_producao'] = 'Pré-Pago - Produção';
                $tipo_venda_lista['pre_pago_producao_triangular'] = 'Pré-Pago - Produção - Triangular';
            }
        }else{
            $tipo_venda_lista['pedido_futuro_venda'] = 'Pedido Futuro';
            $tipo_venda_lista['pedido_futuro_triangular'] = 'Pedido Futuro - Triangular';
            
            $cliente_prepago = ClientePrePago::where('cpf_cnpj', $PedidoPortalObj->cliente->cpf_cnpj)->count();
            if($cliente_prepago > 0){
                $tipo_venda_lista['pre_pago_futuro'] = 'Pré-Pago - Pedido Futuro';
            }
        }
        

        $cliente_conta_e_ordem_codigo = '';
        $cliente_conta_e_ordem_descricao = '';

        if(!is_null($PedidoPortalObj->cod_cliente_conta_e_ordem)){
            $ClienteContaEOrdem = ClienteNasajon::where('codigo', $PedidoPortalObj->cod_cliente_conta_e_ordem)->where('bloqueado', 'false')->first();
            if(!is_null($ClienteContaEOrdem)){
                $cliente_conta_e_ordem_codigo = $ClienteContaEOrdem->codigo;
                $cliente_conta_e_ordem_descricao = trim($ClienteContaEOrdem->nome) . " - " . $ClienteContaEOrdem->cpf_cnpj;
                unset($ClienteContaEOrdem);
            }
        }
        
        $preco_frete = $this->fretePreco($PedidoPortalObj);
        $preco_cif_fob = $preco_frete['preco'];
        $frete = $preco_frete['frete'];

        $response = [
            'estabelecimento' => $this->estabelecimentos[intval($PedidoPortalObj->estabelecimento)],
            'cliente' => $cliente,
            'cliente_conta_ordem' => $cliente_conta_ordem,
            'cliente_destino' => $cliente_destino,
            'condicao_pagamento' => $condicao_pagamento,
            'previsao_entrega' => $previsao_entrega,
            'transportadora' => $transportadora,
            'transportadora_redespacho' => $transportadora_redespacho,
            'observacao' => $PedidoPortalObj->observacao,
            'nome_comprador' => $PedidoPortalObj->nome_comprador,
            'email_comprador' => $PedidoPortalObj->email_comprador,
            'no_pedido_compra' => $PedidoPortalObj->no_pedido_compra,
            'itens' => $itens,
            'id' => encrypt($PedidoPortalObj->id),
            'tipo_venda_lista' => $tipo_venda_lista,
            'tipo_venda' => $PedidoPortalObj->tipo_venda,
            'cliente_conta_e_ordem_codigo' => $cliente_conta_e_ordem_codigo,
            'cliente_conta_e_ordem_descricao' => $cliente_conta_e_ordem_descricao,
            'tipo_frete_list' => $this->tipo_frete,
            'tipo_frete' => $PedidoPortalObj->tipo_frete,
            'preco_cif_fob' => $preco_cif_fob,
            'tipo_frete_redespacho' => $PedidoPortalObj->tipo_frete_redespacho,
        ];

        return view("programs.pedido_portal.modal.edicao_futuro")->with(['pedido' => $response]);

    }

    public function salvarFuturo(PedidoPortalEdicaoFuturaRequest $request){
        $field = $request->only(['pedido', 'item', 'preco_unitario', 'transportadora_nome', 'transportadora', 'transportadora_redespacho_nome', 'transportadora_redespacho', 'nome_comprador', 'email_comprador', 'no_pedido_compra', 'observacao', 'cliente_nome', 'condicao_pagamento', 'tipo_venda', 'codigo_cliente_conta_e_ordem', 'nome_cliente_conta_e_ordem', 'transportadora_tipo_frete', 'transportadora_redespacho', 'transportadora_redespacho_tipo_frete']);
        $id = '';
        try{
            $id = decrypt($field['pedido']);
        } catch (Exception $e) {
            return response()
                ->json(
                    [
                        'status' => 'error', 
                        'message' => 'Ocorreu uma instabilidade. Tente novamente',
                        'error' => [],
                        'response' => []
                    ],
                422);
        }

        $PedidoPortalObj = PedidoPortal::find($id);

        if(empty($PedidoPortalObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado',
                'error' => [],
                'response' => []
            ], 422);
        }
        if($PedidoPortalObj->status_pedido != 8){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não está mais para aprovação',
                'error' => [],
                'response' => []
            ], 422);
        }

        $transportadora = '';
        $transportadora_redespacho = '';
        if(!empty($field['transportadora_nome'])){
            $transportadoraObj = TransportadorNasajon::whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj))  ilike '".trim($field['transportadora_nome'])."'")->first();
            $transportadora = $transportadoraObj->codigo;
            unset($transportadoraObj);
        }
        if(!empty($field['transportadora_redespacho_nome'])){
            $transportadoraObj = TransportadorNasajon::whereRaw("TRIM(CONCAT(TRIM(nome), ' - ', cnpj))  ilike '".trim($field['transportadora_redespacho_nome'])."'")->first();
            $transportadora_redespacho = $transportadoraObj->codigo;
            unset($transportadoraObj);
        }
        $query_cliente = ClienteNasajon::select();
        $query_cliente->where(DB::raw('TRIM(CONCAT(nome,\' - \', cpf_cnpj))'), 'ILIKE', trim($field['cliente_nome']));
        $query_cliente->where('bloqueado', false);
        $result_cliente = $query_cliente->first();

        $query_pagamento = CondicoesPagamentoWeb::select();
        $query_pagamento->where('descricao', 'ilike', $field['condicao_pagamento']);
        $query_pagamento->where('nasajon', true);
        $query_pagamento->where('ativo', true);
        $result_pagamento = $query_pagamento->first();

        $PedidoPortalObj->transportadora = $transportadora;
        $PedidoPortalObj->transportadora_redespacho = $transportadora_redespacho;
        $PedidoPortalObj->nome_comprador = $field['nome_comprador'];
        $PedidoPortalObj->email_comprador = $field['email_comprador'];
        $PedidoPortalObj->no_pedido_compra = $field['no_pedido_compra'];
        $PedidoPortalObj->observacao = $field['observacao'];
        $PedidoPortalObj->cod_cliente = $result_cliente->codigo;
        $PedidoPortalObj->nome_comprador = $result_cliente->nome;
        $PedidoPortalObj->condicao_pagamento = $result_pagamento->id;
        $PedidoPortalObj->tipo_venda = $field['tipo_venda'];
        $PedidoPortalObj->conta_e_ordem = (!empty($field['codigo_cliente_conta_e_ordem']) ? true : false);
        $PedidoPortalObj->cod_cliente_conta_e_ordem = $field['codigo_cliente_conta_e_ordem'];
        $PedidoPortalObj->tipo_frete = $field['transportadora_tipo_frete'];
		$PedidoPortalObj->transportadora_redespacho = $field['transportadora_redespacho'];
		$PedidoPortalObj->tipo_frete_redespacho = $field['transportadora_redespacho_tipo_frete'];
        $PedidoPortalObj->save();

        $historicoPedidoObj = new HistoricoPedido();
    
        $historicoPedidoObj->pedido = $PedidoPortalObj->id;
        $historicoPedidoObj->natureza = 'futuro_atualiza_quantidade';
        $historicoPedidoObj->novo = 'Pedido as quantidades quando programado.';
        $historicoPedidoObj->created_by = Auth::id();
        $historicoPedidoObj->save();

        $itens = $field['item'];
        foreach($itens as $id => $quantidade){
            $PedidoItemPortalObj = PedidoItemPortal::find($id);
            
            $fields['id'] = $id;
            $fields['produto_codigo'] = $PedidoItemPortalObj->cod_produto;
            $fields['produto_codigo_base'] = $PedidoItemPortalObj->codigo_tecidos_base; 
            $fields['produto_codigo_desenho'] = $PedidoItemPortalObj->codigo_desenho; 
            $fields['preco_unitario'] = $field['preco_unitario'][$id];
            $fields['quantidade'] = $quantidade;
            $fields['estabelecimento'] = $PedidoPortalObj->estabelecimento;
            $fields['pedido'] = $PedidoPortalObj->id;
            
            $retorno_edicao = $this->editarFuturoProduto($PedidoPortalObj, $fields);

            if(!empty($retorno_edicao)){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produto '. $PedidoItemPortalObj->cod_produto. ', '. $retorno_edicao['errors']['preco_unitario'],
                    'error' => [
                        "preco_unitario_".$id => $retorno_edicao['errors']['preco_unitario'],
                    ],
                    'response' => []
                ], 422);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido atualizado com sucesso',
            'error' => [],
            'response' => []
        ], 200);
    }

    private function getIdCondicoesPagamentoCartcao(){
        $CondicoesPagamentoWebObj = CondicoesPagamentoWeb::
            where('ativo', true)
            ->where('nasajon', true)
            ->whereIn('nasajon_forma_pagamento', $this->nasajon_forma_pagamento_cartao)
            ->get();
        return $CondicoesPagamentoWebObj->pluck('id')->toArray();
    }

    private function mensagemPilotagem($PedidoPortal){
        $mensagem = '';
        $limite = false;
        $saldo = 0;
        $total_pedidos = 0;
        $total_notas = 0;
        $limite_valor = 0;
        $data = Carbon::now()->setTime(0,0,0);
        $UnidadeNegocioMetaControllerObj = new UnidadeNegocioMetaController();
        $codigo_representante = $PedidoPortal->usuario_detalhes->codigo_representante;
        $meta_negocio = $UnidadeNegocioMetaControllerObj->getVendedorMetaFaturamento($data->copy()->subMonth()->format('m/Y'), [$codigo_representante]);
        unset($UnidadeNegocioMetaControllerObj);
        if(isset($meta_negocio[$codigo_representante])){
            $meta_negocio = reset($meta_negocio[$codigo_representante]);
            $meta = false;
            if(parserFloat10($meta_negocio['valor']) > $this->limite_credito_pilotagem){
                $meta = true;
                if(parserFloat10($meta_negocio['valor']) > parserFloat10($this->limite_credito_pilotagem_teto)){
                    $limite_valor = parserFloat10($this->limite_credito_pilotagem_teto) * ($this->limite_credito_pilotagem_porcentagem / 100);
                }else{
                    $limite_valor = parserFloat10($meta_negocio['valor']) * ($this->limite_credito_pilotagem_porcentagem / 100);
                }
            }
            if($meta == true){
                $pedidoPoratal = PedidoPortal::whereIn('status_pedido', [1, 2])
                    ->where('usuario', $PedidoPortal->usuario)
                    ->where('tipo_venda', 'pedido_pilotagem')
                    ->where('id', '!=', $PedidoPortal->id)
                    ->with(['itens_pedido'])
                    ->get()
                    ->sum('valor_total.total');
                $NotasNasajonObj = PedidosVendaNasajon::select('id', 'numero', 'valor')->whereIn('operacao_codigo', ['PEDAMOSTRA', 'PEDAMOSTRAGRATIS'])
                    ->whereBetween('emissao', [$data->copy()->format('Y-m-01'), $data->format('Y-m-d')])
                    ->where('situacao_descricao', '=', 'Faturado')
                    ->where('vendedor_codigo', $codigo_representante)
                    ->distinct()
                    ->sum('valor');
                $PedidosVendaNasajonObj = PedidosVendaNasajon::whereIn('operacao_codigo', ['PEDAMOSTRA', 'PEDAMOSTRAGRATIS'])
                    ->whereBetween('emissao', [$data->copy()->format('Y-m-01'), $data->format('Y-m-d')])
                    ->where('situacao_descricao', '!=', 'Cancelado')
                    ->where('situacao_descricao', '!=', 'Faturado')
                    ->where('vendedor_codigo', $codigo_representante)
                    ->sum('valor');
                $total_pedidos = $pedidoPoratal + $PedidosVendaNasajonObj;
                $total_notas = $NotasNasajonObj;
                if(parserFloat10($limite_valor) > (parserFloat10($total_pedidos) + parserFloat10($total_notas))){
                    $saldo = $limite_valor - (parserFloat10($total_pedidos) + parserFloat10($total_notas));
                    $limite = true;
                }
            }
        }
        unset($meta_negocio);
        $mensagem .= '<p><b>'.$PedidoPortal->usuario_detalhes->codigo_representante.' - '.$PedidoPortal->usuario_detalhes->name.'</b></p>';
        $mensagem .= '<p><b>Crédito para pilotagem - </b> '.parserValor($limite_valor).'</p>';
        $mensagem .= '<p><b>Nota emitida - </b> '.parserValor($total_notas).'</p>';
        $mensagem .= '<p><b>Pedidos emitido - </b> '.parserValor($total_pedidos).'</p>';
        $mensagem .= '<p><b>Saldo para pilotagem '.$data->format('m/Y').' - </b> '.parserValor($saldo).'</p>';
        
        return $mensagem;
    }

    public function viewItens($PedidoItem){


        $PedidoPortalObj = PedidoPortal::with(['itens_pedido', 'itens_pedido.especificacoes'])->find($PedidoItem);

        if(empty($PedidoPortalObj)){
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido não encontrado',
                'error' => [],
                'response' => []
            ], 422);
        }
        $itens_pedido = [];

        foreach($PedidoPortalObj->itens_pedido as $key => $item){
            $itens_pedido[] = [
                'codigo' => $item->cod_produto,
                'descricao' => $item->especificacoes->descricao,             
                'quantidade' => parserValor($item->quantidade),
                'peso_total' => parserValor($item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0)) . ' kg',
                'preco_unitario' => parserValor($item->preco_unitario),
                'valor_total' => parserValor($item->preco_unitario * $item->quantidade),
            ];
        }


        return view("programs.pedido_portal.modal.detalhes_itens")->with(['itens_pedido' => $itens_pedido]);
	}
	
	public function blacklistPedido($Cliente){
		$retorno = false;
		if(in_array($Cliente->codigo, $this->codigo_cliente_balcao)){
			return false;
		}
		$cpf_cnpj = $Cliente->cpf_cnpj;
		if(strlen(trim($cpf_cnpj)) == 18){
			$cpf_cnpj = substr($cpf_cnpj, 0, 10);
		}

		$grupoEmpresarialObj = GrupoEmpresarial::with('participantes')
		->where('raiz_cnpj', $cpf_cnpj)
		->orWhereHas('participantes', function($query) use ($cpf_cnpj){
			$query->where('raiz_cnpj', $cpf_cnpj);
		})
		->first();

		$clientesNasajonQuery = ClienteNasajon::select('nome', 'codigo', 'cpf_cnpj', 'id');

		if (!is_null($grupoEmpresarialObj)){
			$clientesNasajonQuery->where(function($query) use ($grupoEmpresarialObj){
				if(isset($grupoEmpresarialObj->participantes)){
					foreach($grupoEmpresarialObj->participantes as $participante){
						$query->orWhere('cpf_cnpj', 'like', $participante->raiz_cnpj . '%');
					}
				}
				$query->orWhere('cpf_cnpj', 'like', $grupoEmpresarialObj->raiz_cnpj . '%');
			});
		}
		else {
			$clientesNasajonQuery->where('cpf_cnpj', 'like', $cpf_cnpj . '%');
		}

		unset($Cliente);
		$clientesNasajon = $clientesNasajonQuery->orderBy('cpf_cnpj')
		->groupBy('nome', 'codigo', 'cpf_cnpj', 'id')
		->get();

		unset($clientesNasajonQuery);
		unset($grupoEmpresarialObj);
		$ClienteAnaliseSinteticaControllerObj = new ClienteAnaliseSinteticaController();
		$blacklist = $ClienteAnaliseSinteticaControllerObj->statusBlackList($clientesNasajon->toArray(), true);
		if($blacklist === 3){
			$retorno = true;
		}
		return $retorno;
	}

	private function criandoObservacaoPedido(PedidoPortal $PedidoPortal){
		$observacao = '';
		if(in_array($PedidoPortal->cod_cliente, $this->codigo_cliente_balcao)){
			if(!empty($PedidoPortal->nome_comprador)){
				$observacao .= 'Cliente:'.$PedidoPortal->nome_comprador."\n";
			}
		}
		if($PedidoPortal->enfestar === true){
			$observacao .= "Enfestar Tecidos\n";
		}
		if($PedidoPortal->bater_amostra === true){
			$observacao .= "Bater Amostra\n";
		}
		if($PedidoPortal->incluir_cartelas === true){
			$observacao .= "Incluir Cartelas\n";
		}
		if($PedidoPortal->metragem_exata === true){
			$observacao .= "Metragem Exata\n";
		}
		if($PedidoPortal->transportadora_retira === true){
			if($PedidoPortal->transportadora_retira_imediato === true){
				$observacao .= "Retirada Imediata\n";
			}elseif(!empty($PedidoPortal->transportadora_retira_horario)){
				$horario = Carbon::createFromFormat('H:i:s', $PedidoPortal->transportadora_retira_horario)->format('H:i');
				$observacao .= "Retirada às ".$horario;
			}
		}
		return $observacao;
	}

	public function salvarCampos(Request $request){
		$campos = $request->only(['pedido', 'enfestar', 'bater_amostra', 'incluir_cartelas', 'metragem_exata', 'transportadora_retira_imediato', 'transportadora_retira_horario']);
		$PedidoPortal = PedidoPortal::find($campos['pedido']);
		unset($campos['pedido']);
		if(!empty($PedidoPortal)){
			foreach($campos as $nome => $valor){
				$PedidoPortal->$nome = $valor;
			}
			$PedidoPortal->updated_by = Auth::id();
			$PedidoPortal->save();
			return response()->json([
				'status' => 'success',
				'message' => '',
				'error' => [],
				'response' => []
			], $this->successStatus);
		}else{
			return response()->json([
				'status' => 'error',
				'message' => 'Pedido não encontrado',
				'error' => [],
				'response' => []
			], $this->errorStatus);
		}
	}

    public function gerarArquivoPdf($id){

        $pedidoObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes')->withTrashed()->find($id);
        if(is_null($pedidoObj)){
            return abort(404);
        }
        $estabelecimentos = returnEmpresasNasajonView();

        $produto = [];
        $total_itens_pedido = 0;

        foreach($pedidoObj->itens_pedido as $key => $value){
            $total_itens_pedido += round($value->quantidade * $value->preco_unitario, 2);
            $produto[] = [
                'grupo' => $value->especificacoes->grupo,
                'cod_produto' => $value->cod_produto,
                'descricao' => $value->especificacoes->descricao,
                'marca' => $value->especificacoes->marca,
                'linha' => $value->especificacoes->linha,
                'comissao' => str_replace('.', ',', $value->comissao) .'%',
                'coluna' => $value->coluna,
                'quantidade' => parserValor($value->quantidade),
                'preco_unitario' => parserValor($value->preco_unitario),
                'valor_total' => parserValor($value->quantidade * $value->preco_unitario),
            ];
        }
        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento',
            'pronta_entrega_venda' => 'Pronta Entrega',
            'pronta_entrega_triangular' => 'Pronta Entrega - Triangular',
            'pedido_futuro_venda' => 'Pedido Futuro',
            'pedido_futuro_triangular' => 'Pedido Futuro - Triangular',
            'producao' => 'Produção',
            'producao_triangular' => 'Produção - Triangular',
            'pre_pago_producao' => 'Pré-Pago - Produção',
            'pre_pago_producao_triangular' => 'Pré-Pago - Produção - Triangular',
            'pre_pago' => 'Pré-Pago - Pronta Entrega',
            'pre_pago_triangular' => 'Pré-Pago - Pronta Entrega - Triangular',
            'pre_pago_futuro' => 'Pré-Pago - Pedido Futuro',
            'pedido_orgaopublico' => 'Pedido Orgão Publico',
			'rj_x_sp' => 'Operação RJ/SP',
			'rj_x_sp_triangular' => 'Operação RJ/SP - Triangular',
            'pre_pago_rj_x_sp' => 'Pré-Pago - Operação RJ/SP ',
            'rj_x_sp_futuro' => 'Operação RJ/SP - Pedido Futuro',
            'rj_x_sp_triangular_futuro' => 'Operação RJ/SP - Triangular - Pedido Futuro',
            'pre_pago_rj_x_sp_futuro' => 'Pré-Pago - Operação RJ/SP  - Pedido Futuro',
        ];
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_especial);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas_interno);
        $tipo_venda_lista = array_merge($tipo_venda_lista, $this->tipo_vendas);
        $nome_cliente = '';
        $transportadora = '';
        $transportadora_redespacho = '';
        if($pedidoObj->nasajon === false){
            $nome_cliente = $pedidoObj->cliente['NOME'];
            $transportadora = (!empty($pedidoObj->detalhesTransportador) ? (str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportador->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportador->CGC)))):'');
            $transportadora_redespacho = !is_null($pedidoObj->detalhesTransportadorRedespacho) ? str_replace(' - __.___.___/____-__', '', trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->NOME)) . ' - ' . trim(utf8_encode($pedidoObj->detalhesTransportadorRedespacho->CGC))) : '';
            $cliente_conta_e_ordem = '';
        }else{
            $nome_cliente = $pedidoObj->cliente->nome . ' - ' . $pedidoObj->cliente->cpf_cnpj;
            $transportadora = (!empty($pedidoObj->detalhesTransportador) ? (str_replace(' - __.___.___/____-__', '', trim(($pedidoObj->detalhesTransportador->nome)) . ' - ' . trim(($pedidoObj->detalhesTransportador->cnpj)))):'');

            $transportadora_redespacho = !is_null($pedidoObj->detalhesTransportadorRedespacho) ? str_replace(' - __.___.___/____-__', '', trim(($pedidoObj->detalhesTransportadorRedespacho->nome)) . ' - ' . trim($pedidoObj->detalhesTransportadorRedespacho->cnpj)) : '';
            $cliente_conta_e_ordem = '';
            if(!empty($pedidoObj->cliente_conta_e_ordem)){
                $cliente_conta_e_ordem = $pedidoObj->cliente_conta_e_ordem->nome . ' - ' . $pedidoObj->cliente_conta_e_ordem->cpf_cnpj;
            }
        }
        $response = [
            'id' => $pedidoObj->id,
            'tipo_venda' => $tipo_venda_lista[$pedidoObj->tipo_venda],
            'estabelecimento' => $estabelecimentos[$pedidoObj->estabelecimento],
            'cliente' => [
                'codigo' =>$pedidoObj->cod_cliente,
                'nome' => $nome_cliente
            ],
            'condicao_pagamento_descr' => $pedidoObj->condicao_pagamento_detalhes['descricao'],
            'transportadora_redespacho' =>[
                'nome' => $transportadora_redespacho,
                'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete_redespacho],
                'valor_frete' => parserValor($pedidoObj->valor_frete_redespacho)??''
            ],
            'transportadora' => [
                'nome' => $transportadora,
                'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete],
                'valor_frete' => parserValor($pedidoObj->valor_frete)??''
            ],
            'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
            'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
            'pedido_futuro' => $pedidoObj->pedido_futuro?'Sim':'Não',
            'conta_e_ordem' => $pedidoObj->conta_e_ordem?'Sim':'Não',
            'cliente_conta_e_ordem' => $cliente_conta_e_ordem,
            'data_pedido' => date("d/m/Y", strtotime($pedidoObj->data_pedido)),
            'vendedor' => $pedidoObj->usuario_detalhes->codigo_representante . ' - ' . $pedidoObj->usuario_detalhes->name,
            'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? date("d/m/Y", strtotime($pedidoObj->data_previsao_entrega)) : '&nbsp;',
            'observacao' => $pedidoObj->observacao??'&nbsp;',
            'itens' => $produto,
            'aprovacao' => $pedidoObj->aprovacao['nivelaprovacao']['descricao'] ?? '',
            'valor_total_itens' => parserValor($total_itens_pedido),
            'valor_desconto' => parserValor($pedidoObj->valor_desconto),
            'valor_frete' => parserValor($pedidoObj->valor_frete),
            'valor_total_nota' => parserValor($total_itens_pedido + ($pedidoObj->tipo_frete == 'C'?$pedidoObj->valor_frete:0) - $pedidoObj->valor_desconto),
            'pedido_gerado' => $pedidoObj->pedido_gerado,
            'no_pedido_compra' => $pedidoObj->no_pedido_compra
        ];

		$pdfFilePath = 'pedido_'.$pedidoObj->id.'_'.$pedidoObj->cliente->nome.'.pdf';
		$pdf = PDF::loadView(
			'pdf.contrato_pedido', 
			[
				'pedido' => $response,
			], 
			[], 
			['title' => 'Pedido', 'custom_font_dir' => '', 'custom_font_data' => [],'margin_bottom' => 1]
		);
        
        Storage::put($this->storage.$pedidoObj->id.'/'.$pdfFilePath, $pdf->output());
        return $this->storage.$pedidoObj->id.'/'.$pdfFilePath;
    }

    public function gerarSignatario($id){

        $pedidoObj = PedidoPortal::with('itens_pedido', 'itens_pedido.especificacoes')->withTrashed()->find($id);

        $signatarios = [];

        $signatarios[] = [
            'email' => empty($pedidoObj->email_comprador)? $pedidoObj->cliente->email : $pedidoObj->email_comprador,
            'nome' => empty($pedidoObj->nome_comprador)? $pedidoObj->cliente->nome : $pedidoObj->nome_comprador,
            'cpf' => '',
            'data_nascimento' => '',
            'tipo' => 'representante_legal',
        ];

        return $signatarios;
    }

    public function separacaoPedidoFuturoPorPCMN($Pedido){
        $array_pedidos = [];
        $pedido_gerado = $Pedido->id;
        $numero_compra_principal = $Pedido->itens_pedido[0]->numero_compra;
    
        if(substr_count($numero_compra_principal, "-") !== 0){
            $numero_compra_principal = explode("-", $numero_compra_principal);
            $numero_compra_principal = explode(":", $numero_compra_principal[0]);
            $numero_compra_principal = $numero_compra_principal[0];
        }
    
        $query_outro_numero_compra = PedidoItemPortal::select();
        $query_outro_numero_compra->where('pedido', $Pedido->id);
        $query_outro_numero_compra->where('numero_compra', '<>', $numero_compra_principal);
        $query_outro_numero_compra->orderBy('numero_compra');
        $result_outro_numero_compra = $query_outro_numero_compra->get();
    
        $outro_numero_compra_auxiliar = '';
        foreach($result_outro_numero_compra as $item){
            $novo_pedido = false;

            if(substr_count($item->numero_compra, "-") !== 0){
                $numero_compra_separado = explode("-", $item->numero_compra);
                foreach($numero_compra_separado as $value){
                    $dados = explode(":", $value[0]);
                    $numero = $value[0];
                    $quantidade = floatval($value[1]);
                    if(empty($outro_numero_compra_auxiliar)){
                        $outro_numero_compra_auxiliar = $item->numero_compra;
                        $novo_pedido = true;
                    }else if($outro_numero_compra_auxiliar !== $item->numero_compra){
                        $outro_numero_compra_auxiliar = $item->numero_compra;
                        $novo_pedido = true;
                    }
            
                    if($novo_pedido){
                        $pedidoPortalNovoObj = new PedidoPortal;
                        $pedidoPortalNovoObj->data_pedido = $Pedido->data_pedido;
                        $pedidoPortalNovoObj->usuario = $Pedido->usuario;
                        $pedidoPortalNovoObj->cod_cliente = $Pedido->cod_cliente;
                        $pedidoPortalNovoObj->nome_comprador = $Pedido->nome_comprador;
                        $pedidoPortalNovoObj->email_comprador = $Pedido->email_comprador;
                        $pedidoPortalNovoObj->status_pedido = $Pedido->status_pedido;
                        $pedidoPortalNovoObj->estabelecimento = $Pedido->estabelecimento;
                        $pedidoPortalNovoObj->pedido_gerado = $Pedido->pedido_gerado;
                        $pedidoPortalNovoObj->pedido_futuro = $Pedido->pedido_futuro;
                        $pedidoPortalNovoObj->condicao_pagamento = $Pedido->condicao_pagamento;
                        $pedidoPortalNovoObj->no_pedido_compra = $Pedido->no_pedido_compra;
                        $pedidoPortalNovoObj->cod_usuario_autorizador = $Pedido->cod_usuario_autorizador;
                        $pedidoPortalNovoObj->data_previsao_entrega = $Pedido->data_previsao_entrega;
                        $pedidoPortalNovoObj->tipo_frete = $Pedido->tipo_frete;
                        $pedidoPortalNovoObj->observacao = $Pedido->observacao;
                        $pedidoPortalNovoObj->transportadora = $Pedido->transportadora;
                        $pedidoPortalNovoObj->transportadora_redespacho = $Pedido->transportadora_redespacho;
                        $pedidoPortalNovoObj->comissao = $Pedido->comissao;
                        $pedidoPortalNovoObj->created_by = $Pedido->created_by;
                        $pedidoPortalNovoObj->base_icms = $Pedido->base_icms;
                        $pedidoPortalNovoObj->valor_icms = $Pedido->valor_icms;
                        $pedidoPortalNovoObj->base_icmsst = $Pedido->base_icmsst;
                        $pedidoPortalNovoObj->valor_icmsst = $Pedido->valor_icmsst;
                        $pedidoPortalNovoObj->valor_frete = $Pedido->valor_frete;
                        $pedidoPortalNovoObj->valor_seguro = $Pedido->valor_seguro;
                        $pedidoPortalNovoObj->valor_desconto = $Pedido->valor_desconto;
                        $pedidoPortalNovoObj->outros_valores = $Pedido->outros_valores;
                        $pedidoPortalNovoObj->valor_ipi = $Pedido->valor_ipi;
                        $pedidoPortalNovoObj->valor_total_produtos = $Pedido->valor_total_produtos;
                        $pedidoPortalNovoObj->valor_total_nota = $Pedido->valor_total_nota;
                        $pedidoPortalNovoObj->erro_integracao = $Pedido->erro_integracao;
                        $pedidoPortalNovoObj->motivo_rejeicao = $Pedido->motivo_rejeicao;
                        $pedidoPortalNovoObj->codigo_operacao = $Pedido->codigo_operacao;
                        $pedidoPortalNovoObj->conta_e_ordem = $Pedido->conta_e_ordem;
                        $pedidoPortalNovoObj->cod_cliente_conta_e_ordem = $Pedido->cod_cliente_conta_e_ordem;
                        $pedidoPortalNovoObj->tipo_venda = $Pedido->tipo_venda;
                        $pedidoPortalNovoObj->tipo_frete_redespacho = $Pedido->tipo_frete_redespacho;
                        $pedidoPortalNovoObj->valor_frete_redespacho = $Pedido->valor_frete_redespacho;
                        $pedidoPortalNovoObj->pedido_remessa_gerado = $Pedido->pedido_remessa_gerado;
                        $pedidoPortalNovoObj->tabela_tipo_preco = $Pedido->tabela_tipo_preco;
                        $pedidoPortalNovoObj->frete_preco = $Pedido->frete_preco;
                        $pedidoPortalNovoObj->nasajon = $Pedido->nasajon;
                        $pedidoPortalNovoObj->migracao = $Pedido->migracao;
                        $pedidoPortalNovoObj->dias_cancelar = $Pedido->dias_cancelar;
                        $pedidoPortalNovoObj->agente_venda_id = $Pedido->agente_venda_id;
                        $pedidoPortalNovoObj->projeto_id = $Pedido->projeto_id;
                        $pedidoPortalNovoObj->bionexo = $Pedido->bionexo;
                        $pedidoPortalNovoObj->cartao = $Pedido->cartao;
                        $pedidoPortalNovoObj->presencial = $Pedido->presencial;
                        $pedidoPortalNovoObj->promotor_venda_id = $Pedido->promotor_venda_id;
                        $pedidoPortalNovoObj->enfestar = $Pedido->enfestar;
                        $pedidoPortalNovoObj->bater_amostra = $Pedido->bater_amostra;
                        $pedidoPortalNovoObj->incluir_cartelas = $Pedido->incluir_cartelas;
                        $pedidoPortalNovoObj->transportadora_retira = $Pedido->transportadora_retira;
                        $pedidoPortalNovoObj->transportadora_retira_horario = $Pedido->transportadora_retira_horario;
                        $pedidoPortalNovoObj->transportadora_retira_imediato = $Pedido->transportadora_retira_imediato;
                        $pedidoPortalNovoObj->metragem_exata = $Pedido->metragem_exata;
                        $pedidoPortalNovoObj->cliente_sem_telefone = $Pedido->cliente_sem_telefone;
                        $pedidoPortalNovoObj->cliente_telefone = $Pedido->cliente_telefone;
                        $pedidoPortalNovoObj->outlet = $Pedido->outlet;
                        $pedidoPortalNovoObj->rj_x_sp = $Pedido->rj_x_sp;
                        $pedidoPortalNovoObj->clicksign_documentos_id = $Pedido->clicksign_documentos_id;
                        $pedidoPortalNovoObj->data_previsao_entrega_original = $Pedido->data_previsao_entrega_original;
                        $pedidoPortalNovoObj->data_previsao_entrega_ultima = $Pedido->data_previsao_entrega_ultima;
                        $pedidoPortalNovoObj->save();
            
                        $pedido_gerado .= ', '.$pedidoPortalNovoObj->id;
                        $array_pedidos[] = $pedidoPortalNovoObj->id;
                    }
            
                    $pedidoItemPortalNovoObj = new PedidoItemPortal;
                    $pedidoItemPortalNovoObj->pedido = $pedidoPortalNovoObj->id;
                    $pedidoItemPortalNovoObj->usuario = $item->usuario;
                    $pedidoItemPortalNovoObj->cod_produto = $item->cod_produto;
                    $pedidoItemPortalNovoObj->quantidade = $quantidade;
                    $pedidoItemPortalNovoObj->preco_unitario = $item->preco_unitario;
                    $pedidoItemPortalNovoObj->created_by = $item->created_by;
                    $pedidoItemPortalNovoObj->valor_icms = $item->valor_icms;
                    $pedidoItemPortalNovoObj->base_calculo_icms = $item->base_calculo_icms;
                    $pedidoItemPortalNovoObj->valor_ipi = $item->valor_ipi;
                    $pedidoItemPortalNovoObj->aliquota_icms = $item->aliquota_icms;
                    $pedidoItemPortalNovoObj->aliquota_ipi = $item->aliquota_ipi;
                    $pedidoItemPortalNovoObj->valor_frete = $item->valor_frete;
                    $pedidoItemPortalNovoObj->valor_total = $item->valor_total;
                    $pedidoItemPortalNovoObj->coluna = $item->coluna;
                    $pedidoItemPortalNovoObj->comissao = $item->comissao;
                    $pedidoItemPortalNovoObj->ipi_produto = $item->ipi_produto;
                    $pedidoItemPortalNovoObj->preco_base = $item->preco_base;
                    $pedidoItemPortalNovoObj->coluna_a = $item->coluna_a;
                    $pedidoItemPortalNovoObj->coluna_b = $item->coluna_b;
                    $pedidoItemPortalNovoObj->coluna_c = $item->coluna_c;
                    $pedidoItemPortalNovoObj->numero_compra = $numero;
                    $pedidoItemPortalNovoObj->preco_promocao = $item->preco_promocao;
                    $pedidoItemPortalNovoObj->codigo_tecidos_base = $item->codigo_tecidos_base;
                    $pedidoItemPortalNovoObj->codigo_desenho = $item->codigo_desenho;
                    $pedidoItemPortalNovoObj->produto_sem_estoque = $item->produto_sem_estoque;
                    $pedidoItemPortalNovoObj->tem_ipi = $item->tem_ipi;
                    $pedidoItemPortalNovoObj->contador = $item->contador;
                    $pedidoItemPortalNovoObj->preco_original = $item->preco_original;
                    $pedidoItemPortalNovoObj->necessidades_compras_id = $item->necessidades_compras_id;
                    $pedidoItemPortalNovoObj->faccaos_id = $item->faccaos_id;
                    $pedidoItemPortalNovoObj->pedido_compras_uuid_nasajon = $item->pedido_compras_uuid_nasajon;
                    $pedidoItemPortalNovoObj->pedido_remessa_uuid_nasajon = $item->pedido_remessa_uuid_nasajon;
                    $pedidoItemPortalNovoObj->save();
            
                    $item->delete();
                    $item->save();
                }
                
            }else{
                if(empty($outro_numero_compra_auxiliar)){
                    $outro_numero_compra_auxiliar = $item->numero_compra;
                    $novo_pedido = true;
                }else if($outro_numero_compra_auxiliar !== $item->numero_compra){
                    $outro_numero_compra_auxiliar = $item->numero_compra;
                    $novo_pedido = true;
                }
        
                if($novo_pedido){
                    $pedidoPortalNovoObj = new PedidoPortal;
                    $pedidoPortalNovoObj->data_pedido = $Pedido->data_pedido;
                    $pedidoPortalNovoObj->usuario = $Pedido->usuario;
                    $pedidoPortalNovoObj->cod_cliente = $Pedido->cod_cliente;
                    $pedidoPortalNovoObj->nome_comprador = $Pedido->nome_comprador;
                    $pedidoPortalNovoObj->email_comprador = $Pedido->email_comprador;
                    $pedidoPortalNovoObj->status_pedido = $Pedido->status_pedido;
                    $pedidoPortalNovoObj->estabelecimento = $Pedido->estabelecimento;
                    $pedidoPortalNovoObj->pedido_gerado = $Pedido->pedido_gerado;
                    $pedidoPortalNovoObj->pedido_futuro = $Pedido->pedido_futuro;
                    $pedidoPortalNovoObj->condicao_pagamento = $Pedido->condicao_pagamento;
                    $pedidoPortalNovoObj->no_pedido_compra = $Pedido->no_pedido_compra;
                    $pedidoPortalNovoObj->cod_usuario_autorizador = $Pedido->cod_usuario_autorizador;
                    $pedidoPortalNovoObj->data_previsao_entrega = $Pedido->data_previsao_entrega;
                    $pedidoPortalNovoObj->tipo_frete = $Pedido->tipo_frete;
                    $pedidoPortalNovoObj->observacao = $Pedido->observacao;
                    $pedidoPortalNovoObj->transportadora = $Pedido->transportadora;
                    $pedidoPortalNovoObj->transportadora_redespacho = $Pedido->transportadora_redespacho;
                    $pedidoPortalNovoObj->comissao = $Pedido->comissao;
                    $pedidoPortalNovoObj->created_by = $Pedido->created_by;
                    $pedidoPortalNovoObj->base_icms = $Pedido->base_icms;
                    $pedidoPortalNovoObj->valor_icms = $Pedido->valor_icms;
                    $pedidoPortalNovoObj->base_icmsst = $Pedido->base_icmsst;
                    $pedidoPortalNovoObj->valor_icmsst = $Pedido->valor_icmsst;
                    $pedidoPortalNovoObj->valor_frete = $Pedido->valor_frete;
                    $pedidoPortalNovoObj->valor_seguro = $Pedido->valor_seguro;
                    $pedidoPortalNovoObj->valor_desconto = $Pedido->valor_desconto;
                    $pedidoPortalNovoObj->outros_valores = $Pedido->outros_valores;
                    $pedidoPortalNovoObj->valor_ipi = $Pedido->valor_ipi;
                    $pedidoPortalNovoObj->valor_total_produtos = $Pedido->valor_total_produtos;
                    $pedidoPortalNovoObj->valor_total_nota = $Pedido->valor_total_nota;
                    $pedidoPortalNovoObj->erro_integracao = $Pedido->erro_integracao;
                    $pedidoPortalNovoObj->motivo_rejeicao = $Pedido->motivo_rejeicao;
                    $pedidoPortalNovoObj->codigo_operacao = $Pedido->codigo_operacao;
                    $pedidoPortalNovoObj->conta_e_ordem = $Pedido->conta_e_ordem;
                    $pedidoPortalNovoObj->cod_cliente_conta_e_ordem = $Pedido->cod_cliente_conta_e_ordem;
                    $pedidoPortalNovoObj->tipo_venda = $Pedido->tipo_venda;
                    $pedidoPortalNovoObj->tipo_frete_redespacho = $Pedido->tipo_frete_redespacho;
                    $pedidoPortalNovoObj->valor_frete_redespacho = $Pedido->valor_frete_redespacho;
                    $pedidoPortalNovoObj->pedido_remessa_gerado = $Pedido->pedido_remessa_gerado;
                    $pedidoPortalNovoObj->tabela_tipo_preco = $Pedido->tabela_tipo_preco;
                    $pedidoPortalNovoObj->frete_preco = $Pedido->frete_preco;
                    $pedidoPortalNovoObj->nasajon = $Pedido->nasajon;
                    $pedidoPortalNovoObj->migracao = $Pedido->migracao;
                    $pedidoPortalNovoObj->dias_cancelar = $Pedido->dias_cancelar;
                    $pedidoPortalNovoObj->agente_venda_id = $Pedido->agente_venda_id;
                    $pedidoPortalNovoObj->projeto_id = $Pedido->projeto_id;
                    $pedidoPortalNovoObj->bionexo = $Pedido->bionexo;
                    $pedidoPortalNovoObj->cartao = $Pedido->cartao;
                    $pedidoPortalNovoObj->presencial = $Pedido->presencial;
                    $pedidoPortalNovoObj->promotor_venda_id = $Pedido->promotor_venda_id;
                    $pedidoPortalNovoObj->enfestar = $Pedido->enfestar;
                    $pedidoPortalNovoObj->bater_amostra = $Pedido->bater_amostra;
                    $pedidoPortalNovoObj->incluir_cartelas = $Pedido->incluir_cartelas;
                    $pedidoPortalNovoObj->transportadora_retira = $Pedido->transportadora_retira;
                    $pedidoPortalNovoObj->transportadora_retira_horario = $Pedido->transportadora_retira_horario;
                    $pedidoPortalNovoObj->transportadora_retira_imediato = $Pedido->transportadora_retira_imediato;
                    $pedidoPortalNovoObj->metragem_exata = $Pedido->metragem_exata;
                    $pedidoPortalNovoObj->cliente_sem_telefone = $Pedido->cliente_sem_telefone;
                    $pedidoPortalNovoObj->cliente_telefone = $Pedido->cliente_telefone;
                    $pedidoPortalNovoObj->outlet = $Pedido->outlet;
                    $pedidoPortalNovoObj->rj_x_sp = $Pedido->rj_x_sp;
                    $pedidoPortalNovoObj->clicksign_documentos_id = $Pedido->clicksign_documentos_id;
                    $pedidoPortalNovoObj->data_previsao_entrega_original = $Pedido->data_previsao_entrega_original;
                    $pedidoPortalNovoObj->data_previsao_entrega_ultima = $Pedido->data_previsao_entrega_ultima;
                    $pedidoPortalNovoObj->save();
        
                    $pedido_gerado .= ', '.$pedidoPortalNovoObj->id;
                    $array_pedidos[] = $pedidoPortalNovoObj->id;
                }
        
                $pedidoItemPortalNovoObj = new PedidoItemPortal;
                $pedidoItemPortalNovoObj->pedido = $pedidoPortalNovoObj->id;
                $pedidoItemPortalNovoObj->usuario = $item->usuario;
                $pedidoItemPortalNovoObj->cod_produto = $item->cod_produto;
                $pedidoItemPortalNovoObj->quantidade = $item->quantidade;
                $pedidoItemPortalNovoObj->preco_unitario = $item->preco_unitario;
                $pedidoItemPortalNovoObj->created_by = $item->created_by;
                $pedidoItemPortalNovoObj->valor_icms = $item->valor_icms;
                $pedidoItemPortalNovoObj->base_calculo_icms = $item->base_calculo_icms;
                $pedidoItemPortalNovoObj->valor_ipi = $item->valor_ipi;
                $pedidoItemPortalNovoObj->aliquota_icms = $item->aliquota_icms;
                $pedidoItemPortalNovoObj->aliquota_ipi = $item->aliquota_ipi;
                $pedidoItemPortalNovoObj->valor_frete = $item->valor_frete;
                $pedidoItemPortalNovoObj->valor_total = $item->valor_total;
                $pedidoItemPortalNovoObj->coluna = $item->coluna;
                $pedidoItemPortalNovoObj->comissao = $item->comissao;
                $pedidoItemPortalNovoObj->ipi_produto = $item->ipi_produto;
                $pedidoItemPortalNovoObj->preco_base = $item->preco_base;
                $pedidoItemPortalNovoObj->coluna_a = $item->coluna_a;
                $pedidoItemPortalNovoObj->coluna_b = $item->coluna_b;
                $pedidoItemPortalNovoObj->coluna_c = $item->coluna_c;
                $pedidoItemPortalNovoObj->numero_compra = $item->numero_compra;
                $pedidoItemPortalNovoObj->preco_promocao = $item->preco_promocao;
                $pedidoItemPortalNovoObj->codigo_tecidos_base = $item->codigo_tecidos_base;
                $pedidoItemPortalNovoObj->codigo_desenho = $item->codigo_desenho;
                $pedidoItemPortalNovoObj->produto_sem_estoque = $item->produto_sem_estoque;
                $pedidoItemPortalNovoObj->tem_ipi = $item->tem_ipi;
                $pedidoItemPortalNovoObj->contador = $item->contador;
                $pedidoItemPortalNovoObj->preco_original = $item->preco_original;
                $pedidoItemPortalNovoObj->necessidades_compras_id = $item->necessidades_compras_id;
                $pedidoItemPortalNovoObj->faccaos_id = $item->faccaos_id;
                $pedidoItemPortalNovoObj->pedido_compras_uuid_nasajon = $item->pedido_compras_uuid_nasajon;
                $pedidoItemPortalNovoObj->pedido_remessa_uuid_nasajon = $item->pedido_remessa_uuid_nasajon;
                $pedidoItemPortalNovoObj->save();
        
                $item->delete();
                $item->save();
            }
        }
    
        $pedido_gerado .= '.';
    
        foreach($array_pedidos as $pedido_novo){
            Artisan::queue('pedido:validacao', ['pedido' => $pedido_novo]);
        }
    
        return $pedido_gerado;
    }

    private function editarFuturoProduto($pedidoObj, $fields){

        $pedido_item = PedidoItemPortal::findOrFail($fields['id']);

        $produtoObj = ProdutoNasajon::where('codigo', strtoupper($fields['produto_codigo']))->first();

        if(
            is_null($produtoObj) &&
            isset($fields['produto_codigo_base']) &&
            !empty($fields['produto_codigo_base']) &&
            isset($fields['produto_codigo_desenho']) &&
            !empty($fields['produto_codigo_desenho'])
        ){
            $return = $this->criarProdutoProducao($fields);
            $produtoObj = ProdutoNasajon::where('codigo', strtoupper($fields['produto_codigo']))->first();
        }else{
            if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular' , 'pre_pago_producao', 'pre_pago_producao_triangular'])){
                $return = $this->atualizaProdutos($fields);
            }
        }
        $tem_ipi = false;
        if(!empty($produtoObj->ipi) && intval($fields['estabelecimento']) != 3){
            $tem_ipi = true;
        }

        $estadoObj = CepEstado::find($pedidoObj->cliente->uf);

        switch ($fields['estabelecimento']) {
            case '3':
                $origem = "RO";
            break;
            case '4':
                $origem = "TO";
            break;
            default:
                $origem = "SP";
            break;
        }

		if($pedidoObj->rj_x_sp == true){
			$origem = 'SP';
		}

        $internacional = false;

        $aliquotaObj = AliquotaPreco::where("origem", $origem)
            ->where('estado', $pedidoObj->cliente->uf)
            ->where('internacional', $internacional)->first();

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            !in_array(str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $razao_cnpj_textil === $raiz_cnpj
        ){
            $preco_custo = true;
        }
        
        $produto_sem_estoque = false;
        if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $validacaoProdutosProducao = $this->validarProdutoNasajonProducaoFuturo($pedidoObj, strtoupper($fields['produto_codigo']), strtoupper($fields['produto_codigo_base']), strtoupper($fields['produto_codigo_desenho']), $fields['preco_unitario'], $fields['quantidade'], $estadoObj->regiao, $preco_custo);
            if ($validacaoProdutosProducao['status'] === 'error'){
                return $validacaoProdutosProducao;
            }else{
                $produto_sem_estoque = $validacaoProdutosProducao['response']['produto_sem_estoque'];
            }
        }else{
            $erros = $this->validarProdutoNasajonFuturo($origem, strtoupper($fields['produto_codigo']), $pedidoObj->cliente->uf, null, $estadoObj->regiao, $pedidoObj, $fields['preco_unitario'], $fields['quantidade'], '', $preco_custo);
        }

        $preco_promocao = false;
        if (!empty($erros)){
            return $erros;
        }

        if($preco_custo === true){
            $valor_frete = 0;
            $base_calculo_icms = 0;
            $valor_icms = 0;
            $coluna_preco = 0;
            
            $coluna_c = 0;
            $coluna_b = 0;
            $coluna_a = 0;

            $comissao_porcentagem = 0;
            $coluna_preco = 0;

        } else {
            $frete = $pedidoObj->frete_preco;

            $items = new ListaDePrecosRequest([
                'origem' => $origem,
                'aliquota' => $aliquotaObj->icms_venda,
                'produto' => strtoupper($fields['produto_codigo']),
                'moeda' => ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real',
                'frete' => $frete,
                'estado' => $estadoObj->uf,
                'cidade' => $pedidoObj->cliente->cidade,
                'tipo_cliente' => (
                    $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico',
                'prazo_medio' => $pedidoObj->condicao_pagamento_detalhes['media'] ?? 0,

                'promocao' => false,
                'estabelecimento' => $pedidoObj->estabelecimento,
                'cliente' => $pedidoObj->cod_cliente,
                'codigo_vendedor' => $pedidoObj->usuario_detalhes->codigo_representante,
                'estabelecimento' => $pedidoObj->estabelecimento,
            ]);

            $precoObj = new ListagemDePrecosController;
            $precos = $precoObj->filter($items, true, false, true, false, false, false, false, false, false);
            $parametrosAprovacaoObj = ParametrosAprovacao::with('tipoUsuario')->where('estabelecimento', str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT))->get();
            $margemPrazoObj = MargemPrazo::where('estabelecimento', $pedidoObj->estabelecimento)->first();
            
            $desconto_maximo_gerente = 1 - $parametrosAprovacaoObj[($parametrosAprovacaoObj->search(function ($item, $key){ return strtolower($item->tipoUsuario->nome) == 'gerente'; }))]->percentual_desconto / 100;

            $fator_comissao = $margemPrazoObj->preco_b - 1;

            $infoProduto = new ProdutoController;
            $promocional = $infoProduto->promocional(strtoupper($fields['produto_codigo']), $pedidoObj->estabelecimento, $pedidoObj->usuario_detalhes->codigo_representante, $pedidoObj->cod_cliente, $frete);

            $precos_promocao = [];
            if($promocional === true){
                $items = new ListaDePrecosRequest([
                    'origem' => $origem,
                    'produto' => strtoupper($fields['produto_codigo']),
                    'moeda' => ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real',
                    'prazo_medio' => $pedidoObj->condicao_pagamento_detalhes['media'] ?? 0,
                    'frete' => $frete,
                    'estado' => $estadoObj->uf, 
                    'cidade' => $pedidoObj->cliente->cidade,
                    'tipo_cliente' => (
                        $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
                        intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
                        intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
                    ) ? 'isento' : 'juridico',
                    'promocao' => true,
                    'estabelecimento' => $pedidoObj->estabelecimento,
                    'cliente' => $pedidoObj->cod_cliente,
                    'codigo_vendedor' => $pedidoObj->usuario_detalhes->codigo_representante,
                ]);
                $precos_promocao = $precoObj->filter($items, true, false, true, false, false, false, false, false, false);
                $precos_promocao = reset($precos_promocao);
            }

            $coluna_c = $precos[0][2];
            $coluna_b = $precos[0][1];
            $coluna_a = $precos[0][0];
            $preco_unitario = parserNumber($fields['preco_unitario']);

            if(!empty($pedidoObj->usuario_detalhes->detalhesModelHasRoles)){
                $perfil_acesso = $pedidoObj->usuario_detalhes->detalhesModelHasRoles->detalhesRoles->name;
            }else{
                $perfil_acesso = '';
            }

            /*if($perfil_acesso === 'REP.Playstation'){
                $coluna_preco = '0';
                if ($coluna_a <= $preco_unitario){

                    for($x = 0; $x <= 12; $x++ ){
                        if($precos[0][$x] <= $preco_unitario){
                            $acrescimo_comissao = $x;
                        }
                    }

                    $comissao_porcentagem = $pedidoObj->usuario_detalhes["comissao_a"] + $acrescimo_comissao;
                    if ($comissao_porcentagem > 15){
                        $comissao_porcentagem = 15;
                    }
                }
                else if(
                    $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                    $coluna_a * $desconto_maximo_gerente <= $preco_unitario
                ){
                    $comissao_porcentagem = $pedidoObj->usuario_detalhes["comissao_a"];
                }
                else if($coluna_a * $desconto_maximo_gerente > $preco_unitario) {
                    $porcentagem_desconto = 1 - (parserNumber($fields['preco_unitario']) / parserNumber($precos[0]['coluna_a']));
                    $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
                    $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
                    $comissao_porcentagem = $pedidoObj->usuario_detalhes->comissao_a - $desconto_comissao;
                    $comissao_porcentagem = $comissao_porcentagem >= 5 ? $comissao_porcentagem : 5;
                    $coluna_preco = '0';
                }

                $desconto_permitido = 9.01;
                if($pedidoObj->cliente->cidade == 'Nova Friburgo'){
                    $desconto_permitido = 14.01;
                }
                if($coluna_a > $preco_unitario){
                    $desconto = ((1 - ($preco_unitario / $coluna_a)) * 100);
                    if(
                        $desconto <= $desconto_permitido
                    ){
                        $comissao_porcentagem = 6;
                    }
                    else{
                        $comissao_porcentagem = 5;
                        if($promocional === true){
                            $comissao_porcentagem = $precos_promocao['promocional']['comissao'];
                            if (
                                isset($precos_promocao['promocional']['sem_desconto_adicional']) &&
                                $precos_promocao['promocional']['sem_desconto_adicional'] === true &&
                                $preco_unitario < parserNumber($precos_promocao['coluna_a'])
                            ){
                                $error['preco_unitario'] = 'Preço abaixo da promoção, não permitida';
                                $error_return = ['status' => 'error', 'errors' => $error];
                                return $error_return;
                            }
                            $preco_promocao = true;
                        }
                    }
                }

            }else*/ if (!in_array($pedidoObj->usuario_detalhes->tipo_usuario_id, [13, 16, 19, 14])){
                $coluna_preco = '0';
                if ($coluna_a <= $preco_unitario){
                    for($x = 0; $x <= 12; $x++ ){
                        if($precos[0][$x] <= $preco_unitario){
                            $acrescimo_comissao = $x;
                        }
                    }
                    $comissao_porcentagem = $pedidoObj->usuario_detalhes["comissao_a"] + $acrescimo_comissao;
                
                    if ($comissao_porcentagem > 15){
                        $comissao_porcentagem = 15;
                    }
                }
                else if(
                    $coluna_a * (1 + $fator_comissao) > $preco_unitario &&
                    $coluna_a * $desconto_maximo_gerente <= $preco_unitario
                ){
                    $comissao_porcentagem = $pedidoObj->usuario_detalhes["comissao_a"];
                }
                else if($coluna_a * $desconto_maximo_gerente > $preco_unitario) {

                    $porcentagem_desconto = 1 - (parserNumber($fields['preco_unitario']) / parserNumber($precos[0]['coluna_a']));
                    $desconto_a_mais = $porcentagem_desconto * 100 - (1 - $desconto_maximo_gerente) * 100;
                    $desconto_comissao = ceil($desconto_a_mais / 1.5) * 0.5;
                    $comissao_porcentagem = $pedidoObj->usuario_detalhes->comissao_a - $desconto_comissao;
                    $comissao_porcentagem = $comissao_porcentagem >= 2 ? $comissao_porcentagem : 2;
                    $coluna_preco = '0';

                }
					$desconto_permitido = 9.01;
					if($pedidoObj->cliente->cidade == 'Nova Friburgo'){
						$desconto_permitido = 14.01;
					}
                    if($coluna_a > $preco_unitario){
                        $desconto = ((1 - ($preco_unitario / $coluna_a)) * 100);
                        if(
                            $desconto <= $desconto_permitido
                        ){
                            $comissao_porcentagem = 3;
                        }
                        else{
                            $comissao_porcentagem = 2;
                            if($promocional === true){
                                $comissao_porcentagem = $precos_promocao['promocional']['comissao'];
                                $preco_promocao = true;
                                if (
                                    isset($precos_promocao['promocional']['sem_desconto_adicional']) &&
                                    $precos_promocao['promocional']['sem_desconto_adicional'] === true &&
                                    $preco_unitario < parserNumber($precos_promocao['coluna_a'])
                                ){
                                    $error['preco_unitario'] = 'Preço abaixo da promoção, não permitida';
                                    $error_return = ['status' => 'error', 'errors' => $error];
                                    return $error_return;
                                }
                            }
                        }
                    }

                if($pedidoObj->bionexo == true){
                    if($comissao_porcentagem > 2){
                        $comissao_porcentagem -= 1;
                        if($comissao_porcentagem < 2){
                            $comissao_porcentagem = 2;
                        }
                    }
                }
            }else{
                $coluna_preco = '0';
                $comissao_porcentagem = $pedidoObj->usuario_detalhes["comissao_a"];
            }

            if($frete == 'cif'){
                $valor_frete = parserNumber($fields['preco_unitario']) * parserNumber($fields['quantidade']);
            }else{
                $valor_frete = 0;
            }
            $base_calculo_icms = (
                parserNumber($fields['preco_unitario']) * parserNumber($fields['quantidade'])
            ) * (1 - ($aliquotaObj->aliquota / 100));

            $valor_icms = (
                (
                    parserNumber($fields['preco_unitario']) * parserNumber($fields['quantidade'])
                ) * ($aliquotaObj->aliquota / 100)
            );
        }

		$preco_original = 0;
		$preco_unitario = parserNumber($fields['preco_unitario']);
		if($pedidoObj->metragem_exata === true && $pedidoObj->estabelecimento != 3){
			$preco_original = $preco_unitario;
			$preco_unitario = $preco_unitario * (1 + ($this->porcentagem_metragem_exata / 100));
		}

        $excecao_comissao = ProdutoPromocional::select();
        $excecao_comissao->whereNull('codigo_cliente');
        $excecao_comissao->whereNull('codigo_produto');
        $excecao_comissao->whereNull('codigo_estabelecimento');
        $excecao_comissao->whereNull('tipo_frete');
        $excecao_comissao->whereNull('codigo_cliente');
        $excecao_comissao->whereNull('grupo');
        $excecao_comissao->where('codigo_vendedor', $pedidoObj->usuario);
        $excecao_comissao->where('tipo_promocional', 'pedido');
        $excecao_comissao = $excecao_comissao->first();
        if(!empty($excecao_comissao)){
            $comissao_porcentagem = $excecao_comissao->comissao;
        }

    	$pedido_item->pedido = $fields['pedido'];
        $pedido_item->cod_produto = strtoupper($fields['produto_codigo']); 
    	$pedido_item->usuario = Auth::id();
    	$pedido_item->quantidade = parserNumber($fields['quantidade']);
    	$pedido_item->preco_unitario = $preco_unitario;
    	$pedido_item->updated_by = Auth::id();
        $pedido_item->valor_icms = $valor_icms;
        $pedido_item->base_calculo_icms = $base_calculo_icms;
        $pedido_item->valor_ipi = 0;
        $pedido_item->aliquota_icms = $aliquotaObj->aliquota;
        $pedido_item->aliquota_ipi = 0;
        $pedido_item->valor_frete = $valor_frete;
        $pedido_item->valor_total = round( ( $preco_unitario * parserNumber($fields['quantidade']) ), 2);
        $pedido_item->coluna = $coluna_preco;
        $pedido_item->comissao = $comissao_porcentagem;
        $pedido_item->ipi_produto = $pedidoObj->estabelecimento == 3 ? $produtoObj->ipi : 0;
        $pedido_item->preco_base = $produtoObj->precovenda;
        $pedido_item->coluna_a = $coluna_a;
        $pedido_item->coluna_b = $coluna_b;
        $pedido_item->coluna_c = $coluna_c;
        $pedido_item->preco_promocao = $preco_promocao;
        $pedido_item->produto_sem_estoque = $produto_sem_estoque;

        $pedido_item->codigo_tecidos_base = $fields['produto_codigo_base'];
        $pedido_item->codigo_desenho = $fields['produto_codigo_desenho'];

        $pedido_item->preco_original = $preco_original;

        $pedido_item->tem_ipi = $tem_ipi;

    	$pedido_item->save();

    	return "";
    }

    public function validarProdutoNasajonFuturo($origem, $cod_produto, $estado, $aliquota, $regiao, $pedidoObj, $preco_unitario, $quantidade, $cidade, $preco_custo = false){

        $razao_cnpj_textil = '06311274';

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $produtoObj = ProdutoEspecificacao::where('codigo_produto', strtoupper($cod_produto))->first();

        $media_prazo = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;

        $frete = $pedidoObj->frete_preco;

        $arr['origem'] = $origem;
        $arr['produto'] = strtoupper($cod_produto);
        $arr['estado'] = $estado;
        $arr['cidade'] = $cidade;
        $arr['moeda'] = ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
        $arr['frete'] = $frete;
        $arr['regiao'] = $regiao;
        $arr['tipo_cliente'] = (
            $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        $arr['prazo_medio'] = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;

        $promocao = false;
        if($pedidoObj->pedido_futuro === false){
            $promocao = true;
        }
        $arr['promocao'] = $promocao;
        $arr['estabelecimento'] = $pedidoObj->estabelecimento;
        $arr['cliente'] = $pedidoObj->cod_cliente;
        $arr['codigo_vendedor'] = $pedidoObj->usuario_detalhes->codigo_representante;
        $arr['estabelecimento'] = $pedidoObj->estabelecimento;

        $listaDePrecosObj = new ListagemDePrecosController();
        $filter_request = new ListaDePrecosRequest($arr);

        $arr_result = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $pedidoObj->estabelecimento)->get()->first();

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            $razao_cnpj_textil !== $raiz_cnpj
        ){
            if(in_array($produtoObj->grupo, $this->grupos_especiais)){
                if(parserNumber($arr_result[0]['coluna_a']) > parserNumber($preco_unitario)){
                    $desconto = ((1 - (parserNumber($preco_unitario) / parserNumber($arr_result[0]['coluna_a']))) * 100);
                    if($desconto > $this->desconto_especiais){
                        $preco_unitario_erro = "desconto máximo para este produto é {$this->desconto_especiais}%";
                    }
                }
            }
        }

        $valor_minimo = ceil((parserNumber($arr_result[0]['coluna_a']) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100) / 100;

        $arr['promocao'] = false;
        $filter_request = new ListaDePrecosRequest($arr);
        $arr_result_max = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);

        $valor_maximo = ceil((parserNumber($arr_result_max[0]['coluna_c']) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100) / 100;
        if(
            $razao_cnpj_textil !== $raiz_cnpj
        ){
            if (floatval($valor_minimo) > parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item, está abaixo do mínimo permitido. Favor verificar' . in_array($pedidoObj->cod_cliente, $this->codigo_grupo_textil_mn);
            }
            

            if (floatval($valor_maximo) < parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item está acima do máximo permitido. Favor verificar';
            }
        }

        if(isset($preco_unitario_erro)){
            $error['preco_unitario'] = $preco_unitario_erro;
            return ['status' => 'error', 'errors' => $error];
        }   

        if(isset($error)){
            return ['status' => 'error', 'errors' => $error,];      
        }

        return '';
    }

    public function validarProdutoNasajonProducaoFuturo($PedidoPortalObj, $produto_codigo, $produto_codigo_base, $produto_codigo_desenho, $preco_unitario, $quantidade, $regiao, $preco_custo = false){
        switch ($PedidoPortalObj->estabelecimento) {
            case '3':
                $origem = "RO";
            break;
            case '4':
                $origem = "TO";
            break;
            default:
                $origem = "SP";
            break;
        }
        $estado = $PedidoPortalObj->cliente->uf;
        $cidade = $PedidoPortalObj->cliente->cidade;

        $media_prazo = $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;

        $frete = $PedidoPortalObj->frete_preco;

        $arr['origem'] = $origem;
        $arr['produto'] = strtoupper($produto_codigo);
        $arr['estado'] = $estado;
        $arr['cidade'] = $cidade;
        $arr['moeda'] = ($PedidoPortalObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
        $arr['frete'] = $frete;
        $arr['regiao'] = $regiao;
        $arr['tipo_cliente'] = (
            $PedidoPortalObj->cliente->inscricaoestadual == 'ISENTO' ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 2 ||
            intval($PedidoPortalObj->cliente->indicadorinscricaoestadual) == 9
        ) ? 'isento' : 'juridico';
        $arr['prazo_medio'] = $PedidoPortalObj->condicao_pagamento_detalhes->media ?? 0;

        $promocao = false;
        if($PedidoPortalObj->pedido_futuro === false){
            $promocao = true;
        }
        $arr['promocao'] = $promocao;
        $arr['estabelecimento'] = $PedidoPortalObj->estabelecimento;
        $arr['cliente'] = $PedidoPortalObj->cod_cliente;
        $arr['codigo_vendedor'] = $PedidoPortalObj->usuario_detalhes->codigo_representante;

        $listaDePrecosObj = new ListagemDePrecosController();
        $filter_request = new ListaDePrecosRequest($arr);

        $retultado_preco = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $parametrosPedidoPortalObj = ParametrosPedido::where('estabelecimento', $PedidoPortalObj->estabelecimento)->get()->first();
        $retultado_preco = reset($retultado_preco);

        $valor_minimo = ceil((parserNumber($retultado_preco['coluna_a']) * (1 - $parametrosPedidoPortalObj->valor_minimo_porcentagem/100) ) * 100) / 100;

        $arr['promocao'] = false;
        $filter_request = new ListaDePrecosRequest($arr);
        $resultado_preco_maxio = $listaDePrecosObj->filter($filter_request, true, false, true, false, false, false, false, false);
        $resultado_preco_maxio = reset($resultado_preco_maxio);

        $valor_maximo = ceil((parserNumber($resultado_preco_maxio['coluna_c']) * (1 + $parametrosPedidoPortalObj->valor_maximo_porcentagem/100) ) * 100) / 100;
        if(!in_array($PedidoPortalObj->cod_cliente, $this->codigo_grupo_textil_mn)){
            if (floatval($valor_minimo) > parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item, está abaixo do mínimo permitido. Favor verificar' . in_array($PedidoPortalObj->cod_cliente, $this->codigo_grupo_textil_mn);
            }

            if (floatval($valor_maximo) < parserNumber($preco_unitario)){
                $preco_unitario_erro = 'O preço unitário do item está acima do máximo permitido. Favor verificar';
            }
        }

        if(isset($preco_unitario_erro)){
            $error['preco_unitario'] = $preco_unitario_erro;
            return ['status' => 'error', 'errors' => $error];
        }

    }

    public function geraProposta(PedidoSalvarRequest $request){
        $pedidoObj = PedidoPortal::findOrFail($request->id);
        switch ($pedidoObj->estabelecimento) {
            case '3':
                $origem = 'RO';
                break;
            case '4':
                $origem = 'TO';
                break;            
            default:
                $origem = 'SP';
                break;
        }
		if($pedidoObj->rj_x_sp == true){
			$origem = 'SP';
		}

        $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $pedidoObj->cliente->cpf_cnpj);
        $raiz_cnpj = substr($raiz_cnpj, 0, 8);

        $preco_custo = false;
        if(
            !in_array(str_pad($pedidoObj->estabelecimento, 2, '0', STR_PAD_LEFT), ['01', '02']) &&
            $this->razao_cnpj_textil === $raiz_cnpj
        ){
            $preco_custo = true;
        }


        if($preco_custo == false){
            $media_prazo = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;
        }else{
            $media_prazo = 0;
        }

        $aprovacaoDePedidoControllerObj = new AprovacaoDePedidoController;

        $natop = $aprovacaoDePedidoControllerObj->getCodigoOperacao($pedidoObj);

        if($media_prazo < 15){
            $coluna = 'prazo_vista';
        }
        else if($media_prazo >= 15 && $media_prazo < 30){
            $coluna = 'prazo_15';
        }
        else if($media_prazo >= 30 && $media_prazo < 45){
            $coluna = 'prazo_30';
        }
        else if($media_prazo >= 45 && $media_prazo < 60){
            $coluna = 'prazo_45';
        }
        else if($media_prazo >= 60){
            $coluna = 'prazo_60';
		}

        $requestTotalizadores = new Request(['id' => $pedidoObj->id]);
        $totalizadores = json_decode($this->totalizadores($requestTotalizadores)->content(), TRUE);
        if ($totalizadores['valor_total_pedido'] < 0){
            $errors[] = ['total_pedido' => ["O valor do pedido deve ser maior que zero"]];
		}
		
		$margem_prazo = MargemPrazo::where('estabelecimento', $pedidoObj->estabelecimento)->first();
        $pedidoObj->fill([
            'codigo_operacao' => $natop,
            'base_icms' => 0,
            'data_pedido' => date('Y-m-d'),
            'valor_icms' => $pedidoObj->itens_pedido->sum('valor_icms'),
            'base_icmsst' => $pedidoObj->itens_pedido->sum('base_icmsst'),
            'valor_icmsst' => $pedidoObj->itens_pedido->sum('valor_icmsst'),
            'valor_frete' => floatval($pedidoObj->tipo_frete == 'C' ? $pedidoObj->valor_frete : 0),
            'valor_seguro' => 0,
            'outros_valores' => 0,
            'valor_ipi' => $pedidoObj->itens_pedido->sum('base_ipi'),
            'valor_total_produtos' => $pedidoObj->valor_total->total,
            'valor_total_nota' => $pedidoObj->itens_pedido->sum('valor_total') + floatval($pedidoObj->tipo_frete == 'C'? $pedidoObj->valor_frete : 0) - floatval($pedidoObj->valor_desconto),
            'total_pedido' => $pedidoObj->itens_pedido->sum('valor_total'),
        ]);
        $pedidoObj->save();
        $erro_estoque = [];
        $erro_preco = [];
        $clienteObj = ClienteNasajon::where('codigo', $pedidoObj->cod_cliente)->where('bloqueado', 'false')->first();
        
        $preco_frete = $this->fretePreco($pedidoObj);
        $preco_cif_fob = $preco_frete['preco'];
        $frete = $preco_frete['frete'];

        $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $pedidoObj->estabelecimento)->get()->first();
        $media = $pedidoObj->condicao_pagamento_detalhes->media ?? 0;
        foreach($pedidoObj->itens_pedido as $key => $value){
            $produto = ProdutoEspecificacao::where('codigo_produto', strtoupper($value->cod_produto))->first();
            if(in_array($produto->grupo, $this->grupos_especiais) &&  $media > $this->media_especial){
                $erro_estoque[$value->cod_produto] = "Condição média para este produto tem que ser menor ou igual a {$this->media_especial} dias";
            }
            $requestObj = new Request([
                'cod_cliente' => $pedidoObj->cod_cliente, 
                'codigo' => $value->cod_produto, 
                'estabelecimento' => $pedidoObj->estabelecimento, 
                'prazo' => $media_prazo,  
                'tipo_frete' => $frete, 
                'estabel' => str_pad($pedidoObj->estabelecimento, 2, "0", STR_PAD_LEFT),
                'pedido_futuro' => $pedidoObj->pedido_futuro, 
                'data_previsao_entrega' => $pedidoObj->data_previsao_entrega,
                'estado' => $pedidoObj->cliente['estado'],
                'cod_exato' => true,
                'pedido' => $pedidoObj->id
            ]);
            $infoProduto = new ProdutoController();
            try {
                $result_estoque = $infoProduto->retornarDadosEstoqueNasajon($produto, $pedidoObj->estabelecimento, $pedidoObj, false, $value->codigo_tecidos_base);
            } catch (Exception $e) {
				return response()
					->json(
						[
							'status' => 'error', 
							'message' => 'Erro na execução: ' . $e->message . ' - ' . var_dump($result_estoque),
							'error' => [],
							'response' => []
						],
					422);
            }
            if(
                empty($result_estoque)
			){
                $erro_estoque[$value->cod_produto] = 'O produto não tem estoque para completar o pedido. Favor verificar.';
            }
            else {
                if ($pedidoObj->pedido_futuro === true){
                    $coluna_quinzena = date('\k_Y_m_', strtotime($pedidoObj->data_previsao_entrega)) . (date('j', strtotime($pedidoObj->data_previsao_entrega)) <= 15? "1": "2");
                    if (str_replace(',', '.', str_replace('.', '', $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) <= 0){
                        $erro_estoque[$value->cod_produto] = 'O produto não tem nenhum estoque disponível na compra da quinzena. Favor verificar.';
                    }
                    else if (str_replace(',', '.', str_replace('.', '', $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'])) < $value->quantidade){
                        $erro_estoque[$value->cod_produto] = 'O produto '.$value->cod_produto.' só tem '. $result_estoque['quinzenas'][$coluna_quinzena]['quantidade'] .' ' . $result_estoque['unidade'] . ' disponível na compra da quinzena e não atenderá o pedido. Favor verificar.';
                    }

                    $numero_compra = '';

                    if(isset($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'])){
                        foreach($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'] as $verificacao_pedido){
                            if($verificacao_pedido['quantidade'] >= $value->quantidade){
                                $numero_compra = $verificacao_pedido['pedido'];
                            }
                        }
                        if(empty($numero_compra)){
                            $verificacao_total = 0;
                            foreach($result_estoque['quinzenas'][$coluna_quinzena]['pedidos_separado'] as $verificacao_pedido){
                                $verificacao_total += $verificacao_pedido['quantidade'];
                                if($verificacao_total < $value->quantidade){
                                    if(!empty($numero_compra)){
                                        $numero_compra .= '-'.$verificacao_pedido['pedido'].':'.$verificacao_pedido['quantidade'];
                                    }else{
                                        $numero_compra = $verificacao_pedido['pedido'].':'.$verificacao_pedido['quantidade'];
                                    }                                
                                }
                            }
                        }
                        $value->numero_compra = $numero_compra;
                        $value->save();
                    }
                    
                }
                else if($pedidoObj->pedido_futuro === false){
                    if((str_replace(',', '.', str_replace('.', '', $result_estoque['pronta_entrega']))) <= 0){
                        $erro_estoque[$value->cod_produto] = 'O produto não tem nenhum estoque disponível no estabelecimento. Favor verificar.';
                    }
                    else if((str_replace(',', '.', str_replace('.', '', $result_estoque['pronta_entrega'])))  < $value->quantidade && $pedidoObj->pedido_futuro === false){
                        $erro_estoque[$value->cod_produto] = 'O produto só tem '. $result_estoque['pronta_entrega'] .' ' . $result_estoque['unidade'] . ' em estoque e não atenderá o pedido. Favor verificar.';
                    }
                }
            }
            if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
                $erro_estoque = [];
            }

            if(!in_array($pedidoObj->cod_cliente, $this->codigo_grupo_textil_mn) && $preco_custo === false){
				
                $internacional = 'false';
                $estadoObj = CepEstado::find($clienteObj->uf);

                $aliquotaObj = AliquotaPreco::where('origem', $origem)
                    ->where('estado', $clienteObj->uf)
                    ->where('internacional', $internacional)
                    ->first();


                $arr['cidade'] = $pedidoObj->clienteSemBloqueio->uf??null;

                $arr['origem'] = $origem;
                $arr['produto'] = $value->cod_produto;
                $arr['estado'] = $clienteObj->uf;
                $arr['aliquota'] = $pedidoObj->tipo_venda == 'isento'? $aliquotaObj->icms_venda_cliente_isento : $aliquotaObj->icms_venda;
                $arr['moeda'] = ($pedidoObj->pedido_futuro === true && $origem == 'RO') ? 'dolar' : 'real';
                $arr['frete'] = $preco_cif_fob;
                $arr['regiao'] = $estadoObj->regiao;
                $arr['tipo_cliente'] = (
                    $pedidoObj->cliente->inscricaoestadual == 'ISENTO' ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 2 ||
                    intval($pedidoObj->cliente->indicadorinscricaoestadual) == 9
                ) ? 'isento' : 'juridico';
                
                $promocao = false;
                if($pedidoObj->pedido_futuro === false){
                    $promocao = true;
                }
                $arr['promocao'] = $promocao;
                $arr['cliente'] = $pedidoObj->cod_cliente;
                $arr['codigo_vendedor'] = $pedidoObj->usuario_detalhes->codigo_representante;
                $arr['prazo_medio'] = $media_prazo;

                if (strtolower(Auth::user()->tipo_usuario->nome) != 'vendedor interno'){
                    $arr['coluna'] = $coluna;
                }
                else{
                    $arr['coluna'] = 'coluna_a';
                }
                $arr['estabelecimento'] = $pedidoObj->estabelecimento;

                $listaDePrecosObj = new ListagemDePrecosController();
                $filter_request = new ListaDePrecosRequest($arr);
                $use_auth = true;
                $salvar_pesquisa = false;
                $retornar_array = true;
                $aprovacao = false;
                $exportacao = false;
                $deletados = false;
                $olharEstoque = false;

                $arr_result = $listaDePrecosObj->filter(
                    $filter_request,
                    $use_auth,
                    $salvar_pesquisa,
                    $retornar_array,
                    $aprovacao,
                    $exportacao,
                    $deletados,
                    $olharEstoque
                );
                if(empty($arr_result)){
                    continue;
                }

                if (strtolower(Auth::user()->tipo_usuario->nome) != 'vendedor interno'){
                    $valor_minimo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0]['coluna_a'])) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                    $valor_maximo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0]['coluna_c'])) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;
                }
                else{
                    $valor_minimo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0][$coluna])) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                    $valor_maximo = round((str_replace(",", ".", str_replace('.', '', $arr_result[0][$coluna])) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;            
                }


                if (floatval($valor_minimo) > floatval($value->preco_unitario)){
                    $erro_preco[$value->cod_produto] = 'O preço unitário do item está abaixo do mínimo permitido de ' . parserValor($valor_minimo) . '. Favor verificar';
                }
                if (floatval($valor_maximo) < floatval($value->preco_unitario)){
                    $erro_preco[$value->cod_produto] = 'O preço unitário do item está acima do máximo permitido de ' . parserValor($valor_maximo) . '. Favor verificar';
                }

                if(in_array($pedidoObj->tipo_venda, ['pedido_pilotagem'])){
                    $ProdutoNasajonObj = ProdutoNasajon::where('codigo', strtoupper($value->cod_produto))->first();
                    if(
                        !in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS', 'Kg', 'KG', 'PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                    ){
                        $erro_estoque[$value->cod_produto] = ("Unidade do produto inválida<br>Unidades aceitas para produção:<br>Kilo = kg<br>Metros = m<br>Unideade = un");
                    }
                    if(
                        $this->quantidade_metros_limite_pilotagem < $value->quantidade &&
                        in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS'])
                    ){
                        $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_metros_limite_pilotagem."m");
                    }
                    if(
                        $this->quantidade_kg_limite_pilotagem < $value->quantidade &&
                        in_array($ProdutoNasajonObj->unidade, ['Kg', 'KG'])
                    ){
                        $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_kg_limite_pilotagem."Kg");
                    }
                    if(
                        $this->quantidade_undiade_limite_pilotagem < $value->quantidade &&
                        in_array($ProdutoNasajonObj->unidade, ['PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                    ){
                        $erro_estoque[$value->cod_produto] = ("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_undiade_limite_pilotagem."");
                    }

                }
            }
        }
		$pedidoObj->observacao = $this->criandoObservacaoPedido($pedidoObj);

		$errors = [];
        if(count($erro_estoque) > 0){
            $errors['estoque'] = $erro_estoque;
        }

        if(count($erro_preco) > 0){
            $errors['preco'] = $erro_preco;
        }

        if (count($errors) > 0){
            return response()->json([
                'status' => 'error', 
                'message' => 'Informações inválidas',
                'errors' => $errors,
                'reponse' => []
            ], 422);
        }
        if(in_array($pedidoObj->tipo_venda, ['producao', 'producao_triangular', 'pre_pago_producao', 'pre_pago_producao_triangular'])){
            $data = Carbon::now();
            $data->addDays(2);
            if($data->format('N') == 6){
                $data->addDays(2);
            }else if($data->format('N') == 7){
                $data->addDays(1);
            }
            $pedidoObj->data_previsao_entrega = $data->format('Y-m-d');
        }

		if(
			!empty($pedidoObj->cliente_telefone)
		){
			$ClienteEdicaoControllerObj = new ClienteEdicaoController();
			$atulizacao_telefone = $ClienteEdicaoControllerObj->atualizaTefoneCliente($pedidoObj->cliente, $pedidoObj->cliente_telefone);
		}
        if(!in_array($pedidoObj->tipo_venda, $this->tipo_vendas_triangular)){
            $pedidoObj->conta_e_ordem = false;
            $pedidoObj->cod_cliente_conta_e_ordem = '';
        }

        if($pedidoObj->cliente->vendedor_codigo !== $pedidoObj->usuario_detalhes->codigo_representante){
            $validacaoAgenteVenda = UserController::validaAgenteVenda($pedidoObj->cliente->vendedor_codigo);
            if($validacaoAgenteVenda !== true && $validacaoAgenteVenda > 0){
                $pedidoObj->agente_venda_id = $validacaoAgenteVenda;
            }
        }

        $validacaoPromotorVenda = UserController::validaPromotorVenda($pedidoObj->usuario_detalhes->codigo_representante);
        if($validacaoPromotorVenda !== true && $validacaoPromotorVenda > 0){
            if($pedidoObj->cliente->vendedor_codigo !== '001'){
                $user = User::where("codigo_representante", $pedidoObj->cliente->vendedor_codigo)->first();
                $pedidoObj->promotor_venda_id = $user->id;
            }
        }

        $pedidoObj->motivo_rejeicao = null;
        $pedidoObj->status_pedido = 14;
        $pedidoObj->save();

        $verificacao_proposta = PedidoProposta::where('pedido_id', $pedidoObj->id)->get();

        foreach($verificacao_proposta as $proposta){
            $proposta->deleted_by = Auth::id();
            $proposta->save();
            $proposta->delete();    
        }

        $pedidoPropostaObj = new PedidoProposta;
        $pedidoPropostaObj->pedido_id = $pedidoObj->id;
        $pedidoPropostaObj->created_by = Auth::id();
        $pedidoPropostaObj->save();

        return response()
            ->json(
                [
                    'status' => 'success', 
                    'message' => '',
                    'error' => [],
                    'response' => []
                ],
                200);
    }

    public function aprovacaoProposta(Request $request, $hash){
        try{
            $pedido = $this->descriptaLinkProposta($hash);
        }catch(\Exception $e){
            return abort(404);
		}
        $estabelecimentos = returnEmpresasNasajonView();

        $pedidoObj = PedidoPortal::with(['condicao_pagamento_detalhes', 'itens_pedido', 'cliente', 'usuario_detalhes', 'itens_pedido.especificacoes', 'itens_pedido.info_produtoNasjon', 'detalhesTransportador', 'status_pedido_detalhes'])->where('id', $pedido)->get()->first();

        if (empty($pedidoObj)){
            return abort(404);
        }

        $tipo_frete = [
            '' => '',
            'P' => "PAGO",
            'A' => "A PAGAR",
            'C' => "COBRADO",
            'T' => "TERCEIRO",
            'S' => "SEM FRETE"
        ];

        $tipo_venda_lista = [
            '' => 'Normal',
            'venda' => 'Normal',
            'triangular' => 'Triangular',
            'isento' => 'Cliente Isento'
        ];

        $total = 0;
        $quantidade_total = 0;
        $peso_total = 0;

        $pedidoObj->itens_pedido->each(function($item) use(&$produto, &$total, $pedidoObj, &$quantidade_total, &$peso_total){
            $produto[] = [
                'grupo' => $item->especificacoes->grupo,
                'cod_produto' => $item->cod_produto,
                'descricao' => $item->especificacoes->descricao,
                'marca' => $item->especificacoes->marca,
                'linha' => $item->especificacoes->linha,
                'quantidade' => $item->quantidade,
                'preco_unitario' => $pedidoObj->estabelecimento === 3 && $item->tem_ipi?  ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) : $item->preco_unitario,
                'valor_total' => $pedidoObj->estabelecimento === 3 && $item->tem_ipi? ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) * $item->quantidade : $item->preco_unitario * $item->quantidade,
                'peso' => $item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0),
            ];
            $valor_total_item = $pedidoObj->estabelecimento === 3 && $item->tem_ipi? ($item->preco_unitario * (1 + ($item->ipi_produto / 100))) * $item->quantidade : $item->preco_unitario * $item->quantidade;
            $total = $total + $valor_total_item;
            $quantidade_total += $item->quantidade;
            $peso_total += $item->quantidade * (!empty($item->info_produtoNasjon->pesoliquido) ? $item->info_produtoNasjon->pesoliquido : 0);
        });

        $response = [
            'id' => $pedidoObj->id,
            'vendedor' => empty($pedidoObj->usuario_detalhes)? '': ($pedidoObj->usuario_detalhes->codigo_representante) . ' - ' . ($pedidoObj->usuario_detalhes->name),
            'status' =>[
                "id" => $pedidoObj->status_pedido,
                "descricao" => $pedidoObj->status_pedido_detalhes->status
            ],
            'pedido_futuro' => '',
            'estabelecimento' => $estabelecimentos[intval($pedidoObj->estabelecimento)],
            'cod_cliente' => $pedidoObj->cliente->codigo,
            'cliente' => ($pedidoObj->cliente->nome),
            'cpf_cnpj' => $pedidoObj->cliente->cpf_cnpj,
            'condicao_pagamento_descr' => $pedidoObj->condicao_pagamento_detalhes->descricao ?? '',
            'transportadora' =>
            [
                'nome' => $pedidoObj->detalhesTransportador->nome .' - '. $pedidoObj->detalhesTransportador->cnpj,
                'tipo_frete' => $tipo_frete[$pedidoObj->tipo_frete],
                'valor_frete' => parserValor($pedidoObj->valor_frete)
            ],
            'tipo_frete' => $tipo_frete[$pedidoObj->frete],
            'nome_comprador' => $pedidoObj->nome_comprador??'&nbsp;',
            'email_comprador' => $pedidoObj->email_comprador??'&nbsp;',
            'data_pedido' => Carbon::parse($pedidoObj->data_pedido)->format('d/m/Y'),
            'data_previsao_entrega' => !empty($pedidoObj->data_previsao_entrega) ? Carbon::parse($pedidoObj->data_previsao_entrega)->format('d/m/Y') : '&nbsp;',
            'observacao' => ($pedidoObj->observacao)??'&nbsp;',
            'itens' => $produto,
            'valor_total_itens' => parserValor($total),
            'valor_desconto' => parserValor(0),
            'valor_frete' => parserValor($pedidoObj->frete_preco),
            'valor_total_nota' => parserValor($pedidoObj->valor_total_nota),
            'total' => parserValor($total),
            'quantidade_total' => parserValor($quantidade_total),
            'peso_total' => parserValor($peso_total) . ' kg',
            'criado_por' => $pedidoObj->criadoPor->name,
            'criado_em' => date('d/m/Y H:i:s', strtotime($pedidoObj->created_at)),
        ];

        return view("programs.proposta_pedido.aprovacao_proposta")->with(['pedido' => $response]);
    }

    public function aprovarProposta(Request $request){
        $fields = $request->only('id');

        $pedidoPortal = PedidoPortal::find($fields['id']);

        $pedidoPropostaObj = PedidoProposta::where('pedido_id', $fields['id'])->first();
        $pedidoPropostaObj->aprovacao = true;
        $pedidoPropostaObj->aprovacao_data = Carbon::now();
        $pedidoPropostaObj->ip = $request->ip();
        $pedidoPropostaObj->save();

        if($pedidoPortal->pedido_futuro === true){
            $pedidos_futuros = $this->separacaoPedidoFuturoPorPCMN($pedidoPortal);
        }else{
            
            $pedidoPortal->status_pedido = 2;
            $pedidoPortal->save();
    
            Artisan::queue('pedido:validacao', ['pedido' => $pedidoPortal->id]);
        }
        

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function recusarProposta(Request $request){
        $fields = $request->only('id');

        $pedidoPortal = PedidoPortal::find($fields['id']);
		$pedidoPortal->status_pedido = 15;
		$pedidoPortal->save();

        $pedidoPropostaObj = PedidoProposta::where('pedido_id', $fields['id'])->first();
        $pedidoPropostaObj->aprovacao = false;
        $pedidoPropostaObj->aprovacao_data = Carbon::now();
        $pedidoPropostaObj->ip = $request->ip();
        $pedidoPropostaObj->save();

        $response = [
            "status" => 'success',
            "message" => '',
            "error" => [],
            "response" => []
        ];
        return response()->json($response);
    }

    public function geracaoPropostaLink(Request $request){
        $pedidoObj = PedidoPortal::findOrFail($request->id);

        $this->enviarEmailProposta($pedidoObj);

        $link_proposta = route("pedido_portal.aprovacao_proposta", $this->encriptaLinkProposta($pedidoObj->id));

        return view('programs.pedido_portal.modal.proposta_link')->with(['link_proposta' => $link_proposta]);
    }

    private function enviarEmailProposta($pedido){
		try{
			$pedidos = [];

			$EmailObj = new EmailController();

			if (!empty($pedido)){

				$email_send = [];
				$nome_cliente = '';
				$cliente = $pedido->cliente;
				if(!empty($pedido->email_comprador)){
					$email_send[] = $pedido->email_comprador;
				}elseif(!empty($cliente->email)){
					$email_send[] = $cliente->email;
				}

				$email_send[] = $pedido->usuario_detalhes->email;

				if(!empty($pedido->nome_comprador)){
					$nome_cliente = $pedido->nome_comprador;
				}elseif(!empty($cliente->email)){
					$nome_cliente = $cliente->nome;
				}
		
				$variaveis = [
					'nome_cliente' => $nome_cliente,
					'numero_pedido' => $pedido->id,
					'link_proposta' => route("pedido_portal.aprovacao_proposta", $this->encriptaLinkProposta($pedidoObj->id))
				];

				if(count($email_send) > 0){
					$EmailObj->sendEmailToken($pedido->estabelecimento, "pedido_proposta", $email_send, $variaveis);
				}
				
			}
		}catch(\Exception $e){

		}

	}

    private function encriptaLinkProposta($id){
        $retorno = $id;
        $retorno = $retorno  * 2;
        $retorno = $retorno + 35;
        $retorno = $retorno * 3;

        return dechex($retorno);
    }

    private function descriptaLinkProposta($link){
        $retorno = $link;
        $retorno = hexdec($retorno);
        $retorno = $retorno / 3;
        $retorno = $retorno - 35;
        $retorno = $retorno  / 2;

        return $retorno;
    }

    public function corrigirStatusPedidoPago(Request $request){
        $id = $request->only(['id']);

        try{
            $pedido = PedidoPortal::find($id['id']);
            $pedido->status_pedido = 3;
            $pedido->save();

            $response = [
                "status" => 'success',
                "message" => '',
                "error" => [],
                "response" => []
            ];
            return response()->json($response,200);

        }catch(Exception $e){
            $response = [
                "status" => 'error',
                "message" => '',
                "error" => ['error' => $e],
                "response" => []
            ];
            return response()->json($response,422);
        }
    }

    public function auditoriaComissao(){
        $pedidoObj = PedidoPortal::with(['itens_pedido' => function($query){
            $query->whereNotNull('campanha_id');
        }])
        ->whereHas('itens_pedido', function($query){
            $query->whereNotNull('campanha_id');
        })
        ->where('status_pedido', 3)
        ->get();

        foreach($pedidoObj as $pedido){
            foreach($pedido->itens_pedido as $produto){
                $comissao_original = is_null($produto->comissao_original) ? 0 : $produto->comissao_original;
                $incentivo_campanha = is_null($produto->incentivo_campanha) ? 0 : $produto->incentivo_campanha;

                if($comissao_original + $incentivo_campanha != $produto->comissao
                ){
                    $auditoria = new AuditoriaComissao;
                    $auditoria->pedido_item_id = $produto->id;
                    $auditoria->pedido_id = $produto->pedido;
                    $auditoria->campanha_id = $produto->campanha_id;
                    $auditoria->comissao_anterior = $produto->comissao;
                    $auditoria->comissao_nova = $produto->comissao_original + $produto->incentivo_campanha;
                    $auditoria->save();

                    if($comissao_original + $incentivo_campanha > 0){
                        $produto->comissao = $comissao_original + $incentivo_campanha;
                        $produto->updated_by = 1;
                        $produto->save();
                    }
                }
                if($comissao_original + $incentivo_campanha == $produto->comissao && $incentivo_campanha == 0.11){
                    
                    $produto->comissao_original = $incentivo_campanha;
                    $produto->updated_by = 1;
                    $produto->save();
                    
                    $auditoria = new AuditoriaComissao;
                    $auditoria->pedido_item_id = $produto->id;
                    $auditoria->pedido_id = $produto->pedido;
                    $auditoria->campanha_id = $produto->campanha_id;
                    $auditoria->comissao_anterior = $produto->comissao;
                    $auditoria->comissao_nova = $produto->comissao_original + $produto->incentivo_campanha;
                    $auditoria->save();

                    $produto->comissao = $produto->comissao_original + $produto->incentivo_campanha;
                    $produto->updated_by = 1;
                    $produto->save();
                   
                }
            }
        }
    }
}
