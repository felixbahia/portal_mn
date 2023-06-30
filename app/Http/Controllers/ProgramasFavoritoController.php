<?php

namespace App\Http\Controllers;

use Auth;
use App\ProgramasFavorito;
use App\Programa;
use App\Modulo;

use Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProgramasFavoritoController extends Controller
{
    public function salvarProgramaFavorito(Request $request){
        $fields = $request->only(['programa']);
        $programas_id = '';
        try{
            $programas_id = decrypt($fields['programa']);
        }catch(\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Erro ao converter programa',
                'error' => [],
                'response' => []
            ], 422);
        }
        $ProgramasFavoritoObj = ProgramasFavorito::query()
            ->where('user_id', Auth::id())
            ->where('programas_id', $programas_id)
            ->first();
        if(!empty($ProgramasFavoritoObj)){
            $ProgramasFavoritoObj->delete();
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => ['ativo'=>false]
            ]);
        }else{
            $ProgramasFavoritoObj = new ProgramasFavorito;
            $ProgramasFavoritoObj->user_id = Auth::id();
            $ProgramasFavoritoObj->programas_id = $programas_id;
            $ProgramasFavoritoObj->save();
            return response()->json([
                'status' => 'success',
                'message' => '',
                'error' => [],
                'response' => ['ativo' => true]
            ]);
        }
    }

    public static function getProgramaAtivo($programa){

        $ProgramasFavoritoObj = ProgramasFavorito::query()
            ->where('user_id', Auth::id())
            ->where('programas_id', $programa)
            ->first();
        if(!empty($ProgramasFavoritoObj)){
            return true;
        }
        else{
            return false;
        }
    }

    public function index(Request $request){
        $ProgramasFavoritoObj = ProgramasFavorito::query()
            ->with('programa')
            ->where('user_id', Auth::id())
            ->get();
        $programas = [];
        $icon = Storage::url('public/icons/modulo/favorito-icon.png');
        $modulo = ['nome' => 'Favoritos', 'rota' => route('favoritos.index')];
        $submodulo = ['nome'=>'', 'rota' => ''];
        foreach($ProgramasFavoritoObj as $programafavorito){
            $programa = $programafavorito->programa;
            if(Permission::where("name","programas ".$programa["model"])->first() === null){
                continue;
            }
            if(Auth::user()->hasPermissionTo("programas ".$programa["model"]) === false){
                continue;
            }
            if(!Route::has($programa["route_index"])){
                continue;
            }
            $icon_temp = $programa["icon"];
            if(empty($icon_temp)){
                $modulo = Modulo::find($programa['modulos_id']);
                $icon_temp = Storage::url($modulo["icon"]);
            }else{
                $icon_temp = Storage::url($programa["icon"]);
            }
            $key = $programa["nome"];
            $key = str_replace(' ', '_', $key);
            $key = strtolower($key);
            $key = tirarAcentos($key);
            $key = strtolower($key);
            $programas[$key] = [
                "route" => route($programa["route_index"]),
                "nome" => $programa["nome"],
                "icon" => $icon_temp,
            ];
        }
        if(Auth::user()->tipo_usuario_id !== 12 && strtolower(Auth::user()->tipo_usuario->nome) !== 'cliente'){
            $programa = Programa::find('11');
            $icon_temp = $programa["icon"];
            if(empty($icon_temp)){
                $icon_temp = $icon;
            }else{
                $icon_temp = Storage::url($programa["icon"]);
            }
            $key = $programa["nome"];
            $key = str_replace(' ', '_', $key);
            $key = strtolower($key);
            $key = tirarAcentos($key);
            $key = strtolower($key);
            $programas[$key] = [
                "route" => route($programa["route_index"]),
                "nome" => $programa["nome"],
                "icon" => $icon_temp,
            ];

            $programa = Programa::find('299');
            $icon_temp = $programa["icon"];
            if(empty($icon_temp)){
                $icon_temp = $icon;
            }else{
                $icon_temp = Storage::url($programa["icon"]);
            }
            $key = $programa["nome"];
            $key = str_replace(' ', '_', $key);
            $key = strtolower($key);
            $key = tirarAcentos($key);
            $key = strtolower($key);
            $programas[$key] = [
                "route" => route($programa["route_index"]),
                "nome" => $programa["nome"],
                "icon" => $icon_temp,
            ];
        }
        ksort($programas);
        return view('sub_modulo', [ 'programas' => $programas, "icon" => $icon, "modulo" => $modulo, "submodulo" => $submodulo ]);
    }

}
