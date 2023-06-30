@extends('layouts.app')
@section('title', ' | Cadastrar Projeto')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Cadastrar Projeto</div>
                <div class="card-body">
                    @if (count($errors) > 0)
                        <div class="form-group">
                            <div class="col-md-12 col-xs-12 col-sm-12">
                                <div class="alert alert-danger">
                                    <ul>
                                    @foreach ($errors->all() as $error) 
                                        <li>{{ $error }}</li>
                                    @endforeach 
                                    </ul>   
                                </div>
                            </div>    
                        </div>    
                    @endif  
                    {{ Form::open(array('route' => 'projeto.store', 'role'=>'form', 'name' =>'frm_projeto' )) }}
                        @csrf
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" class="form-control" name="nome" id="nome" maxlength="255" value="{{ old('nome') }}" placeholder="Nome " required="required" />
                        </div>
                        <div class="form-group">
                            <label for="codigo_cliente">Códgio de cliente</label>
                            <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" maxlength="255" value="{{ old('codigo_cliente') }}" placeholder="Códgio de cliente" />
                        </div>
	                    <button type="submit" class="btn btn-success float-right">Salvar</button>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
