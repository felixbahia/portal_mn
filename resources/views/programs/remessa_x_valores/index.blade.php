@extends('layouts.app')

@section('content-filter')
<form action="#" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>Listagem de {{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="col-lg-3">
            {{ Form::select('estabelecimento', $estabelecimentos, '',  ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
		</div>
        <div class="col-lg-2">
			{{ Form::text('data', $data_inicial, ['id' => 'data', 'class' => 'form-control data_month', 'maxlength' => '14', 'placeholder' => 'Data MM/AAAA']) }}
        </div>
    </div>
    <div class="content-buttons">
        <button name="btn-filterform" id="btn-filterform" class="btn-filter">Buscar</button>
        <input type="reset" name="btn-clearform" id="btn-clearform" class="btn-clear" value="Limpar busca" />
    </div>
</form>
@endsection
@section('content')
<div class="content-table">
    <table class="table table-striped table-filter-pedido-itens table-not-edit" id="table-filters-remessa-valores">
        <thead>
            <tr>
                <th class="tb_estabelecimento align-middle border-right" rowspan="2">Estabelecimento</th>
                <th class="tb_date align-middle border-right" rowspan="2">Data</th>
                <th class="td_produto_codigo align-middle border-right" rowspan="2">Produto</th>
                <th class="td_produto_descricao align-middle border-right" rowspan="2">Descricao</th>
                <th class="td_quantidade align-middle border-right" colspan="4">quantidade</th>
                <th class="td_valor align-middle" colspan="4">Valor</th>
            </tr>
			<tr>
                <th class="td_quantidade align-middle border-right">saldo inicial<br>01/2021</th>
                <th class="td_quantidade align-middle border-right">remessa</th>
                <th class="td_quantidade align-middle border-right">venda</th>
                <th class="td_quantidade align-middle border-right">saldo</th>

                <th class="td_valor align-middle border-right">remessa</th>
                <th class="td_valor align-middle border-right">venda</th>
                <th class="td_valor align-middle border-right">Nota<br>complementar</th>
                <th class="td_valor align-middle">saldo</th>
			</tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
@endsection
@section('script-footer')
    $(document).ready( function () {
        $(document).find('.data_month').mask('00/0000');
        $(document).find('.data_month').datepicker({
            language: 'pt-BR',
            format: 'mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });
        $(document).find("#btn-filterform").on("click", function(){
            filtrarRemessaXValores($(document).find("#form_filter").serialize());
        });
		$.fn.dataTable.moment('DD/MM/YYYY');
		table_remessa_valores = $(document).find("#table-filters-remessa-valores").DataTable({
			"searching": false,
			"lengthChange": false,
			"info": false,
			"scrollY": "60vh",
			"scrollCollapse": true,
			"paging": false,
			"dom": 'Bfrtip',
			"autowidth": true,
			"buttons": [
				{
					extend: 'excelHtml5',
					footer: true,
					exportOptions: {
						modifier: {
							page: 'all'
						},
						format: {
							body: function ( data, row, column, node ) {
								data = $('<p>' + data + '</p>').text();
								if (column > 3) {
									if(data != ''){
										numero = data.replace(/[.]/g,'').replace(',','');
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
					}
				},
			],
			"language": {
				"decimal":        ",",
				"emptyTable":     "Nenhum registro encontrado",
				"infoPostFix":    "",
				"thousands":      ".",
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
					"type": "num-fmt", 
					"targets": ["td_quantidade", "td_valor"],
					"orderable": false,
					"width": '100px',
					"render": $.fn.dataTable.render.number( '.', ',', 2 )
				},
				{
					"class": "tb_date", 
					"targets": "tb_date",
					"width": '50px',
					"orderable": false
				},
				{
					"targets": ["tb_estabelecimento"],
					"width": '180px',
					"orderable": false
				},
				{
					"targets": ["td_produto_codigo"],
					"width": '150px',
					"orderable": false
				},
				{
					"targets": ["td_produto_descricao"],
					"orderable": false
				}
			],
		}).on('draw', function(){
			$(document).find('[data-toggle="tooltip"]').tooltip();
		});
    });
    function filtrarRemessaXValores(data_form){
        var $return;
        table_remessa_valores.clear().draw();
        $.ajax({
            url: "{{ route('remessa_x_valor.filtro') }}",
            dataType: 'json',
            data: data_form,
            method: 'POST',
            success: function(callback){
				table_remessa_valores.clear().draw();
                var data = callback.response;
                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            data[field].data,
                            createLinkProduto(data[field]),
                            '<div><div data-toggle="tooltip" data-html="true" title="" data-original-title="'+data[field].produto_descricao+'">'+data[field].produto_descricao+'</div></div>',
                            data[field].saldo_inicial,
                            data[field].remessa,
                            data[field].venda,
                            data[field].saldo,
                            data[field].valor_remessa,
                            data[field].valor_venda,
                            data[field].valor_nota_complementar,
                            data[field].valor_saldo,
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_remessa_valores.rows.add(fields_filter).order([ 0, 'asc' ], [ 2, 'asc' ] ).draw().nodes();
                }
            }
        });
    }

	function createLinkProduto(dado){
		$titulo = dado.estabelecimento + ' - ' + dado.data + ' - ' + dado.produto_codigo + ' - ' + dado.produto_descricao;
		$html = '<a href="#" onclick="abrirHistoricoProdutos(\''+dado.filtro+'\', \''+$titulo+'\')">'+dado.produto_codigo+'</a>';
		return $html;
	}

	function abrirHistoricoProdutos($filtro, titulo){
		$.ajax({
			url: "{{ route("remessa_x_valor.modal.historico") }}",
			type: 'POST',
			data: {
				_token: "{{ csrf_token() }}",
				filtro: $filtro
			},
			success: function(body){
				createModal("historico_produtos_remessa", titulo, body, 'modal-lg');
			}
		});
	}
@endsection
