@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-dialog-pedido" id="table-filters-dialog">
        <thead>
            <tr>
                <th class="tb_number_150">Num. Projeto</th>
                <th>Projeto</th>
                <th>Cliente</th>
                <th>Linha</th>
                <th class="tb_date_150">Data do Início</th>
                <th class="tb_date_150">Data de Previsão de Entrega</th>
                <th class="tb_date_150">Data Prorrogação</th>
                <th class="tb_number_150">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($linhas as $linha)
             <tr>
                <td class="tb_number_150"><a href="#" data-toggle='tooltip' data-html='true' title='Visualizar' onclick="abrirProjeto('{{ $linha['num_projeto'] }}')">{{ $linha['num_projeto'] }}</a></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $linha['projeto'] }}'>{{ $linha['projeto'] }}</div></div></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $linha['cliente'] }}'>{{ $linha['cliente'] }}</div></div></td>
                <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $linha['linha'] }}'>{{ $linha['linha'] }}</div></div></td>
                <td class="tb_date_150">{{ $linha['data_inicio_projeto'] }}</td>
                <td class="tb_date_150">{{ $linha['data_previsao_entrega'] }}</td>
                <td class="tb_date_150">{{ $linha['prorrogacao'] }}</td>
                <td class="tb_number_150">{{ $linha['valor'] }}</td>
             </tr>   
            @endforeach
        </tbody>
        <tfoot>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
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
