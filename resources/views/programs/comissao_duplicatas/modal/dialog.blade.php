@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_comissao" id="form_comissao" onsubmit="return false;">
    {{ Form::button('Exportação PDF', array('class' => 'btn btn-primary float-right', 'id' => 'btn_exportar')) }}
</form>

<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog2">
        <thead>
            <tr>
                <th rowspan="2">Estabelecimento</th>
                <th rowspan="2">Cliente</th>
                <th rowspan="2" class="duplicata">Duplicata</th>
                <th rowspan="2" class="tb_number_html ">Parc.</th>
                <th rowspan="2" class='tb_number_html'>Nota</th>
                <th colspan="4">Datas</th> 
                <th rowspan="2" class="tb_number_column">Valor pago</th>
                <th rowspan="2" class="tb_number_column">Comissão %</th>
                <th rowspan="2" class="tb_number_column">Valor Comissão</th>
            </tr>
            <tr>
                <th class="sort-date">Emissão</th>
                <th class="sort-date">Vecto.</th>
                <th class="sort-date">Pgto.</th>
                <th class="sort-date">Lancto.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos['titulos'] as $titulo)
           
            @if($titulo['campanha'] == true)
                <tr class="table-warning">
            @else
                <tr>
            @endif
                @if(!empty($titulo['informativo_campanha']))
                    <td><b>{{ $titulo['estabelecimento'] }}</b></td>
                    <td><b><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $titulo['cliente'] }}'>{{ $titulo['cliente'] }}</div></div></b></td>
                    <td><b>
                        {{ $titulo['duplicata'] }}
                        @if($titulo['campanha'] == true)
                            <a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Campanha Zera Estoque/Campanha Reative A Moda'" style="color: black;"></a>
                        @endif
                        </b>
                    </td>
                    <td class="tb_number"><b>{{ $titulo['parcela'] }}</b></td>
                    <td class="tb_number">
                        <b>
                            @if(!empty($titulo['id']))
                            <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" onclick="revisaoComissao('{{ $titulo['id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"> {{ $titulo['numero_documento'] }}</a> <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" class='bt-edit-inline' onclick="revisaoComissao('{{ $titulo['id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"></a>
                            @else
                            <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Comissão da duplicata" href="#" class='bt-edit-inline' onclick="editarComissao('{{ $titulo['id_titulo'] }}')"></a>
                            @endif
                        </b>
                    </td>
                    </td>
                    <td class="tb_date"><b>{{ $titulo['emissao'] }}</b></td>
                    <td class="tb_date"><b>{{ $titulo['vencimento'] }}</b></td>
                    <td class="tb_date"><b>{{ $titulo['data_pagamento'] }}</b></td>
                    <td class="tb_date"><b>{{ $titulo['data_lancamento'] }}</b></td>
                    <td class="tb_number"><b>{{ $titulo['valor'] }}</b></td>
                    <td class="tb_number">
                        <b>{!! $titulo['porcentagem'] !!}</b>
                    </td>
                    <td class="tb_number"><b>{{ $titulo['comissao'] }}</b></td>
                @else
                    <td>{{ $titulo['estabelecimento'] }}</td>
                    <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $titulo['cliente'] }}'>{{ $titulo['cliente'] }}</div></div></td>
                    <td>
                        {{ $titulo['duplicata'] }}
                        @if($titulo['campanha'] == true)
                            <a href="#" class="btn-informacao-sem-alinhamento" data-toggle="tooltip" data-placement="top" title="" data-original-title="Campanha Zera Estoque/Campanha Reative A Moda'" style="color: black;"></a>
                        @endif
                    </td>
                    <td class="tb_number">{{ $titulo['parcela'] }}</td>
                    <td class="tb_number">
                        @if(!empty($titulo['id']))
                        <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" onclick="revisaoComissao('{{ $titulo['id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"> {{ $titulo['numero_documento'] }}</a> <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" class='bt-edit-inline' onclick="revisaoComissao('{{ $titulo['id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"></a>
                        @else
                        <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Comissão da duplicata" href="#" class='bt-edit-inline' onclick="editarComissao('{{ $titulo['id_titulo'] }}')"></a>
                        @endif
                    </td>
                    <td class="tb_date">{{ $titulo['emissao'] }}</td>
                    <td class="tb_date">{{ $titulo['vencimento'] }}</td>
                    <td class="tb_date">{{ $titulo['data_pagamento'] }}</td>
                    <td class="tb_date">{{ $titulo['data_lancamento'] }}</td>
                    <td class="tb_number">{{ $titulo['valor'] }}</td>
                    <td class="tb_number">
                        {!! $titulo['porcentagem'] !!}
                    </td>
                    <td class="tb_number">{{ $titulo['comissao'] }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8"></td>
                <td>Total:</td>
                <td class="tb_number" id='total_basecomissao'>{{ $titulos['total']['valor_total'] }}</td>
                <td></td>
                <td class="tb_number" id='total_comissao'>{{ $titulos['total']['comissao'] }}</td>
            </tr>
        </tfoot>
    </table>
    @if(!empty($lancamentos['dados']))
        <label>Lançamentos</label>
        <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog-creddeb">
            <thead>
                <tr>
                    <th class="sort-date">Data</th>
                    <th>Documento</th>
                    <th>Motivo</th>
                    <th class="tb_number">Nota</th>
                    <th>Cliente</th>
                    <th class="tb_number">Credito</th>
                    <th class="tb_number">Debito</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lancamentos['dados'] as $lancamento)
                <tr>
                    <td>{{ $lancamento['data'] }}</td>
                    <td>{{ $lancamento['documento'] }}</td>
                    <td><div><div data-toggle="tooltip" data-trigger="hover" data-placement="top" title="{{ $lancamento['motivo'] }}">{{ $lancamento['motivo'] }}</div></div></td>
                    <td class="tb_number">{{ $lancamento['nota'] }}</td>
                    <td><div><div data-toggle="tooltip" data-trigger="hover" data-placement="top" title="{{ $lancamento['cliente'] }}">{{ $lancamento['cliente'] }}</div></div></td>
                    <td>{{ $lancamento['credito'] }}</td>
                    <td>{{ $lancamento['debito'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4"></td>
                    <td>Total Apurado:</td>
                    <td class="tb_number" id='total_credito'>{{ $lancamentos['total']['credito'] }}</td>
                    <td class="tb_number" id='total_debito'>{{ $lancamentos['total']['debito'] }}</td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td>Total Comissão:</td>
                    <td class="tb_number" id='total_comissao_real'>{{ $lancamentos['total']['comissao'] }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>
<script type="text/javascript">
    $(document).ready( function () {
        $(document).find('#table-dialog2').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number_column" },
                { "class": "tb_number", type: 'html-num', targets: "tb_number_html" },
                { "class": "text-right", targets: "duplicata" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
        
        $(document).find('#table-dialog-creddeb').DataTable({
            "searching": false,
            "lengthChange": false,
            "info": false,
            "scrollX": false,
            "scrollCollapse": true,
            "paging": false,
            "autoWidth": true,
            "language": {
                "decimal":        ".",
                "emptyTable":     "Nenhum registro encontrado",
                "infoPostFix":    "",
                "thousands":      ",",
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
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });
        
        form_modal = $(document).find("#form_comissao");
        form_modal.find("#btn_exportar").off("click");
        form_modal.find("#btn_exportar").on("click", function(){
            var form =  $(document).find("#form_comissao");
            gerarExportacao(form);
        });
    });


    function showNotasDetalhes($id_nota, estabelecimento){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                id_nota: $id_nota,
                estabelecimento: estabelecimento,
                origem: 'NASAJON',
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
            }
        });
    }

    function retornaComissao($origem, $numero_nota, $estabelecimento_data){
        $.ajax({
            url: '{{ route('revisao_comissao.retorna_comissao') }}',
            data: {
                origem: $origem,
                _token: '{{ csrf_token() }}',
                numero_nota: $numero_nota,
                estabelecimento_data: $estabelecimento_data,
                vendedor: '{{ $codigo_representante }}'
            },
            method: 'POST',
            success: function(data){

                $id = 'pedido_modal_comissoes';
                $title = 'Detalhes da comissão';

                createModal($id, $title, data, "");
            
            },
        });
    }

    function revisaoComissao($nota_id, $titulo_id, $vendedor_codigo){
        $.ajax({
            url: '{{ route('comissao_duplicatas.modal.retorna_pesquisa') }}',
            data: {
                _token: '{{ csrf_token() }}',
                nota_id: $nota_id,
                titulo_id : $titulo_id,
                vendedor_codigo: $vendedor_codigo
            },
            method: 'POST',
            success: function(data){
                $id = 'pedido_modal_comissoes';
                $title = 'Detalhes da comissão';
                $class = 'modal-lg';
                createModal($id, $title, data, $class);
            },
        });
    }
    function gerarExportacao(form){
        $('<form action="{{ route('comissao_duplicatas.modal.pdf') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="exportar" value="{{ $exportar }}"/>\
                </form>').appendTo('body').submit().remove()   
    }

    function editarComissao($id){
        $.ajax({
            url: '{{ route('comissao_duplicatas.modal.editar') }}',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id,
            },
            method: 'POST',
            success: function(data){
                $id = 'pedido_modal_comissoes';
                $title = 'Comissão no título';
                createModal($id, $title, data, '');
            },
        });
    }
    
</script>
@endsection
