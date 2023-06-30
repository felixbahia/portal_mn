@extends('layouts.app')
@section('title', ' | Editar Projeto')
@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Editar Projeto</div>
                <div class="card-body">
                    @if (count($errors) > 0)
                        <div class="form-group">
                            <div class="col-md-6 col-xs-6 col-sm-6">
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
                    {{ Form::open(array('route' => array('projeto.update', $projeto->id), 'role'=>'form', 'name' =>'form_edit_role')) }}  
	                    <input type="hidden" name="id" value="{{ $projeto->id }}">
                        @csrf
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" class="form-control" name="nome" id="nome" maxlength="255" value="{{ $projeto->nome }}" placeholder="Nome " required="required" />
                        </div>
                        <div class="form-group">
                            <label for="codigo_cliente">Códgio de cliente</label>
                            <input type="text" class="form-control" name="codigo_cliente" id="codigo_cliente" maxlength="255" value="{{ $projeto->codigo_cliente }}" placeholder="Códgio de cliente" />
                        </div>
                        <button type="submit" class="btn btn-success float-right">Salvar</button>
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
