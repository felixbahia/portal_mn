<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use App\TitulosEmAbertoNasajon;
use App\ClienteNasajon;
use App\User;
use Carbon\Carbon;
use App\FormaPagamentoNasajon;
use App\ContasNasajon;
use App\VendedorTituloNasajon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProrrogacaoTitulosController extends Controller
{
	private $dias_prorrogacao = 90;

	public function index(Request $request){
		if(Auth::user()->hasPermissionTo("programas App\ProrrogacaoTitulos") === false){
			return abort(403);
		}
		$request->session()->flash('model', 'App\ProrrogacaoTitulos');
		$estabelecimentos = returnEmpresasTodosNasajonView();
		return view("programs.prorrogacao_titulos.index")->with(['estabelecimentos' => $estabelecimentos, 'bancos' => $this->returnBancos()]);
	}


    private function returnBancos(){
        $bancos = TitulosEmAbertoNasajon::selectRaw('distinct banco_nome as banco')->where('banco_nome', '<>',null)->get();
        $returnBancos = [];
        foreach($bancos as $banco){
            $returnBancos[$banco->banco] = strtoupper($banco->banco);
        }
        $banco = array_merge($returnBancos, $returnBancos);
        ksort($banco);
        return $banco;
    }

	private function codigosIntercomapny(){
		$clientes_exluir = ClienteNasajon::select('id')
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '06311274%'")
			->orWhereRaw("replace(replace(replace(cpf_cnpj, '.', ''), '-', ''), '/', '') ILIKE '05075884%'")
			->get();
		$clientes_exluir = $clientes_exluir->pluck('id')->toArray();
		return $clientes_exluir;
	}

	public function buscaValores(Request $request){
		$fields = $request->only(['data_de', 'data_ate', 'data_nova', 'estabelecimento', 'banco']);

		$fields['data_de'] = Carbon::createFromFormat("d/m/Y", $fields['data_de'])->setTime(0,0,0);
		$fields['data_ate'] = Carbon::createFromFormat("d/m/Y", $fields['data_ate'])->setTime(0,0,0);

		$data_nova = Carbon::createFromFormat("d/m/Y", $fields['data_nova'])->setTime(0,0,0);

		$TitulosEmAbertoNasajonObj = TitulosEmAbertoNasajon::
			whereBetween('vencimento', [$fields['data_de'], $fields['data_ate']])
			->whereNotIn('id_cliente', $this->codigosIntercomapny());
		
		if(isset($fields['estabelecimento'])){
			$fields['estabelecimento'] = str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT); 
			$TitulosEmAbertoNasajonObj->where('codigo', $fields['estabelecimento']);
		}
		if(isset($fields['banco'])){
			$TitulosEmAbertoNasajonObj->where('banco_nome', $fields['banco']);
		}

		$TitulosEmAbertoNasajonObj = $TitulosEmAbertoNasajonObj->get();
		$valores = [
			'titulos_seram_prorrogados' => 0,
			'valor_seram_prorrogados' => 0,
			'titulos_nao_seram_prorrogados' => 0,
			'valor_nao_seram_prorrogados' => 0,
		];
		$dias_prorrogacao = $this->dias_prorrogacao;
		$dataHoje = Carbon::now()->setTime(0,0,0);

		$TitulosEmAbertoNasajonObj->each(function($titulo) use (&$valores, $dias_prorrogacao, $dataHoje, $data_nova) {
			$vencimento = Carbon::parse($titulo->vencimento_original)->setTime(0,0,0);
			if(
				// $vencimento->diffInDays($data_nova) > $dias_prorrogacao ||
				$titulo->enviado_para_banco == false ||
				$titulo->enviado_para_cartorio == true
			){
				$valores['titulos_nao_seram_prorrogados'] += 1;
				$valores['valor_nao_seram_prorrogados'] += $titulo->valor;
			}else{
				$valores['titulos_seram_prorrogados'] += 1;
				$valores['valor_seram_prorrogados'] += $titulo->valor;
			}
		});
		$valores['valor_nao_seram_prorrogados'] = parserValor($valores['valor_nao_seram_prorrogados']);
		$valores['valor_seram_prorrogados'] = parserValor($valores['valor_seram_prorrogados']);
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => $valores
		]);
	}
	public function processarTitulos(Request $request){
		$fields = $request->only(['data_de', 'data_ate', 'data_nova', 'estabelecimento', 'banco', 'taxa_juros', 'despesas_adicionais' ]);

		$fields['data_de'] = Carbon::createFromFormat("d/m/Y", $fields['data_de'])->setTime(0,0,0);
		$fields['data_ate'] = Carbon::createFromFormat("d/m/Y", $fields['data_ate'])->setTime(0,0,0);

		$data_nova = Carbon::createFromFormat("d/m/Y", $fields['data_nova'])->setTime(0,0,0);
		$data_nova_multa = Carbon::createFromFormat("d/m/Y", $fields['data_nova'])->setTime(0,0,0)->addDay();

		$taxa_juros = (float) str_replace(',', '.', $fields['taxa_juros']);
		$taxa_juros_original = (float) str_replace(',', '.', $fields['taxa_juros']);
		$taxa_juros = $taxa_juros / 100;

		$despesas_adicionais = 0;
		if(!empty($fields['despesas_adicionais'])){
			$despesas_adicionais = (float) str_replace(',', '.', $fields['despesas_adicionais']);
		}

		$TitulosEmAbertoNasajonObj = TitulosEmAbertoNasajon::
			whereBetween('vencimento', [$fields['data_de'], $fields['data_ate']])
			->whereNotIn('id_cliente', $this->codigosIntercomapny());
		
		if(isset($fields['estabelecimento'])){
			$fields['estabelecimento'] = str_pad($fields['estabelecimento'], 2, 0, STR_PAD_LEFT); 
			$TitulosEmAbertoNasajonObj->where('codigo', $fields['estabelecimento']);
		}
		
		if(isset($fields['banco'])){
			$TitulosEmAbertoNasajonObj->where('banco_nome', $fields['banco']);
		}

		$TitulosEmAbertoNasajonObj = $TitulosEmAbertoNasajonObj->get();
		$dias_prorrogacao = $this->dias_prorrogacao;
		$dataHoje = Carbon::now()->setTime(0,0,0);

		$titulos = collect([]);

		$valores = [
			'titulos_seram_prorrogados' => 0,
			'valor_seram_prorrogados' => 0,
			'titulos_nao_seram_prorrogados' => 0,
			'valor_nao_seram_prorrogados' => 0,
		];

		$TitulosEmAbertoNasajonObj->each(function($titulo) use (&$valores, &$titulos, $dias_prorrogacao, $taxa_juros, $data_nova, $despesas_adicionais) {
			$vencimento = Carbon::parse($titulo->vencimento_original)->setTime(0,0,0);
			$dias_diferenca = $vencimento->diffInDays($data_nova);
			if(
				// $dias_diferenca <= $dias_prorrogacao &&
				$titulo->enviado_para_banco == true &&
				$titulo->enviado_para_cartorio == false
			){
				$valor_titulo = (float) $titulo->valor;
				$valor_adicional = ($valor_titulo * ($taxa_juros * ( $dias_diferenca - 1 ) ));
				$titulos->push([
					'id' => $titulo->titulo_id,
					'numero' => $titulo->numero,
					'adicional' => $valor_adicional,
					'vencimento_antes' => $vencimento,
					'titulo' => $titulo
				]);
				$valores['titulos_seram_prorrogados'] += 1;
				$valores['valor_seram_prorrogados'] += $titulo->valor + $valor_adicional + $despesas_adicionais;
			}else{
				$valores['titulos_nao_seram_prorrogados'] += 1;
				$valores['valor_nao_seram_prorrogados'] += $titulo->saldotitulo;
			}
		});

		$usuario = Auth::user();
		$id_usuario = $usuario->codigo_nasajon;
		if(empty($id_usuario)){
			$id_usuario = User::where('id', 1)->first()->codigo_nasajon;
		}
		$formaPagamentoObj = FormaPagamentoNasajon::where('codigo', '010')->first();
		$contaObj = ContasNasajon::where('codigo', '000')->first();
		$titulos->each(function($titulo) use ($data_nova, $usuario, $id_usuario, $despesas_adicionais, $formaPagamentoObj, $contaObj, $dataHoje, $taxa_juros_original, $data_nova_multa){

			$observacao = "### Titulo {$titulo['numero']} renegociado para o dia {$data_nova->format('d/m/Y')} pelo usuário {$usuario->name} com o valor adicional de ".parserValor($titulo['adicional']);
			if(!empty($despesas_adicionais)){
				$observacao .= " com despesas adicionais bancarias de ".parserValor($despesas_adicionais);
				$titulo['adicional'] += $despesas_adicionais;
			}
			$observacao .= " ### - Sistema automático\n";

			$sql_prorrogar_titulos = "select * from integracoes.gerarrenegociacaotitulo('{$titulo['id']}','{$data_nova->format('Y-m-d')}', 0, '{$observacao}', '{$id_usuario}');";
			
			$sql_titulo = "select * from integracoes.api_titulorecebermovo(
				uuid_generate_v4(), /* id */
				'{$titulo['titulo']['id_estabelecimento']}', /* estabelecimento */
				'{$titulo['titulo']['id_cliente']}', /* cliente */
				'{$titulo['adicional']}', /* valor */
				'{$dataHoje->format('Y-m-d')}', /* emissao */
				'{$data_nova->format('Y-m-d')}', /* vencimento */
				'{$titulo['numero']}.1ND', /* numero */
				'{$formaPagamentoObj->formapagamento}', /* forma pagamento */
				'{$contaObj->conta}', /* conta */
				NULL, /* layout */
				'{$data_nova_multa->format('Y-m-d')}', /* data_multa */
				'{$titulo['titulo']['multa']}', /* percentual multa */
				'{$taxa_juros_original}', /* juros diarios */
				'{$id_usuario}', /* usuario */
				'{$observacao}', /* observacao */
				false
			);";
			$prorrogar_titulos = [];
			try{
				$prorrogar_titulos = DB::connection('nasajon')->select($sql_prorrogar_titulos);
			}catch(\Exception $e){
				Log::error($e->getMessage());
				Log::error($sql_prorrogar_titulos);
			}
			$titulo_novo = [];
			$mensagem_nasajon = [];
			try{
				$titulo_novo = DB::connection('nasajon')->select($sql_titulo);
				$mensagem_nasajon = $titulo_novo[0]->mensagem;
				$mensagem_nasajon = json_decode($mensagem_nasajon, true);
			}catch(\Exception $e){
				Log::error($e->getMessage());
				Log::error($sql_titulo);
			}
			if(!empty($mensagem_nasajon) && $mensagem_nasajon['codigo'] == 'OK'){
				$VendedorTituloNasajonObj = VendedorTituloNasajon::where('tituloreceber', $titulo['id'])->get();
				$VendedorTituloNasajonObj->each(function($vendedor) use($mensagem_nasajon){
					$sql_vendedor = "select * from integracoes.api_tituloreceber_vendedornovo(
						'{$mensagem_nasajon['mensagem']}',
						'{$vendedor->vendedor}',
						100,
						'{$vendedor->percentual_comissao}'
					);";
					try{
						$titulo_novo = DB::connection('nasajon')->select($sql_vendedor);
					}catch(\Exception $e){
						Log::error($e->getMessage());
						Log::error($sql_vendedor);
					}
				});
			}
		});
		$valores['valor_nao_seram_prorrogados'] = parserValor($valores['valor_nao_seram_prorrogados']);
		$valores['valor_seram_prorrogados'] = parserValor($valores['valor_seram_prorrogados']);
		return response()->json([
			'status' => 'success',
			'message' => '',
			'error' => [],
			'response' => $valores
		]);
	}
}
