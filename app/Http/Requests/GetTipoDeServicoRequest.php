<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\FaccaoTipoDeServico;
use App\ProdutoEspecificacao;
use App\LancamentoProjetoFaccao;

class GetTipoDeServicoRequest extends FormRequest
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
        return [
            'servico_descricao' => [
                'required',
                function($attribute, $value, $fail) {
                    if(!empty($this->servico_codigo)){
                        if(!empty($this->servico_produto)){
                            if(empty($this->servico_codigo_produto_acabado)){
                                $query = LancamentoProjetoFaccao::select();
                                $query->where('lancamento_projeto_produtos_id', $this->servico_produto);
                                $query->where('tipo_servico_id', $this->servico_codigo);
                                $query->whereNull('lancamento_projeto_tecidos_id');
                                $result = $query->first();

                                if(!empty($result)){
                                    return $fail('Este Produto já tem esse Serviço.');
                                }
                            }
                        }
                    }
                }
            ],
            'servico_codigo' => [
                'required'
            ],
            'tipo_de_servico' => [
                'required',
                function($attribute, $value, $fail) {
                    $query = ProdutoEspecificacao::select();
                    $query->where('codigo_produto', 'ILIKE', $value);
                    $query->where('linha', 'MAO DE OBRA');
                    $result = $query->first();
                    if(empty($result)){
                        return $fail(__('validation.exists', ['attribute' => 'Código do Serviço']));
                    }
                }
            ],
            'custo_unitario' => [
                function($attribute, $value, $fail) {
                    if(!empty($this->custo_unitario)){
                        $value = str_replace(",", ".", str_replace(".", "", $this->custo_unitario));
                        $value = floatval($value);
                        if(empty($value)){
                            return $fail('Serviço sem preço.');
                        }
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'faccao.required' => __('validation.required', ['attribute' => 'Facção']),
            'id_produto.required' => __('validation.required', ['attribute' => 'Produto de Referência']),
            'custo_unitario.required' => __('validation.required', ['attribute' => 'Custo Unitário']),
            'tipo_de_servico.required' => __('validation.required', ['attribute' => 'Tipo de Serviço']),
            'servico_produto.required' => __('validation.required', ['attribute' => 'Produto']),
            'servico_descricao.required' => __('validation.required', ['attribute' => 'Nome do Serviço']),
            'servico_codigo.required' => __('validation.required', ['attribute' => 'Código do Serviço']),
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $key = "servico_".$key;
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => 'Campos inválidos', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}