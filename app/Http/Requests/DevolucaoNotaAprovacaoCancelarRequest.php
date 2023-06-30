<?php

namespace App\Http\Requests;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

use Illuminate\Support\Facades\Crypt;

use App\NotaVendaNasajon;
use App\DevolucaoNota;

use Auth;

class DevolucaoNotaAprovacaoCancelarRequest extends FormRequest
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
            'id' => [
                'required',
            ],
            'motivo_reprovacao' => [
                'required',
                function ($attribute, $value, $fail){

                    try {
                        $id = Crypt::decrypt($this->id);
                    } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                        return $fail('Registro inválido!');
                    }

                    $devolucaoNotaObj = DevolucaoNota::with('status_detalhes')->find($id);
            
                    if(empty($devolucaoNotaObj)){
                        return $fail('Registro inválido');
                    }
                }
            ]
        ];
    }


    public function messages()
    {
        return [
            'id.required' => 'Não há um ID válido para este cancelamento, favor atualizar a página',
            'motivo_reprovacao.required' => 'Digite o motivo deste cancelamento'
        ];
    }

    protected function failedValidation(Validator $validator) {
        $errors = (new ValidationException($validator))->errors();
        foreach ($errors as $key => $value) {
            $errors[$key] = implode("<br>", $value);
        }
        $error = [
            'status' => 'error', /// success, error
            'message' => '', /// mensagem
            'error' => $errors,
            'response' => []
        ];
        throw new HttpResponseException(response()->json($error, 403));
    }
}
