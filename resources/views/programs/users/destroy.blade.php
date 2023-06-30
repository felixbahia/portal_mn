@extends('layouts.page-dialog')

@section('content')
    <div class=row>
        <div class='col-lg-12'>
            <h5>Deseja realmente excluir este registro?</h5>
        </div>
    </div>
    <div class="row">
        <table class="table">
            <thead class="thead-light">
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Setor</th>
                    <th>Tipo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{$usuario['name']}}</td>
                    <td>{{$usuario['email']}}</td>
                    <td>{{$usuario['setor']}}</td>
                    <td>{{$usuario['tipo']['nome']}}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <form action="{{ route("usuario.destroy") }}" method="POST" onsubmit="return false;" }">
                @csrf
                
                {{ Form::hidden('id', $usuario['id']) }}
                
                <input type="submit" class="btn btn-danger float-right" value="Excluir">
            </form>
        </div>
    </div>
@endsection