@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-necessidade_compras">
        <thead>
            <tr>
                <th>Núm. Projeto</th>
                <th>Projeto</th>
                <th>Cliente</th>
                @if($tipo === "servico")
                <th>Código do Serviço</th>
                <th>Serviço</th>
                @else
                <th>Código do Produto</th>
                <th>Produto</th>
                @endif
                <th>Saldo do Pedido</th>
                <th>Necessidade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($arr_materia_prima as $materia_prima)
            <tr>
                <td class="tb_number" style="width: 150px;"><a href="#" data-toggle='tooltip' data-html='true' title='Visualizar' onclick="abrirProjeto('{{ $materia_prima['num_projeto'] }}')">{{ $materia_prima['num_projeto'] }}</a></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $materia_prima['nome_projeto'] }}'>{{ $materia_prima['nome_projeto'] }}</div></div></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $materia_prima['cliente'] }}'>{{ $materia_prima['cliente'] }}</div></div></td>
                <td style="width: 200px;">{{ $materia_prima['codigo_produto'] }}</td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $materia_prima['produto'] }}'>{{ $materia_prima['produto'] }}</div></div></td>
                <td class="tb_number" style="width: 150px;">{{ $materia_prima['saldo_pedido'] }}</td>
                <td class="tb_number" style="width: 150px;">{{ $materia_prima['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number" style="width: 150px;"></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="tb_number" style="width: 150px;">Total:</td>
                <td class="tb_number" style="width: 150px;">{{ $total }}</td>
            </tr>
        </tfoot>
    </table>
    <br>
    <script>
        function abrirProjeto($id){
            $.ajax({
                url: '{{ route('lancamento_projeto.modal.view') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id_projeto: $id 
                },
                success: function (data){
                    createModal('detalhes', 'Detalhes do Projeto', data, 'modal-lg');
                }
            });
        } 
    </script>
@endsection
