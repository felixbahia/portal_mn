@extends('layouts.app')
@section('title', ' | Produção | Projetos')
@section('module-image', URL::asset('/images/icons/producao-icon.png'))
@section('module-name', 'Produção')
@section('module-url', route('producao'))
@section('page-url', route('projeto.list'))
@section('page-name', 'Projetos')

@section('content')
<div class="content-table">
    <table class="table table-striped">
        <thead>
            <tr>
                <th scope="col">Nome</th>
                <th scope="col">Códgio de cliente</th>
                <th scope="col">Editar</th>
                <th scope="col">Excluir</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection

@section('content-filter')
<form action="#" name="frm_filter" id="frm_filter" onsubmit="return false;">
    <h3>Listagem de Projetos</h3>
    <
</form>
@endsection