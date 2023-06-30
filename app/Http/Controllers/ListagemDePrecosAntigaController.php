<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;


class ListagemDePrecosAntigaController extends Controller
{
	public function index(Request $request){
        if(Auth::user()->hasPermissionTo("programas App\ListaDePrecoAntiga") === false){
            return abort(403);
        }
        $request->session()->flash('model', 'App\ListaDePrecoAntiga');
        return view('programs.listagem_precos_antiga.index')->with(["url" => Storage::url('lista_preco')]);
	}
}
