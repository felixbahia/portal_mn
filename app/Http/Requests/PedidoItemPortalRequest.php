<?php

namespace App\Http\Requests;

use App\CampanhasProduto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\PedidoPortal;
use App\ProdutoNasajon;
use App\ProdutoEspecificacao;
use App\ProdutoUsoConsumoNasajon;

class PedidoItemPortalRequest extends FormRequest
{
    private $quantidade_metros_limite_pilotagem = 6;
    private $quantidade_kg_limite_pilotagem = 2;
    private $quantidade_undiade_limite_pilotagem = 1;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
		$PedidoPortal = PedidoPortal::find($this->pedido);
        $ProdutoEspecificacaoObj = ProdutoEspecificacao::with(['ficha_tecnica'])
            ->where('ativo', true)
            ->where('codigo_produto', $this->produto_codigo)
            ->first();
        return [
            'pedido' => [
                'required',
            ],
            'produto_codigo' => [
                'required',
                Rule::unique('pedido_item', 'cod_produto')
                ->where('pedido', $this->pedido)
                ->whereNull("deleted_at")
                ->ignore($this->id, 'id'),
                function($attribute, $value, $fail) use($PedidoPortal,$ProdutoEspecificacaoObj) {
                    if($PedidoPortal->status_pedido == 3){
                        return $fail("Pedido aprovado, não poder ser editado");
                    }
					if($PedidoPortal->status_pedido == 11){
						return $fail("Pedido aguardando liberação de pagamento, não poder ser editado");
					}
                    if($PedidoPortal->status_pedido == 9){
                        if(!empty($PedidoPortal->cielo)){
                            foreach($PedidoPortal->cielo as $cielo){
                                if($cielo->cielo_status_id == 4){
                                    return $fail("Pedido foi pago, não poder ser editado"); 
                                }
                            }
                        }
					}
					$clienteObj = $PedidoPortal->cliente;
                    $razao_cnpj_textil = '06311274';
                    $raiz_cnpj = str_replace([' ', '-', '/', '.'], '', $clienteObj->cpf_cnpj);
                    $raiz_cnpj = substr($raiz_cnpj, 0, 8);
					if($raiz_cnpj == $razao_cnpj_textil){
						if($ProdutoEspecificacaoObj->industrializado == true && empty($ProdutoEspecificacaoObj->ficha_tecnica)){
                            return $fail("Produto industrializado sem ficha técnica para transferência. Entre em contato com o setor responsável pelo cadastro");
						}
					}
                    $produtoCampanha = CampanhasProduto::with('campanha')
                    ->where('produto_codigo', $value)
                    ->whereHas('campanha', function($query) {
                        $query->where('ativo', true);
                    })
                    ->first();
                    if(!empty($produtoCampanha->produto_codigo) && !in_array($PedidoPortal->tipo_venda, [
                            'venda',
                            'isento',
                            'pedido_orgaopublico',
                            'pronta_entrega_triangular',
                            'pronta_entrega_venda',
                            'pedido_pilotagem',
                            'pre_pago',
                            'rj_x_sp',
                            'pre_pago_rj_x_sp'
                        ])){
                        return $fail("Este produto pertence a Campanha ".$produtoCampanha->campanha->nome.', a venda somente Pronta Entrega');
                    }
                }
            ],
            'quantidade' => [
                'required',
                function($attribute, $value, $fail) use($PedidoPortal){
                    if(!is_float(str_replace(",", ".", $value)) && floatval(str_replace(",", ".", str_replace(".", "", $value))) < 0.01){
                        return $fail("Valor deve ser um número maior que zero");
                    }
                    if(in_array($PedidoPortal->tipo_venda, ['producao', 'producao_triangular'])){
                        $ProdutoNasajonObj = ProdutoNasajon::where('codigo', strtoupper($this->produto_codigo_base))->first();
                        if(
                            strtolower($ProdutoNasajonObj->unidade) !== 'm' &&
                            strtolower($ProdutoNasajonObj->unidade) !== 'kg'
                        ){
                            return $fail("Unidade do produto inválida<br>Unidades aceitas para produção:<br>Kilo = kg<br>Metros = m");
                        }
                        // if(
                        //     strtolower($ProdutoNasajonObj->unidade) == 'm' &&
                        //     (parserNumber($value) % 10) !== 0
                        // ){
                        //     return $fail("Quantidade para produção tem que ser multiplo de 10");
                        // }
                        if(
                            strtolower($ProdutoNasajonObj->unidade) == 'kg' &&
                            parserNumber($value) < 5
                        ){
                            return $fail("Quantidade para produção tem que ser maior ou igual a 5");
                        }
                        
                    }
                    if(in_array($PedidoPortal->tipo_venda, ['pedido_pilotagem'])){
                        $ProdutoNasajonObj = ProdutoNasajon::where('codigo', strtoupper($this->produto_codigo))->first();
                        if(empty($ProdutoNasajonObj)){
                            $ProdutoNasajonObj = ProdutoUsoConsumoNasajon::where('codigo', strtoupper($this->produto_codigo))->first();
                        }
                        $codigo_uso_consumo = [
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
                        if(empty($ProdutoNasajonObj) && in_array($this->produto_codigo, $codigo_uso_consumo)){
                            if(
                                $this->quantidade_undiade_limite_pilotagem < parserNumber($value)
                            ){
                                return $fail("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_undiade_limite_pilotagem."");
                            }
                        }else{
                            if(
                                !in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS', 'Kg', 'KG', 'PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                            ){
                                return $fail("Unidade do produto inválida<br>Unidades aceitas para produção:<br>Kilo = kg<br>Metros = m<br>Unideade = un");
                            }
                            if(
                                $this->quantidade_metros_limite_pilotagem < parserNumber($value) &&
                                in_array($ProdutoNasajonObj->unidade, ['m', 'M', 'METRO', 'METROS', 'mt', 'MT', 'MTS'])
                            ){
                                return $fail("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_metros_limite_pilotagem."m");
                            }
                            if(
                                $this->quantidade_kg_limite_pilotagem < parserNumber($value) &&
                                in_array($ProdutoNasajonObj->unidade, ['Kg', 'KG'])
                            ){
                                return $fail("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_kg_limite_pilotagem."Kg");
                            }
                            if(
                                $this->quantidade_undiade_limite_pilotagem < parserNumber($value) &&
                                in_array($ProdutoNasajonObj->unidade, ['PC', 'PC\'', 'PÇ', 'PCS', 'PÇS', 'UM', 'UN', 'UN.', 'UND', 'UNI', 'UNID'])
                            ){
                                return $fail("Quantidade para pilotagem tem que ser menor ou igual a ".$this->quantidade_undiade_limite_pilotagem."");
                            }
                        }
                    }
                }
            ],
            'preco_unitario' => [
                'required', 
                function($attribute, $value, $fail){
                    if(!is_float(str_replace(",", ".", $value)) && floatval(str_replace(",", ".", str_replace(".", "", $value))) < 0.01){
                        return $fail("Valor deve ser um número maior que zero");
                    }
                }
            ],
            'produto_codigo_base' => [
                function($attribute, $value, $fail) use($PedidoPortal){
                    if(in_array($PedidoPortal->tipo_venda, ['producao', 'producao_triangular'])){
                        if(empty($value)){
                            return $fail('O campo Produto Base é obrigatório.');
                        }
                    }
                }
            ],
            'produto_codigo_desenho' => [
                function($attribute, $value, $fail) use($PedidoPortal){
                    if(in_array($PedidoPortal->tipo_venda, ['producao', 'producao_triangular'])){
                        if(empty($value)){
                            return $fail('O campo Desenho é obrigatório.');
                        }
                    }
                }
            ]
        ];
    }

    public function messages(){
        return [
            'produto_codigo.unique' => 'Este produto já está no pedido. Para modificar seus valores, edite-o.',
            'preco_unitario.required' => 'Por favor insira um valor',
            'preco_unitario.min' => 'O preço não pode ser zero',
            'produto_codigo_base.required' => __('validation.required', ['attribute' => 'Produto Base']),
            'produto_codigo_desenho.required' => __('validation.required', ['attribute' => 'Desenho']),

        ];
    }
}
