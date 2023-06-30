<?php

namespace App\Http\Requests\Api;

use Auth;

use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\ListagemDePrecosController;

use App\PedidoPortal;
use App\Produto;
use App\AliquotaPreco;
use App\ParametrosPedido;

use App\Http\Requests\ListaDePrecosRequest;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProdutoPedidoModificarRequest extends FormRequest
{
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

        $pedido = $this->route('pedido');
        $quantidade = $this->quantidade;
        $preco_unitario = $this->preco_unitario;

        return [
            'id' => [
                'required',
                Rule::exists('pedido_item')->where(function ($query) {
                    $query->whereNull('deleted_at');
                }),
            ], 
            'quantidade' => [
                'required',
                'numeric'
            ],
            'preco_unitario' => [
                'required',
                'numeric'
            ],
            'codigo_produto' => [
                'required',
                'exists:srv_prologos.TBPRD1,CODPRD',
                function ($campo, $valor, $fail) use ($pedido, $quantidade, $preco_unitario){

                    $pedidoObj = PedidoPortal::find($pedido);
                    $produtoObj = Produto::find($valor);

                    $media_prazo = $pedidoObj->condicao_pagamento_detalhes->media;

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

                    $produtoControllerObj = new ProdutoController();

                    $arr = new Request([
                        'estabel' => $pedidoObj->estabelecimento,
                        'codigo' => $valor,
                        'cod_exato' => true
                    ]);

                    $array = $produtoControllerObj->filterAnalise($arr, false, true);

                    // dd($array);

                    if (isset($array['message'])){

                        $fail($array['message']);
                        return null;
                    }

                    if ($pedidoObj->pedido_futuro == true){
                        $data_explodida = explode("-", $pedidoObj->data_previsao_entrega);
                        $coluna_quinzena = "k_" . $data_explodida[0] . "_" . $data_explodida[1] . "_" . ($data_explodida[2] <= 15? "1": "2");

                        if (str_replace(",", ".", $array['total_coluna'][$coluna_quinzena]) < $quantidade){
                            $fail('Produto sem estoque futuro');
                        }

                    }
                    else{
                        if (str_replace(",", ".", $array['total_coluna']['pronta_entrega']) < $quantidade){
                            $fail('Produto sem estoque');
                        }
                    }

                    // Erro de Preço

                    if((in_array($produtoObj->PROCEDENCIA, [0,3,4,5]) && 
                        (!isset($produtoObj->informacoes_adicionais) || $produtoObj->informacoes_adicionais->exibir_nacional == false))
                        ||
                        (in_array($produtoObj->PROCEDENCIA, [1,2,6,7]) && 
                         isset($produtoObj->informacoes_adicionais) && $produtoObj->informacoes_adicionais->exibir_nacional == true)) {
                        
                        $internacional = 'false';
                    }
                    else if((in_array($produtoObj->PROCEDENCIA, [1,2,6,7]) && 
                        (!isset($produtoObj->informacoes_adicionais) || $produtoObj->informacoes_adicionais->exibir_nacional == false))
                        ||
                        (in_array($produtoObj->PROCEDENCIA, [0,3,4,5]) && 
                         isset($produtoObj->informacoes_adicionais) && $produtoObj->informacoes_adicionais->exibir_nacional == true)){
                        $internacional = 'true';
                    }

                    $aliquotaObj = AliquotaPreco::where('origem', $origem)
                        ->where('estado', $pedidoObj->cliente->ESTADO)
                        ->where('internacional', $internacional)
                        ->first();

                    if ($pedidoObj->tipo_frete == 'A') {
                        $frete = 'cif';
                    }
                    else{
                        $frete = 'fob';
                    }

                    $arr_precos['origem'] = $origem;
                    $arr_precos['produto'] = $valor;
                    $arr_precos['estado'] = $pedidoObj->cliente->ESTADO;
                    $arr_precos['aliquota'] = $aliquotaObj->aliquota;
                    $arr_precos['moeda'] = 'real';
                    $arr_precos['frete'] = $frete;
                    $arr_precos['regiao'] = $pedidoObj->cliente->estado_detalhe->regiao;

                    if (Auth::user()->tipo_usuario_id != 16){
                        $arr_precos['coluna'] = $coluna;
                    }
                    else{
                        $arr_precos['coluna'] = 'coluna_a';
                    }
                    $listaDePrecosObj = new ListagemDePrecosController();
                    $filter_request = new ListaDePrecosRequest($arr_precos);

                    $arr_result = json_decode($listaDePrecosObj->filter($filter_request, true, false)->content(), true);

                    // dd($arr_result);

                    $parametrosPedidoObj = ParametrosPedido::where('estabelecimento', $pedidoObj->estabelecimento)->get()->first();

                    // dd($value);

                    if (Auth::user()->tipo_usuario_id != 16){
                        $valor_minimo = ceil((str_replace(",", ".", $arr_result[0]['coluna_a']) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                        $valor_maximo = ceil((str_replace(",", ".", $arr_result[0]['coluna_c']) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;
                    }
                    else{
                        $valor_minimo = ceil((str_replace(",", ".", $arr_result[0][$coluna]) * (1 - $parametrosPedidoObj->valor_minimo_porcentagem/100) ) * 100)/100;
                        $valor_maximo = ceil((str_replace(",", ".", $arr_result[0][$coluna]) * (1 + $parametrosPedidoObj->valor_maximo_porcentagem/100) ) * 100)/100;            
                    }


                    if (floatval($valor_minimo) > floatval(str_replace(",", ".", $preco_unitario))){
                        $fail('O preço unitário do item está abaixo do mínimo permitido de ' . $valor_minimo);
                    }

                    if (floatval($valor_maximo) < floatval(str_replace(",", ".", $preco_unitario))){
                        $fail('O preço unitário do item está acima do máximo permitido de ' . $valor_maximo);
                    }

                }
            ],
        ];
    }

    public function messages()
    {
        return array(
            'id.required' => __('validation.required', ['attribute' => 'ID']),
            'id.exists' => __('validation.exists', ['attribute' => 'ID']),
            'quantidade.required' => __('validation.required', ['attribute' => 'quantidade']),
            'quantidade.numeric' => __('validation.numeric', ['attribute' => 'quantidade']),
            'preco_unitario.required' => __('validation.required', ['attribute' => 'preço unitário']),
            'preco_unitario.numeric' => __('validation.numeric', ['attribute' => 'preço unitário']),
            'codigo_produto.required' => __('validation.required', ['attribute' => 'código do produto']),
            'codigo_produto.exists' => __('validation.exists', ['attribute' => 'código do produto']),
        );
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        $error = [
            "error" => [
                "error" => true,
                "msg" => [
                    "dev" => reset($errors)[0],
                    "user" => reset($errors)[0]
                ]
            ],
            "request" => $this->camposRequest(),
            "response" => new \stdClass()
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }

    private function camposRequest(){
        $campos = $this->all();
        $campos = $this->parserValueNull($campos);
        return $campos;
    }
    private function parserValueNull($campos){
        foreach ($campos as $key => $value) {
            if(is_array($value)){
                $campos[$key] = $this->parserValueNull($value);
            }else if(empty($value)){
                $campos[$key] = '';
            }
        }
        return $campos;
    }
}
