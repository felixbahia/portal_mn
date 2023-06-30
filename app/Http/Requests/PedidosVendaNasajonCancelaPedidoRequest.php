<?php

namespace App\Http\Requests;

use App\Http\Controllers\UserController;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Auth;

use App\User;
use App\PedidosVendaNasajon;

use Illuminate\Foundation\Http\FormRequest;
class PedidosVendaNasajonCancelaPedidoRequest extends FormRequest
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
        $ids = UserController::varreSubordinados(Auth::id());
        $subordinados = User::whereIn('id', $ids)->get()->pluck('codigo_representante')->toArray();

        return [
            'pedido' => [
                function($attribute, $value, $fail) use ($subordinados){
                    $pedidoObj = PedidosVendaNasajon::find($value);
    
                    if(is_null($pedidoObj)){
                        return $fail('Pedido não encontrado!');
                    }
                    
                    if(!in_array($pedidoObj->situacao_descricao, ['Aberto', 'Aguardando Documento','Liquidado'])){
                        return $fail('Pedido não está em aberto!');
                    }
                    
                    if($pedidoObj->situacao_descricao == 'Faturado'){
                        return $fail('Pedido já faturado!');
                    }
                    
                    if($pedidoObj->situacao_descricao == 'Faturado'){
                        return $fail('Pedido já faturado!');
                    }
    
                    if($pedidoObj->situacao_descricao == 'Cancelado'){
                        return $fail('Pedido já cancelado!');
                    }
                    
                    /*if(!in_array($pedidoObj->vendedor_codigo, $subordinados) && !in_array(Auth::user()->tipo_usuario_id, [1, 15])){
                        return $fail('Pedido não pertence a equipe do usuário!');
                    }*/
            
                }
            ]
        ];
    }
    public function messages(){
        return [
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
