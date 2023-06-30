@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    @if(!empty($response['importado']))
        <h5>Importado</h5>
        <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados-importado">
            <thead>
                <tr>
                    <th>{{ $cliente_fornecedor }}</th>
                    <th class="tb_number">Previsão</th>
                    <th class="tb_number">Realizado</th>
                    <th class="tb_number">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($response['importado'] as $dado)
                <tr>
                    <td>
                        @if(!empty($dado["codigo"]))
                            <a href="#" class="tb_number" onclick="mostrarTitulosFornecedorDetalhes('{{ $dado["codigo"] }}', '{{ $data_inicio }}', '{{ $data_fim }}')">
                                <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                            </a>
                        @else
                            <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                        @endif
                    </td>
                    <td>{{ $dado['previsto'] }}</td>
                    <td>{{ $dado['valor'] }}</td>
                    <td>{{ $dado['saldo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number">{{ $total_previso['importado'] }}</td>
                    <td class="tb_number">{{ $total['importado'] }}</td>
                    <td class="tb_number">{{ $total_saldo['importado']}}</td>
                </tr>
            </tfoot>
        </table>
    @endif
    @if(!empty($response['nacional']))
        <h5>Nacional</h5>
        <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados-nacional">
            <thead>
                <tr>
                    <th>{{ $cliente_fornecedor }}</th>
                    <th class="tb_number">Previsão</th>
                    <th class="tb_number">Realizado</th>
                    <th class="tb_number">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($response['nacional'] as $dado)
                <tr>
                    <td>
                        @if(!empty($dado["codigo"]))
                            <a href="#" class="tb_number" onclick="mostrarTitulosFornecedorDetalhes('{{ $dado["codigo"] }}', '{{ $data_inicio }}', '{{ $data_fim }}')">
                                <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                            </a>
                        @else
                            <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                        @endif
                    </td>
                    <td>{{ $dado['previsto'] }}</td>
                    <td>{{ $dado['valor'] }}</td>
                    <td>{{ $dado['saldo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number">{{ $total_previso['nacional'] }}</td>
                    <td class="tb_number">{{ $total['nacional'] }}</td>
                    <td class="tb_number">{{ $total_saldo['nacional']}}</td>
                </tr>
            </tfoot>
        </table>
    @endif
    @if(!empty($response['despesas']))
    <h5>Despesas</h5>
        <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados-despesas">
            <thead>
                <tr>
                    <th>{{ $cliente_fornecedor }}</th>
                    <th class="tb_number">Previsão</th>
                    <th class="tb_number">Realizado</th>
                    <th class="tb_number">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($response['despesas'] as $dado)
                <tr>
                    <td>
                        @if(!empty($dado["codigo"]))
                            <a href="#" class="tb_number" onclick="mostrarTitulosFornecedorDetalhes('{{ $dado["codigo"] }}', '{{ $data_inicio }}', '{{ $data_fim }}')">
                                <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                            </a>
                        @else
                            <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                        @endif
                    </td>
                    <td>{{ $dado['previsto'] }}</td>
                    <td>{{ $dado['valor'] }}</td>
                    <td>{{ $dado['saldo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number">{{ $total_previso['despesas'] }}</td>
                    <td class="tb_number">{{ $total['despesas'] }}</td>
                    <td class="tb_number">{{ $total_saldo['despesas']}}</td>
                </tr>
            </tfoot>
        </table>
    @endif
    @if(!empty($response['banco']))
        <h5>Banco</h5>
        <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados-banco">
            <thead>
                <tr>
                    <th>{{ $cliente_fornecedor }}</th>
                    <th class="tb_number">Previsão</th>
                    <th class="tb_number">Realizado</th>
                    <th class="tb_number">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($response['banco'] as $dado)
                <tr>
                    <td>
                        @if(!empty($dado["codigo"]))
                            <a href="#" class="tb_number" onclick="mostrarTitulosFornecedorDetalhes('{{ $dado["codigo"] }}', '{{ $data_inicio }}', '{{ $data_fim }}')">
                                <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                            </a>
                        @else
                            <div><div data-toggle="tooltip" data-html="true" title="{{ $dado["cliente"] }}">{{ $dado["cliente"] }}</div></div>
                        @endif
                    </td>
                    <td>{{ $dado['previsto'] }}</td>
                    <td>{{ $dado['valor'] }}</td>
                    <td>{{ $dado['saldo'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td class="tb_number">Total:</td>
                    <td class="tb_number">{{ $total_previso['banco'] }}</td>
                    <td class="tb_number">{{ $total['banco'] }}</td>
                    <td class="tb_number">{{ $total_saldo['banco']}}</td>
                </tr>
            </tfoot>
        </table>
    @endif
    <h5>Resumo</h5>
    <table class="table table-striped table-not-edit table-not-view table-dialog-faturamento" id="table-dados-resumo">
        <thead>
            <tr>
                <th></th>
                <th class="tb_number">Previsão</th>
                <th class="tb_number">Realizado</th>
                <th class="tb_number">Saldo</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="Importado">Importado</div></div></td>
                <td>{{ empty($total_previso['importado'])? '' : $total_previso['importado'] }}</td>
                <td>{{ empty($total['importado'])? '' : $total['importado'] }}</td>
                <td>{{ empty($total_saldo['importado'])? '' : $total_saldo['importado'] }}</td>
            </tr>
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="Nacional">Nacional</div></div></td>
                <td>{{ empty($total_previso['nacional'])? '' : $total_previso['nacional'] }}</td>
                <td>{{ empty($total['nacional'])? '' : $total['nacional'] }}</td>
                <td>{{ empty($total_saldo['nacional'])? '' : $total_saldo['nacional'] }}</td>
            </tr>
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="Despesas">Despesas</div></div></td>
                <td>{{ empty($total_previso['despesas'])? '' : $total_previso['despesas'] }}</td>
                <td>{{ empty($total['despesas'])? '' : $total['despesas'] }}</td>
                <td>{{ empty($total_saldo['despesas'])? '' : $total_saldo['despesas'] }}</td>
            </tr>
            <tr>
                <td><div><div data-toggle="tooltip" data-html="true" title="Banco">Banco</div></div></td>
                <td>{{ empty($total_previso['banco'])? '' : $total_previso['banco'] }}</td>
                <td>{{ empty($total['banco'])? '' : $total['banco'] }}</td>
                <td>{{ empty($total_saldo['banco'])? '' : $total_saldo['banco'] }}</td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="tb_number">Total Geral:</td>
                <td class="tb_number">{{$total_geral['previsto']}}</td>
                <td class="tb_number">{{$total_geral['realizado']}}</td>
                <td class="tb_number">{{$total_geral['saldo']}}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-dados-importado').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });

        $('#table-dados-nacional').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });

        $('#table-dados-despesas').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });

        $('#table-dados-banco').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });

        $('#table-dados-resumo').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "pageLength": 20,
            "orderMulti": false,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: ' ',
                    title: '{{ $data }}',
                    exportOptions: {
                        modifier: {
                            page: 'all',
                            search: 'applied',
                            order: 'applied'
                        },
                        format: {
                            body: function (data, row, column, node ) {
                                data = $('<p>' + data + '</p>').text();
                                if(column === 2 ){
                                    if(data != ''){
                                        numero = data.replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            }
                        }
                    },
                },
            ],
            "language": {
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ".",
                "decimal":        ",",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "zeroRecords":    "Nenhum registro encontrado",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                {
                    "class": "tb_number", 
                    "targets": "tb_number"
                },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ]]
        });
    });

    function mostrarTitulosFornecedorDetalhes($fornecedor_codigo, $data_inicio, $data_fim){
        $.ajax({
            url: '{{ route('fluxo_caixa.modal.previsao_fornecedor')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                fornecedor_codigo: $fornecedor_codigo,
                data_inicio: $data_inicio,
                data_fim: $data_fim,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
</script>
@endsection
