<?php

namespace App\Http\Controllers;

use Request;
use App\Projeto;
use App\Http\Requests\ProjetoRequest;

class ProjetoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
    
   /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Projeto $projeto)
    {
        $projetos = $projeto->get();
        return view('modules.producao.projeto.index')->with(['projetos' => $projetos]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('modules.producao.projeto.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ProjetoRequest $request)
    {
        Projeto::create($request->all());
        Request::session()->flash('message.level', 'success');
        Request::session()->flash('message.content', 'Projeto Adicionado com Sucesso!');

        return redirect(route('projeto.list'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $projeto = Projeto::find($id);
        if(empty($projeto)) {
            return "Projeto não existe";
        }
        return view('modules.producao.projeto.edit')->with('projeto', $projeto);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $projeto = Projeto::find($id);
        $params = Request::all();
        $projeto->update($params);
        Request::session()->flash('message.level', 'success');
        Request::session()->flash('message.content', 'Projeto Alterado com Sucesso!');
        return redirect(route('projeto.list'));        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $projeto = Projeto::find($id);
        $projeto->delete();
        Request::session()->flash('message.level', 'success');
        Request::session()->flash('message.content', 'Projeto Removido com Sucesso!');
        return redirect(route('projeto.list'));

    }
}
