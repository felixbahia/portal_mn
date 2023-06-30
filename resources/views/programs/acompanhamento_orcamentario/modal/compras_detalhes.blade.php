@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view " id="table-filters-produtos">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th class="tb_number" rowspan="2">Pedido</th>
               @if($tipo != 'planejada')
                <th class="tb_number" rowspan="2">Nota</th>
                @endif
                <th  rowspan="2">Fornecedor</th>
                <th rowspan="2">Data Compra</th>
                @if($tipo == 'planejada')
                <th rowspan="2">Previsão Entrega</th>
                @else
                <th rowspan="2">Data Entrega</th>
                @endif
                <th colspan="3" class="tb_number porcetagem align-middle">Quantidade</th>
                <th rowspan="2">Valor</th>
            </tr>
            <tr>
                <th>Comprada</th>
                <th>Recebido</th>
                <th>Saldo</th>
            </tr>
            
        </thead>
        <tbody>
            @foreach ($linhas as $linha)
            <tr>
                <td>{{$linha['estabelecimento']}}</td>
                <td>
                    <a href='#' class="tb_number" onclick="mostrarPedidoComprasDetalhes('{{$linha['id_pedido']}}')">{{$linha['numero_pedido']}}</a>
                    @if($linha['importado'] == true)
                      <a href="#" class="tb_number bt-view acompanhamento_bt_style" onclick="abrirProjeto('{{$linha['id_importado']}}')"></a>
                    @endif
                </td>
                @if($tipo != 'planejada')
                <td class="tb_number"><a href='#' onclick="mostrarNotaEntradaDetalhes('{{$linha['id_nota']}}')">{{$linha['nota_codigo']}}</a></td>
                @endif
                <td><div><div data-toggle="tooltip" data-html="true" title="{{ $linha["fornecedor"] }}">{{ $linha["fornecedor"] }}</div></div></td>
                <td class="tb_date">{{$linha['data_compra']}}</td>
                <td class="tb_date">{{$linha['data_entrega']}}</td>
                <td class="tb_number">{{$linha['quantidade_comprada']}}</td>
                <td class="tb_number">{{$linha['quantidade_recebido']}}</td>
                <td class="tb_number">{{$linha['quantidade_restante']}}</td>
                <td class="tb_number">{{$linha['valor']}}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                @if($tipo != 'planejada')
                <td></td>
                @endif
                <td></td>
                <td class="tb_number">Total :</td>
                <td class="tb_number">{{$total_quantidade['comprada']}}</td>
                <td class="tb_number">{{$total_quantidade['recebido']}}</td>
                <td class="tb_number">{{$total_quantidade['restante']}}</td>
                <td class="tb_number">{{$total}}</td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $('#table-filters-produtos').DataTable({
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
                    title: '',
                    footer: true,
                    exportOptions: {
                        columns: ':visible',
                        format: {
                            body: function(data, row, column, node) {
                                data = $('<p>' + data + '</p>').text();
                                
                                if(column >= 5 && column != 10 ){
                                    if(data != ''){
                                        
                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                                }
                                return data;
                            },    
                            footer: function(data, column) {

                             
                                if(column == 5 || column == 7 || column == 8 || column == 9 || column ==11 || column == 12){

                                    if(data != ''){

                                        numero = data.replace('.','').replace('.','').replace('.','').replace(',','');
                                        inteiro = Math.floor(numero.length - 2);
                                        decimal = Math.floor(numero.length);
                                        data = numero.substr(0,inteiro) + '.' + numero.substr(inteiro,decimal);
                                    }else{
                                        data = '';
                                    }
                              }
                              data = $('<p>' + data + '</p>').text();
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

    function mostrarPedidoComprasDetalhes(id){
        $.ajax({
            url: '{{ route('pedidos_compras.pedidos_abertos.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: id,
            },
            success: function(body){
                createModal("modal_compras_detalhes", "Detalhe do Pedido Compras", body, 'modal-lg');
                var modal = $("#modal_compras_detalhes");
            },
            error: function(callback){
                if((callback.responseJSON)){
                    var data = callback.responseJSON.error;
                    message = '';
                    $.each(data, function(index, el) {
                        message += el+'<br />';
                    });
                    message("Atenção", message);
                }
            }
    
        });
    }

    function mostrarNotaEntradaDetalhes($id){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.nota')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id: $id,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }

        });
    }
    function abrirProjeto($value){

        var $id =$value;
       
      
        var title = 'Detalhes do Importação ';
            $.ajax({
            url: '{{ route('importacao.modal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id 
            },
            success: function (data){
                createModal('detalhes_projeto', title, data, 'modal-lg');
            }
        });
    }
</script>
@endsection