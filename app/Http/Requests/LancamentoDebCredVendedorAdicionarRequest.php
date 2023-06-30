<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use App\PedidoVenda;
use App\LancamentoDebCredVendedor;
use App\ComissaoDataFechamento;

use Carbon\Carbon;

use Auth;

class LancamentoDebCredVendedorAdicionarRequest extends FormRequest
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
            'documento' =>[
                'required',
                'max:10',
                function($attribute, $value, $fail){
                    if(!empty($this->documento) && !empty($this->tipo) && !empty($this->data) && !empty($this->motivo) && !empty($this->vendedor)){
                        $query = LancamentoDebCredVendedor::select('num_documento')
                            ->where('num_documento', $this->documento)
                            ->where('tipo', $this->tipo)
                            ->where('data_lancamento',$this->data)
                            ->where('codigo_motivo', $this->motivo)
                            ->where('codigo_vendedor', $this->vendedor);
                            $result = $query->first();

                            if(!empty($result)){
                                return $fail('');
                            }
                    }
                }
            ], 
            'data' =>[
                'required',
                'max:20',
                'date_format:d/m/Y',
                function($attribute, $value, $fail){
                    if(!empty($this->documento) && !empty($this->tipo) && !empty($this->data) && !empty($this->motivo) && !empty($this->vendedor)){
                        $query = LancamentoDebCredVendedor::select('num_documento')
                            ->where('num_documento', $this->documento)
                            ->where('tipo', $this->tipo)
                            ->where('data_lancamento',$this->data)
                            ->where('codigo_motivo', $this->motivo)
                            ->where('codigo_vendedor', $this->vendedor);
                            $result = $query->first();

                            if(!empty($result)){
                                return $fail('');
                            }
                    }

                    $data = Carbon::createFromFormat('d/m/Y', $value);
    
                    $comissaoDataFechamentoObj = ComissaoDataFechamento::where('data_fim', '<', date('Y-m-d'))->orderBy('data_fim', 'desc')->first();

                    if($comissaoDataFechamentoObj->data_fim->gte($data) && !(Auth::user()->tipo_usuario_id == 1 || Auth::user()->hasRole('ADM - Comissoes'))){
                        return $fail('Não se pode fazer lançamentos para comissões já fechadas.');
                    }
                }
            ], 
            'vendedor' =>[
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->documento) && !empty($this->tipo) && !empty($this->data) && !empty($this->motivo) && !empty($this->vendedor)){
                        $query = LancamentoDebCredVendedor::select('num_documento')
                            ->where('num_documento', $this->documento)
                            ->where('tipo', $this->tipo)
                            ->where('data_lancamento',$this->data)
                            ->where('codigo_motivo', $this->motivo)
                            ->where('codigo_vendedor', $this->vendedor);
                            $result = $query->first();
                            if(!empty($result)){
                                return $fail('Já foi cadastrada este lançamento para esse Vendedor');
                            }
                        
                    }
                }
            ], 
            'motivo' =>[
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->documento) && !empty($this->tipo) && !empty($this->data) && !empty($this->motivo) && !empty($this->vendedor)){
                        $query = LancamentoDebCredVendedor::select('num_documento')
                            ->where('num_documento', $this->documento)
                            ->where('tipo', $this->tipo)
                            ->where('data_lancamento',$this->data)
                            ->where('codigo_motivo', $this->motivo)
                            ->where('codigo_vendedor', $this->vendedor);
                            $result = $query->first();
                            if(!empty($result)){
                                return $fail('');
                            }
                    }
                }
            ], 
            'tipo' =>[
                'required',
                function($attribute, $value, $fail){
                    if(!empty($this->documento) && !empty($this->tipo) && !empty($this->data) && !empty($this->motivo) && !empty($this->vendedor)){
                        $query = LancamentoDebCredVendedor::select('num_documento')
                            ->where('num_documento', $this->documento)
                            ->where('tipo', $this->tipo)
                            ->where('data_lancamento',$this->data)
                            ->where('codigo_motivo', $this->motivo)
                            ->where('codigo_vendedor', $this->vendedor);
                            $result = $query->first();
                            if(!empty($result)){
                                return $fail('');
                            }
                    }
                }
            ], 
            'valor' =>[
                'required',
                'max:8'
            ]
        ];
    }

    public function messages()
    {
        return [
            'documento.required' => __('validation.required', ['attribute' => 'Documento']),
            'documento.max' => __('validation.max', ['attribute', 'Documento']),

            'data.required' => __('validation.required', ['attribute' => 'Data de Lançamento']),
            'data.max' => __('validation.max', ['attribute' => 'Data de Lançamento']),
            'data.date_format' => __('validation.date_format', ['attribute' => 'Data de Lançamento', 'format' => 'DD/MM/AAAA']),

            'vendedor.required' => __('validation.required', ['attribute' => 'Vendedor']),

            'motivo.required' => __('validation.required', ['attribute' => 'Motivo']),

            'tipo.required' => __('validation.required', ['attribute' => 'Lançamento']),

            'valor.required' => __('validation.required', ['attribute' => 'Valor']),
            'valor.max' => __('validation.max', ['attribute' => 'Valor']),

            'vendedor.unique' => '',
            'documento.unique' => 'Já foram cadastradas informações adicionais para este Documento',
            'data.unique' => '',
            'motivo.unique' => '',
            'tipo.unique' => '',
            
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
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
