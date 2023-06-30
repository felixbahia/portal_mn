<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

use App\PedidoPortal;
use App\ClienteNasajon;
use Auth;
use Carbon\Carbon;

class PedidoPortalDuplicarRequest extends FormRequest
{
	private $estabelecimento_prologos = [];
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
            'codigo_cliente_duplicar' => [
                'required',
                function($attribute, $value, $fail) {

                    $codigo_cliente_balcao = ['0000010069999'];

                    $cliente = ClienteNasajon::where('codigo', $value)->first();

                    if(is_null($cliente)){
                        return $fail("Este cliente não tem cadastro no Nasajon, favor verificar");
                    }
                    else{
                        if(empty(Auth::user()->codigo_representante) && (empty(trim($cliente->vendedor_codigo)) || is_null($cliente->vendedor_codigo))){
                            return $fail("Ajustar codigo do vendedor no cliente");
                        }
                        if (empty($cliente->uf)){
                            return $fail("O cadastro deste cliente está incompleto e não possui estado. Favor verificar.");
                        }
                    }
                    if(in_array($value, $codigo_cliente_balcao) && Auth::user()->tipo_usuario_id == 12){
                        return $fail("Representante não pode fazer venda de balcão.");
                    }
                }
            ],
            'previsao_entrega' => [
                function($attribute, $value, $fail) {
                    $hoje = Carbon::now();
                    $PedidoPortalBuscaObj = PedidoPortal::find($this->pedido);

                    if(!empty($PedidoPortalBuscaObj)){
                        if($PedidoPortalBuscaObj->pedido_futuro == true && empty($value)){
                            return $fail(__('validation.required', ['attribute' => 'Previsão de Entrega']));
                        }
                    }

                    $data = Carbon::createFromFormat('d/m/Y',$value);

                    if($data->lt($hoje)){
                        return $fail(__('validation.gt.numeric', ['attribute' => 'Previsão de Entrega','value' => 'Data de Hoje']));
                    }
                }
            ],
        ];
    }

    public function messages(){
        return [
            'codigo_cliente_duplicar.required' => __('validation.required', ['attribute' => 'cliente']),
            'codigo_cliente_duplicar.exists' => __('validation.exists', ['attribute' => 'cliente']),
        ];
    }
    
    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error',
            'message' => '',
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 422));
    }
}
