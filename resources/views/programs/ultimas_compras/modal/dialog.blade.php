@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit" id="table-dialog-tecidos">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Data</th>
                <th>NF</th>
                <th>Fornecedor</th>
                <th>Unidade</th>
                <th>QTD</th>
                <th>Valor Unitário</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $value)
            <tr>
                <td>{{ $value['estabelecimento'] }}</td>
                <td class="tb_date">{{ $value['data'] }}</td>
                <td class="tb_number">
                    @if(!empty($value['nota_id']))
                    <a href="#" onclick="showNotaEntradaDetalhes('{{ $value['nota_id'] }}')">{{ $value['nf'] }}</a>
                    @else
                    {{ $value['nf'] }}
                    @endif
                </td>
                <td>{{ $value['fornecedor'] }}</td>
                <td>{{ $value['unidade'] }}</td>
                <td class="tb_number">{{ $value['quantidade'] }}</td>
                <td class="tb_number">{{ $value['valor'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <br>

    <script>
        function showNotaEntradaDetalhes($id){
            var url = '{{ route('notas_entradas_nasajon.nota')}}';
            var title = 'Detalhes da nota';
            var id = $id;
            xhr = $.ajax({
                url: url,
                data: {_token: "{{ csrf_token() }}", id: id},
                method: 'POST',
                success: function(body){
                    if(body.status === 'error'){
                        message('Erro',body.message,'');
                    }else{
                        createModal("modal_nota_entrada", title, body, 'modal-lg');
                    }
                }
            });
        }

    </script>
@endsection
