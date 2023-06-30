@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter_carteira_pedidos" id="form_filter_carteira_pedidos" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        @if ($check_gerentes === true || $check_supervisores === true || $check_vendedor_representante === true)
            @if($check_gerentes === true)
            <div class="form-group col-lg-2">
                {{ Form::select('gerentes', $gerentes, '', ["id" => 'gerentes', 'class' => 'form-control busca_left', 'placeholder' => 'Gerentes'])}}
            </div>
            @endif
            @if($check_vendedor_representante === true)
            <div class="form-group col-lg-2">
                {{ Form::select('vendedor_representante', $vendedor_representante, '', ["id" => 'vendedor_representante', 'class' => 'form-control', 'placeholder' => 'Vendedor Interno / Representantes'])}}
            </div>
            @endif
        @endif
        <div class="form-group col-lg-2">
            {{ Form::select('status_pedido', $status_pedido,'', ["id" => 'status_pedido', 'class' => 'form-control','placeholder' => 'Status do Pedido']) }}
        </div>

        <div class="col-lg-2">
            {{ Form::select('pedido_programado', $tipo_pedido, 'pronta_entrega', ["id" => 'pedido_programado', 'class' => 'form-control','placeholder' => 'Todos']) }}
        </div>

        @if ($check_gerentes === true || $check_supervisores === true || $check_vendedor_representante === true)
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="intercompany" id="intercompany" value="true" />
                    <label class="form-check-label" for="intercompany"><small style="font-size:13px;"> Intercompany </small></label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="deposito_bancario" id="deposito_bancario" value="true" checked="checked">
                    <label class="form-check-label" for="deposito_bancario"> <small style="font-size:13px;">Usar Crédito</small></label>
                </div>
            </div>
            <div class="col-lg-1">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="vendas" id="vendas" value="true" checked="checked">
                    <label class="form-check-label" for="vendas"> <small style="font-size:13px;">Somente Vendas</small></label>
                </div>
            </div>
        @endif
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>	
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped" id="table-filters-carteira">
        <thead>
        	<tr>
                <th rowspan="2" class="align-middle">Empresa</th>
        		<th colspan="4" class="border-right text-center atraso-col">Atraso</th>
        		<th colspan="4" class="border-right text-center mes-col">{{ date("m/Y")}}</th>
        		<th colspan="4" class="border-right text-center mes-futuro-col">{{ date("m/Y", strtotime('+1 month'))}}</th>
        		<th colspan="4" class="text-center mes-col">Futuro</th>
        	</tr>
            <tr>
				<th>Pedidos</th>
				<th>Cli.</th>
                <th>Grupo</th>
				<th>Valor</th>
                <th>Pedidos</th>
				<th>Cli.</th>
                <th>Grupo</th>
				<th>Valor</th>
				<th>Pedidos</th>
				<th>Cli.</th>
                <th>Grupo</th>
				<th>Valor</th>
				<th>Pedidos</th>
				<th>Cli.</th>
                <th>Grupo</th>
				<th>Valor</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr>
                <th class="border-right ">Total:</th>
                <td class="border-right" id="total_pedidos_atraso"></td>
                <td class="border-right" id="total_clientes_atraso"></td>
                <td class="border-right" id="total_produtos_atraso"></td>
                <td class="border-right" id="total_valor_atraso"></td>

                <td class="border-right" id="total_pedidos_mes"></td>
                <td class="border-right" id="total_clientes_mes"></td>
                <td class="border-right" id="total_produtos_mes"></td>
                <td class="border-right" id="total_valor_mes"></td>
                
                <td class="border-right" id="total_pedidos_mes_futuro"></td>
                <td class="border-right" id="total_clientes_mes_futuro"></td>
                <td class="border-right" id="total_produtos_mes_futuro"></td>
                <td class="border-right" id="total_valor_mes_futuro"></td>
                
                <td class="border-right" id="total_pedidos_futuro"></td>
                <td class="border-right" id="total_clientes_futuro"></td>
                <td class="border-right" id="total_produtos_futuro"></td>
                <td class="border-right" id="total_valor_futuro"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endsection
