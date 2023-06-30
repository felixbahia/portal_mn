@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_comissao" id="form_comissao" onsubmit="return false;">
    {{ Form::button('Exportação PDF', array('class' => 'btn btn-primary float-right', 'id' => 'btn_exportar')) }} 
</form>

<div class="content-dialog-table">
    <table class="table table-striped table-not-edit table-not-view table-comissao" id="table-dialog2">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Cliente</th>
                <th class="tb_number_html ">Duplicata</th>
                <th class="tb_number_html ">Parcela</th>
                <th class='tb_number_html'>Nota</th>
                <th class="sort-date">Emissão</th>
                <th class="sort-date">Vencimento</th>
                <th class="tb_number_column">Valor da duplicata</th>
                <th class="tb_number_column">Desconto</th>
                <th class="tb_number_column">Valor com desconto</th>
                <th class="tb_number_column">Saldo</th>
                <th class="tb_number_column">Comissão %</th>
                <th class="tb_number_column">Valor Comissão</th>
            </tr>
        </thead>
        <tbody>
            @foreach($titulos['titulos'] as $titulo)
            <tr>
                <td>@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['estabelecimento'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td>
                    <div>
                        <div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $titulo['cliente'] }}'>
                            @if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['cliente'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif
                        </div>
                    </div>
                </td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['duplicata'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['parcela'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">
                    @if(!empty($titulo['informativo_campanha'])) <b> @endif
                    @if(Auth::user()->tipo_usuario_id == 1 || in_array(Auth::id(), [46, 26, 105]) && $titulo['editavel'])
                        @if(!empty($titulo['nota_id']))
                            <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" onclick="revisaoComissao('{{ $titulo['nota_id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"> {{ $titulo['numero_documento'] }}</a> <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Detalhes da comissão" href="#" class='bt-edit-inline' onclick="revisaoComissao('{{ $titulo['nota_id'] }}', '{{ $titulo['id_titulo'] }}', '{{ $titulo['vendedor_codigo'] }}')"></a>
                        @else
                            <a data-toggle="tooltip" data-trigger="hover" data-placement="top" title="Comissão da duplicata" href="#" class='bt-edit-inline' onclick="editarComissao('{{ $titulo['id_titulo'] }}')"></a>
                        @endif
                    @else
                    {{ $titulo['numero_documento'] }}
                    @endif
                    @if(!empty($titulo['informativo_campanha'])) </b> @endif
                </td>
                <td class="tb_date">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['emissao'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_date">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['vencimento'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['valor_total'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['desconto'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['valor_com_desconto'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['saldotitulo'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{!! $titulo['porcentagem'] !!}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
                <td class="tb_number">@if(!empty($titulo['informativo_campanha'])) <b> @endif{{ $titulo['comissao'] }}@if(!empty($titulo['informativo_campanha'])) </b> @endif</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"></td>
                <td>Total:</td>
                <td class="tb_number" id='total_valor'>{{ $titulos['total']['valor_total'] }}</td>
                <td class="tb_number" id='total_desconto'>{{ $titulos['total']['desconto'] }}</td>
                <td class="tb_number" id='total_valor_com_desconto'>{{ $titulos['total']['valor_com_desconto'] }}</td>
                <td class="tb_number" id='total_valor_com_desconto'>{{ $titulos['total']['saldotitulo'] }}</td>
                <td></td>
                <td class="tb_number" id='total_comissao'>{{ $titulos['total']['comissao'] }}</td>
            </tr>
        </tfoot>
    </table>
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
                { "class": "tb_number", type: 'numeric-comma-ftm', targets: "tb_number_column" },
                { "class": "tb_number", type: 'html-num-ftm', targets: "tb_number_html" },
                { "class": "tb_date", targets: "sort-date" }
            ],
            "order": [[ 0, 'asc' ],[ 1, 'asc' ]]
        });

        $(document).find('#btn_duplicatas_vencidas').on('click', function(){
            descontos();
        })
        
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
        $('<form action="{{ route('comissao_duplicatas.modal.abertos_pdf') }}" method="POST" target="_blank">\
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
