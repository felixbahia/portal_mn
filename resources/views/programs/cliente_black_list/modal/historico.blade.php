@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-tecidos">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Título</th>
                <th>Motivo</th>
                <th class="tb_date">Data</th>
                <th class="tb_date">Hora</th>
                <th>Responsável</th>
                <th>Baixado Por</th>
                <th>Observação</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados as $dado)
            <tr>
                <td>{{ $dado['cliente'] }}</td>
                <td>{{ $dado['titulo'] }}</td>
                <td>{{ $dado['motivo'] }}</td>
                <td class="tb_date">{{ $dado['data'] }}</td>
                <td class="tb_date">{{ $dado['hora'] }}</td>
                <td>{{ $dado['usuario'] }}</td>
                <td>{{ $dado['baixa_por'] }}</td>
                <td>{{ $dado['observacao'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <br>
@endsection