@section('script-footer')
	$(document).ready( function(){
        $("#btn-filterform").on("click", function(){
            buscaDados();
        });
        $(document).find(".busca_left").on('change', function(event){
            var campos = $(document).find("select:visible");
            var indice = campos.index(event.target) + 1;
            var seletor = $(campos[indice]);
            if($(this).val() !== ''){
                checkDadosUser(seletor, $(this).val());
            }
        });

        $(document).find("#btn-clearform").on('click', function(event){
            limpaBusca();
        });
        buscaDados();
	});
    @if ($check_gerentes === true || $check_vendedor_representante === true)
    function checkDadosUser(campo_busca, valor){
        
        primeira_opcao = $(campo_busca).find("option:first").html();
        $(campo_busca).html("");
        var campos = "<option value=\"\">" + primeira_opcao + "</option>";
        $.ajax({
            url: "{{ route('usuario.dados_subordinados') }}",
            dataType: 'json',
            data: {_token:'{{ csrf_token() }}', user: valor},
            method: 'POST',
            success: function(callback){
                if(callback.status === "success"){
                    var response = callback.response;
                    for(var line in response){
                        campos += "<option value=\""+response[line].id+"\">"+response[line].name+"</option>";
                    }
                }
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        }).done(function(){
            $(campo_busca).html(campos).focus();
        });
    }
    @endif
    table_filters = $('#table-filters-carteira').DataTable({
        "searching": false,
        "paging": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 15,
        "orderMulti": false,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: ' ',
                title: '{{ CustomView::programaName() }}',
                footer: true,
                customize: function ( xlsx ) {
                    var sheet = xlsx.xl.worksheets['sheet1.xml'];
                    $('c[r=G7] t', sheet).attr( 's', '0' );
                },
                exportOptions: {
                    modifier: {
                        page: 'all'
                    },
                    format: {
                        body: function ( data, row, column, node ) {
                            return (column === 3 || column === 6) ?
                                $(data).text().replace( /[$.]/g, '' ).replace( /[$,]/g, '.' ) :
                            (column === 0) ?
                                data :
                                $(data).text();
                        }
                    }
                }
            },
        ],
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
            {
                "targets": [1,2,3,4],
                className: 'atraso-col number_format'
            },
            {
                "targets": [5,6,7,8],
                className: 'mes-col number_format'
            },
            {
                "targets": [9,10,11,12],
                className: 'mes-futuro-col number_format'
            },
            {
                "targets": [13,14,15,16],
                className: 'futuro-col number_format'
            }
        ],
        "order": [[ 0, 'asc' ], [ 2, 'asc' ]]
    });

    function detalhe(estabel, mes, coluna, criterio){
        var title = "Pedidos a faturar ";
        $.ajax({
            url: "{{route('pedidos_orcamentos.pedidos')}}",
            data: {_token: '{{ csrf_token() }}', estabel: estabel, mes: mes, coluna: coluna, criterio: criterio},
            method: 'POST',
            success: function(body){
                createModal("show_detalhe", title, body, 'modal-lg');
            }
        });
    }

    function detalhe_produto(estabel, mes, coluna, criterio){
        var title = "Pedidos a faturar ";
        $.ajax({
            url: "{{route('pedidos_orcamentos.pedidos')}}",
            data: {_token: '{{ csrf_token() }}', estabel: estabel, mes: mes, coluna: coluna, criterio: criterio, produto: true},
            method: 'POST',
            success: function(body){
                createModal("show_detalhe", title, body, 'modal-lg');
            }
        });
    }

    function buscaDados(){
        var $data_form = $("#form_filter_carteira_pedidos").serialize();
        table_filters.clear().draw();
        
        /*Pedido Atraso*/
        $("#total_pedidos_atraso").html("");
        $("#total_clientes_atraso").html("");
        $("#total_valor_atraso").html("");
        $("#total_produtos_atraso").html("");

        /*Pedido mês*/
        $("#total_pedidos_mes").html("");
        $("#total_clientes_mes").html("");
        $("#total_valor_mes").html("");
        $("#total_produtos_mes").html("");

        /*Pedidos Mês futuro*/
        $("#total_pedidos_mes_futuro").html("");
        $("#total_clientes_mes_futuro").html("");
        $("#total_valor_mes_futuro").html("");

        /*Pedidos Futuros*/
        $("#total_pedidos_futuro").html("");
        $("#total_clientes_futuro").html("");
        $("#total_valor_futuro").html("");
        $("#total_produtos_futuro").html("");
        
        $.ajax({
            url: "{{ route('carteira_pedidos.filter') }}",
            dataType: 'json',
            data: $data_form,
            method: 'POST',
            success: function(callback){
                if(callback.status !== 'success'){
                    message("Atenção", callback.message);
                    return false;
                }
                var linhas = callback.response.lines;
                if(linhas.length > 0){
                    var fields_filter = [];
                    var total_pedidos_atraso;
                    var total_clientes_atraso;
                    var total_valor_atraso;

                    var total_pedidos_mes;
                    var total_clientes_mes;
                    var total_valor_mes;
                    
                    var total_pedido_mes_futuro;
                    var total_clientes_mes_futuro;
                    var total_valor_mes_futuro;

                    var total_pedido_futuro;
                    var total_clientes_futuro;
                    var total_futuro;
                    
                    for(var field in linhas){
                        var temp_field = [
                            linhas[field].estabelecimento,
                            linhas[field].passado_pedidos,
                            linhas[field].passado_clientes,
                            linhas[field].passado_quantidade_produtos,
                            linhas[field].passado_valor_total,
                            linhas[field].mes_pedidos,
                            linhas[field].mes_clientes,
                            linhas[field].mes_quantidade_produtos,
                            linhas[field].mes_valor_total,
                            linhas[field].mes_futuro_pedidos,
                            linhas[field].mes_futuro_clientes,
                            linhas[field].mes_futuro_quantidade_produtos,
                            linhas[field].mes_futuro_valor_total,
                            linhas[field].futuro_pedidos,
                            linhas[field].futuro_clientes,
                            linhas[field].futuro_quantidade_produtos,
                            linhas[field].futuro_valor_total
                        ];
                        /*Atraso*/
                        total_pedidos_atraso += linhas[field].passado_pedidos;
                        total_clientes_atraso     += linhas[field].passado_clientes;
                        total_valor_atraso   += linhas[field].passado_valor_total;

                        /* Mês */
                        total_pedidos_mes += linhas[field].mes_pedidos;
                        total_clientes_mes     += linhas[field].mes_clientes;
                        total_valor_mes   += linhas[field].mes_valor_total;
                        
                        /* Mês Futuro */
                        total_pedidos_mes_futuro += linhas[field].mes_futuro_pedidos;
                        total_clientes_mes_futuro     += linhas[field].mes_futuro_clientes;
                        total_valor_mes_futuro   += linhas[field].mes_futuro_valor_total;

                        /*+ Futuros*/
                        total_pedidos_futuro    += linhas[field].futuro_pedidos;
                        total_clientes_futuro   += linhas[field].futuro_clientes;
                        total_valor_futuro      += linhas[field].futuro_valor_total;

                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                }
                
                $("#total_pedidos_atraso").html("<a href=\"#\" onclick=\"detalhe(-1,-1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalPedidos.passado_pedidos+"</a>");
                $("#total_clientes_atraso").html("<a href=\"#\" onclick=\"detalhe(-1,-1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalClientes.passado_clientes+"</a>");
                $("#total_valor_atraso").html("<a href=\"#\" onclick=\"detalhe(-1,-1,'total', '"+callback.response.criterios+"')\">"+callback.response.total.passado_valor_total+"</a>");
                $("#total_produtos_atraso").html("<a href=\"#\" onclick=\"detalhe_produto(-1,-1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalProdutos.passado_quantidade_produtos+"</a>");

                $("#total_pedidos_mes").html("<a href=\"#\" onclick=\"detalhe(-1,0,'total', '"+callback.response.criterios+"')\">"+callback.response.totalPedidos.mes_pedidos+"</a>");
                $("#total_clientes_mes").html("<a href=\"#\" onclick=\"detalhe(-1,0,'total', '"+callback.response.criterios+"')\">"+callback.response.totalClientes.mes_clientes+"</a>");
                $("#total_valor_mes").html("<a href=\"#\" onclick=\"detalhe(-1,0,'total', '"+callback.response.criterios+"')\">"+callback.response.total.mes_valor_total+"</a>");
                $("#total_produtos_mes").html("<a href=\"#\" onclick=\"detalhe_produto(-1,0,'total', '"+callback.response.criterios+"')\">"+callback.response.totalProdutos.mes_quantidade_produtos+"</a>");
                
                $("#total_pedidos_mes_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalPedidos.mes_futuro_pedidos+"</a>");
                $("#total_clientes_mes_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalClientes.mes_futuro_clientes+"</a>");
                $("#total_valor_mes_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,1,'total', '"+callback.response.criterios+"')\">"+callback.response.total.mes_futuro_valor_total+"</a>");
                $("#total_produtos_mes_futuro").html("<a href=\"#\" onclick=\"detalhe_produto(-1,1,'total', '"+callback.response.criterios+"')\">"+callback.response.totalProdutos.mes_futuro_quantidade_produtos+"</a>");
                
                $("#total_pedidos_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,2,'total', '"+callback.response.criterios+"')\">"+callback.response.totalPedidos.futuro_pedidos+"</a>");
                $("#total_clientes_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,2,'total', '"+callback.response.criterios+"')\">"+callback.response.totalClientes.futuro_clientes+"</a>");
                $("#total_valor_futuro").html("<a href=\"#\" onclick=\"detalhe(-1,2,'total', '"+callback.response.criterios+"')\">"+callback.response.total.futuro_valor_total+"</a>");
                $("#total_produtos_futuro").html("<a href=\"#\" onclick=\"detalhe_produto(-1,2,'total', '"+callback.response.criterios+"')\">"+callback.response.totalProdutos.futuro_quantidade_produtos+"</a>");
                
            },
            error: function(data){
                hide_loader();
                message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
            }
        });

    }

    function limpaBusca(){
        $.ajax({
            url: '{{ route('entrada_pedido.reseta_busca') }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
        })
        .done(function(data) {
            
            $("#gerentes").html('<option value="">Gerente</option>');
            $("#supervisores").html('<option value="">Supervisor</option>');
            $("#vendedor_representante").html('<option value="">Vendedor Interno / Representantes</option>');

            $.each(data.gerentes, function(i, item) {

                $("#gerentes").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

            $.each(data.supervisores, function(i, item) {

                $("#supervisores").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

            $.each(data.vendedor_representante, function(i, item) {

                $("#vendedor_representante").append("<option value='"+ i +"'>" + item + "</option>")
                
            });

        });
        
    }
@endsection
</script>