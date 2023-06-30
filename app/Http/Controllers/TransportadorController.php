<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Transportador;
use App\TransportadorNasajon;
use Auth;

class TransportadorController extends Controller
{
	
	public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\Transportador") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\Transportador');
		return view('programs.transportador.index');
	}

	public function indexDialog(Request $request){
        $fields = $request->only(['estabelecimento']);
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
		return view('programs.transportador.modal.dialog')->with(['estabelecimento' => $estabelecimento]);
	}

	public function filter(Request $request){
		$filtro = $request->only(['codtran', 'viatran', 'nome', 'cidade', 'estabelecimento']);
		$estabelecimento = isset($filtro['estabelecimento']) ? intval($filtro['estabelecimento']) : 0;
		unset($filtro['estabelecimento']);
		$query = TransportadorNasajon::select('codigo', 'nome', 'cnpj', 'via_transporte', 'cidade', 'estado')
		->where('bloqueado', false);

		if (!empty($filtro['codtran'])){
			$query->whereRaw("LOWER(codigo) LIKE '%".strtolower(trim($filtro['codtran']))."%'");
		}
		if (!empty($filtro['viatran'])){
			$query->whereRaw("LOWER(via_transporte) LIKE '%".strtolower(trim($filtro['viatran']))."%'");
		}
		if (!empty($filtro['nome'])){
			$query->whereRaw("LOWER(nome) LIKE '%".strtolower(trim($filtro['nome']))."%'");
		}
		if (!empty($filtro['cidade'])){
			$query->whereRaw("LOWER(cidade) LIKE '%".strtolower(trim($filtro['cidade']))."%'");
		}
		
		$result = $query->get()->toArray();
		$return = [];
		foreach ($result as $key => $transportador) {
			$return[] = [
				'codtran' => $transportador['codigo'],
				'nome' => trim($transportador['nome']),
				'cgc' => $transportador['cnpj'],
				'viatran' => $transportador['via_transporte'],
				'cidade' => $transportador['cidade'],
				'estado' => $transportador['estado']
			];
		}

		return response()->json($return);
	}

	public function autocomplete(Request $request){
        $fields = $request->only(["term", "estabelecimento"]);
	
        $return = [];
        $estabelecimento = isset($fields['estabelecimento']) ? intval($fields['estabelecimento']) : 0;
        
		$query = TransportadorNasajon::select('codigo','nome','cnpj')
			->limit("15")
			->orderBy('nome', "ASC")
			->whereRaw("CONCAT(LOWER(TRIM(nome)), ' - ', cnpj) ilike '%".trim($fields["term"])."%'")
			->where('bloqueado', false)
			->distinct('nome')
			->get()
			->toArray();

		foreach ($query as $value){
			$value = (array) $value;
			$return[] = [
				'label' => str_replace(' - __.___.___/____-__', '', trim($value['nome']) . ' - ' . trim($value['cnpj'])),
				'value' => $value['codigo'], 
				'codigo' =>  trim($value['cnpj']),
			];
		}

        return response()->json($return);

	}

	public function codigoParaNome(Request $request){
		$codigo = $request->input("codigo");
		$return = ["status"=>"success", "data"=>[]];
		$busca = TransportadorNasajon::where('codigo', $codigo)
		->where('bloqueado', false)
		->first();

		if(is_null($busca)){
			$return["status"] = "error";
			return response()->json($return);
		}

		$return["data"] = trim($busca['nome']) . (!empty(trim($busca['cnpj']))?' - ' . trim($busca['cnpj']):'');

		return response()->json($return);
	}
	
	public function nomeParaCodigo(Request $request){
		$nome = $request['transportadora_nome'];
		$return = ["status"=>"success", "data"=>[]];
		$busca = TransportadorNasajon::whereRaw("trim(nome + ' - ' + cnpj) = trim('" .  $nome . "')")->get()->first();

		if(is_null($busca)){
			$return["status"] = "error";
			return response()->json($return, 422);
		}

		$return["codigo"] = $busca->codigo;

		return response()->json($return, 200);
	}

    private function parserViaTran($via_transporte){
        switch (intval($via_transporte)) {
            case 0:
                return "Nosso Carro";
            break;
            case 1:
                return "Rodoviário";
            break;
            case 2:
                return "Ferroviário";
            break;
            case 3:
                return "Aéreo";
            break;
            case 4:
                return "Fluvial";
            break;
            case 5:
                return "Maritímo";
            break;
            case 6:
                return "Retirada";
            break;
        }
	}
	
	public function view(Request $request){
		$field = $request->only(['codigo']);
		$transportador = TransportadorNasajon::where('codigo', $field['codigo'])->first();
		$dados = [];
		$dados['codigo'] = $transportador->codigo;
		$dados['cnpj'] = $transportador->cnpj;
		$dados['inscricao_estadual'] = isset($transportador->inscricaoestadual) ? $transportador->inscricaoestadual : '';
		$dados['nome'] = $transportador->nome;
		$dados['nome_fantasia'] = isset($transportador->nomefantasia) ? $transportador->nomefantasia : '';
		$dados['telefone'] = isset($transportador->telefone) ? $transportador->telefone : '';
		$dados['fax'] = isset($transportador->fax) ? $transportador->fax : '';
		$dados['email'] = isset($transportador->email) ? $transportador->email : '';
		$dados['cep'] = isset($transportador->cep) ? $transportador->cep : '';
		$dados['endereco'] = isset($transportador->endereco) ? $transportador->endereco . ', ' . $transportador->numero : '';
		$dados['bairro'] = isset($transportador->bairro) ? $transportador->bairro : '';
		$dados['cidade'] = isset($transportador->cidade) ? $transportador->cidade : '';
		$dados['estado'] = isset($transportador->estado) ? $transportador->estado : '';
		$dados['via_transporte'] = isset($transportador->via_transporte) ? $transportador->via_transporte : '';
		$dados['coleta'] = isset($transportador->coleta) ? (($transportador->coleta === 'S') ? 'Sim' : 'Não') : '';
		return view('programs.transportador.modal.view')->with(['dados'=>$dados]);
	}

	public function dadosTransportador(Request $request){
		
		$fields = $request->only('codigo');
		
		$transportadorObj = TransportadorNasajon::where('codigo', $fields['codigo'])
			->where('bloqueado', false)
			->first();

		return response()->json(
			[
				'status' => 'success',
				'message' => 'Dados recuperados com sucesso!',
				'error' => [],
				'response' => [
					'nome' => $transportadorObj->nome,
					'cnpj' => $transportadorObj->nomecnpj,
					'cidade' => $transportadorObj->cidade,
					'estado' => $transportadorObj->estado,
					'email' => $transportadorObj->email
				]
			], 220
		);

	}
}
