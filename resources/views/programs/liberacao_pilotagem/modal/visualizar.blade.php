@extends('layouts.page-dialog')

@section('content')
<form action="" name="form_pilotagem_visualizar" id="form_pilotagem_visualizar" onsubmit="return false;">
    @csrf
    <div class="form-row">
        {{ Form::hidden('abonar_pilotagem_alterar_comissao',  $dados['abonar_pilotagem_alterar_comissao'], ["id" => 'abonar_pilotagem_alterar_comissao'])}}
        <div class='col-sm-2'>
			<b>Tipo de Ajuste:</b>
		</div>
		<div class='col-sm-3'>
        @if($dados['abonar_pilotagem_alterar_comissao'] == 'abonar_pilotagem')
            Abonar Pilotagem
        @else
            Alterar Comissão
        @endif
		</div>
    </div>
    <div class="form-row filtro_abonar_pilotagem">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-pilotagem">
                    <thead>
                        <th>Estabelecimento</th>
                        <th>Representante</th>
                        <th>Nota</th>
                        <th class="tb_date">Data Lançamento</th>
                        <th class="tb_number">Valor Desconto</th>
                        <th class="tb_number">Valor Crédito</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dados["estabelecimento"] }}'>{{ $dados["estabelecimento"] }}</div></div></td>
                            <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dados["representante"] }}'>{{ $dados["representante"] }}</div></div></td>
                            <td><a href="#" data-title="DETALHES DA NOTA: {{ $dados['nota'] }}" data-route="{{ route('notas_nasajon.modal.exibir') }}" data-nota_id="{{ $dados['nota_id'] }}" class="modal-nota">{{ $dados['nota'] }}</a></td>
                            <td>{{ $dados['data_lancamento'] }}</td>
                            <td>{{ $dados['valor_desconto'] }}</td>
                            <td>{{ $dados['valor_credito'] }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="form-row filtro_comissao">
        <div class="content-dialog-table">
            <div class="content-table">
                <table class="table table-striped" id="table-filters-comissao">
                    <thead>
                    <tr>
                        <th rowspan="2">Estabelecimento</th>
                        <th rowspan="2">Representante</th>
                        <th rowspan="2">Nota</th>
                        <th rowspan="2"> Pedido Venda</th>
                        <th rowspan="2">Título</th>
                        <th rowspan="2" class="tb_number">Parcela</th>
                        <th colspan="3">Datas</th>
                        <th rowspan="2" class="tb_number">Valor Título</th>
                        <th rowspan="2" class="tb_number">% Comissão Atual</th>
                        <th rowspan="2" class="tb_number">% Comissão Alterada</th>
                    </tr>
                    <tr>
                        <th class="tb_date">Emissão</th>
                        <th class="tb_date">Vecto.</th>
                        <th class="tb_date">Lancto.</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dados["estabelecimento"] }}'>{{ $dados["estabelecimento"] }}</div></div></td>
                            <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $dados["representante"] }}'>{{ $dados["representante"] }}</div></div></td>
                            <td><a href="#" data-title="DETALHES DA NOTA: {{ $dados['nota'] }}" data-route="{{ route('notas_nasajon.modal.exibir') }}" data-nota_id="{{ $dados['nota_id'] }}" class="modal-nota">{{ $dados['nota'] }}</a></td>
                            <td><a href="#" data-title="DADOS DO PEDIDO" data-route="{{ route('pedidos_orcamentos.show') }}" data-pedido_venda_id="{{ $dados['pedido_venda_id'] }}" data-origem="nasajon" class="modal-pedido">{{ $dados['pedido_venda'] }}</a></td>
                            <td>{{ $dados['titulo'] }}</td>
                            <td>{{ $dados['parcela'] }}</td>
                            <td>{{ $dados['data_emissao'] }}</td>
                            <td>{{ $dados['data_vencimento'] }}</td>
                            <td>{{ $dados['data_lancamento'] }}</td>
                            <td>{{ $dados['valor_titulo'] }}</td>
                            <td>{{ $dados['comissao_atual'] }}</td>
                            <td>{{ $dados['comissao_alterada'] }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="row">
        <div class='col-sm-2'>
		    <b>Motivo:</b>
		</div>
		<div class='col-sm-3'>
			{{ $dados['motivo'] }}
		</div>
    </div>
</form>
<script>
    $(document).ready( function () {
        form_modal = $(document).find("#form_pilotagem_visualizar");

            $(document).find(".modal-pedido").off('click');
            $(document).find(".modal-pedido").on('click', function(){
                event.stopPropagation();
                abrirModalPedido($(this));
            });
            $(document).find(".modal-nota").off('click');
            $(document).find(".modal-nota").on('click', function(){
                event.stopPropagation();
                abrirModalNota($(this));
            });

        if(form_modal.find("#abonar_pilotagem_alterar_comissao").val() == 'abonar_pilotagem'){
            form_modal.find(".filtro_abonar_pilotagem").show();
            form_modal.find(".filtro_comissao").hide();
        }else{
            form_modal.find(".filtro_abonar_pilotagem").hide();
            form_modal.find(".filtro_comissao").show();
        }

        initTableVisualizar();
    });

    function initTableVisualizar(){
        table_filters_pilotagem_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhuma Nota Encontrada",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_date", targets: "tb_date"}
            ]
        };
        table_pilotagem = $(document).find('#table-filters-pilotagem').DataTable(table_filters_pilotagem_options);

        table_filters_comissao_options = {
            "searching": false,
            "lengthChange": false,
            "info": false,
            "paging": false,
            "pageLength": 15,
            "processing": true,
            "language": {
                "decimal":        ",",
                "thousands":      ".",
                "emptyTable":     "Nenhum Título Encontrado",
                "infoPostFix":    "",
                "loadingRecords": "Carregando...",
                "processing":     "Processando...",
                "paginate": {
                    "first":      "<<",
                    "last":       ">>",
                    "next":       ">",
                    "previous":   "<"
                }
            },
            "columnDefs": [
                { "class": "tb_number", type: 'num-fmt', targets: "tb_number"},
                { "class": "tb_date", targets: "tb_date"}
            ]
        };
        table_comissao = $(document).find('#table-filters-comissao').DataTable(table_filters_comissao_options);
    }

    function abrirModalNota($this){
        var url = $($this).data("route");
        var nota_id = $($this).data("nota_id");
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {
                _token: "{{ csrf_token() }}", id_nota: nota_id},
            method: 'POST',
            success: function(body){
                createModal("modal_nota_detalhes", title, body, 'modal-lg');
            }
        });
    }

    function abrirModalPedido($this){
        var url = $($this).data("route");
        var pedido_id = $($this).data("pedido_venda_id");
        var origem = $($this).data('origem');
        var title = $($this).data('title');
        xhr = $.ajax({
            url: url,
            data: {
                _token: "{{ csrf_token() }}", pedido: pedido_id, origem: origem},
            method: 'POST',
            success: function(body){
                createModal("modal_pedido_detalhes", title, body, 'modal-lg');
            }
        });
    }
</script>
@endsection