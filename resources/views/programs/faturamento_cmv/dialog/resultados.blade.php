@extends('layouts.page-dialog')

@section('content')
<form action="#" id="filtro_resultado" name="filtro_resultado" onsubmit="return false">
	@csrf
	{{ Form::hidden('hash', $hash) }}
	<div class="form-row" style="height: 35px;">
        <div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('estabelecimento', '1', false, ['id' => 'estabelecimento', 'class' => 'form-check-input']) }}
			{{ Form::label('estabelecimento', 'Estabelecimento', ['class' => 'form-check-label']) }}
		</div>
        <div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('codigo', '1', false, ['id' => 'codigo', 'class' => 'form-check-input']) }}
			{{ Form::label('codigo', 'Código', ['class' => 'form-check-label']) }}
		</div>
		<div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('grupo', '1', true, ['id' => 'grupo', 'class' => 'form-check-input']) }}
			{{ Form::label('grupo', 'Grupo', ['class' => 'form-check-label']) }}
		</div>
		<div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('subgrupo', '1', true, ['id' => 'subgrupo', 'class' => 'form-check-input']) }}
			{{ Form::label('subgrupo', 'Subgrupo', ['class' => 'form-check-label']) }}
		</div>
		<div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('marca', '1', true, ['id' => 'marca', 'class' => 'form-check-input']) }}
			{{ Form::label('marca', 'Marca', ['class' => 'form-check-label']) }}
		</div>
		<div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('linha', '1', true, ['id' => 'linha', 'class' => 'form-check-input']) }}
			{{ Form::label('linha', 'Linha', ['class' => 'form-check-label']) }}
		</div>
        <div class="form-check ml-1" style="height: 25px;">
			{{ Form::checkbox('segmento', '1', true, ['id' => 'segmento', 'class' => 'form-check-input']) }}
			{{ Form::label('segmento', 'Segmento', ['class' => 'form-check-label']) }}
		</div>
		<div class="form-group ml-1">
			{{ Form::button('Agrupar', ['class' => 'btn btn-success', 'id' => 'filtrar']) }}
		</div>
	</div>
</form>
<div class="content-dialog-table">
    <table class="table table-striped" id="table-filters-dialog">
        <thead>
            <tr>
                <th class="td_estabelecimento">Estabelecimento</th>
                <th class="td_codigo">Código</th>
                <th class="td_grupo">grupo</th>
                <th class="td_subgrupo">subgrupo</th>
                <th class="td_marca">marca</th>
                <th class="td_linha">linha</th>
                <th class="td_segmento">Segmento</th>
                <th class="tb_number">quantidade</th>
                <th class="tb_number">valor de venda</th>
                <th class="tb_number">valor Médio de venda</th>
                <th class="tb_number">{{ $custo }}</th>
                <th class="tb_margem">margem</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($movimentos as $key => $movimento)
            @if($key !== 'devolucao')
                <tr>
                    <td></td>
                    <td></td>
                    <td>{{ $movimento['grupo'] }}</td>
                    <td>{{ $movimento['subgrupo'] }}</td>
                    <td>{{ $movimento['marca'] }}</td>
                    <td>{{ $movimento['linha'] }}</td>
                    <td>{{ $movimento['segmento'] }}</td>
                    <td>{{ $movimento['quantidade'] }}</td>
                    <td>{{ $movimento['resultado'] }}</td>
                    <td>{{ $movimento['valor_medio'] }}</td>
                    <td>{{ $movimento['custo'] }}</td>
                    <td>{{ $movimento['margem'] }}</td>
                </tr>
            @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="7">{{ $movimentos['devolucao']['grupo'] }}</td>
                <td class="td_quantidade_devolucao">{{ $movimentos['devolucao']['quantidade'] }}</td>
                <td class="td_quantidade_resultado">{{ $movimentos['devolucao']['resultado'] }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
    table_dialog = [];
    $(document).ready(function () {
		$(document).find('#filtrar').off('click');
		$(document).find('#filtrar').on('click', function(){
			filtroResultado();
		});
        setTimeout(function(){
            $height = $(document).find(".modal-body").height() - 150;
            $.fn.dataTable.moment('DD/MM/YYYY');
            table_dialog = $(document).find("#table-filters-dialog").DataTable({
                "searching": false,
                "lengthChange": false,
                "info": false,
                "scrollY": $height,
                "scrollCollapse": true,
                "paging": false,
                "dom": 'Bfrtip',
                "autowidth": false,
                "buttons": [
                    {
                        extend: 'excelHtml5',
                        footer: true,
                        customize: function ( xlsx ) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                        },
                        exportOptions: {
                            modifier: {
                                page: 'all'
                            },
                            format: {
                                body: function ( data, row, column, node ) {
                                    data = $('<p>' + data + '</p>').text();
                                    if (column > 4) {
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
						"type": 'num-fmt', 
                    	"render": $.fn.dataTable.render.number( '.', ',', 2 ),
						"targets": "tb_number"
					},
                    {
						"class": "text_number",
						"targets": "tb_margem"
					},
                    { "class": "td_estabelecimento", "targets": "td_estabelecimento" },
                    { "class": "td_codigo", "targets": "td_codigo" },
                    { "class": "td_grupo", "targets": "td_grupo" },
                    { "class": "td_subgrupo", "targets": "td_subgrupo" },
                    { "class": "td_marca", "targets": "td_marca" },
                    { "class": "td_linha", "targets": "td_linha" },
                    { "class": "td_segmento", "targets": "td_segmento" },
                    { "class": "tb_date", "targets": "sort-date" }
                ]
            }).on('draw', function(){
				$(document).find('[data-toggle="tooltip"]').tooltip();
				$(document).find('a.exibir-nota-entrada').on('click', function(){
					showNotasEntradaDetalhesNasajon($(this).data('documento'),$(this).data('estabelecimento'));
				});
				$(document).find('a.exibir-nota').on('click', function(){
					showNotasDetalhesNasajon($(this).data('documento'),$(this).data('estabelecimento'));
				});
				$(document).find('a.exibir-nota-prologos').on('click', function(){
					showNotasDetalhesPrologos($(this).data('estabelecimento'), $(this).data('documento'), $(this).data('data_movimentacao'))
				});
			});
            table_dialog.columns( '.td_estabelecimento' ).visible( false );
            table_dialog.columns( '.td_codigo' ).visible( false );
        }, 250);

    });

    function showNotasEntradaDetalhesNasajon($documento, $estabelecimento){
        $.ajax({
            url: '{{ route('notas_entradas_nasajon.modal.exibir_busca')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                numero: $documento,
                estabelecimento: $estabelecimento,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: "+$documento, body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }

    function showNotasDetalhesNasajon($documento, $estabelecimento){
        $.ajax({
            url: '{{ route('notas_nasajon.modal.exibir_busca')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                numero: $documento,
                estabelecimento: $estabelecimento,
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota: "+$documento, body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            },
            error: function(callback){
                message("Atenção", callback.responseJSON.message);
            }
        });
    }
    function showNotasDetalhesPrologos(estabelecimento, nota_fiscal, data){
        $.ajax({
            url: '{{ route('historico_vendas.dialog')}}',
            type: 'POST',
            data: {
                _token: '{{csrf_token()}}',
                estabelecimento: estabelecimento,
                documento: nota_fiscal,
                link_pedido: true,
                data: data,
                origem: 'PROLOGOS'
            },
            success: function(body){
                createModal("nota_detalhes", "Detalhes da nota", body, 'modal-lg');
                $(document).find(".troca-aba").on("click", function(e){
                    e.preventDefault();
                    $(document).find(".nav-link").not(".active, .dropdown-toggle").tab("show");
                });
            }

        });
    }

	function filtroResultado(){
        $.ajax({
            url: '{{ route('faturamento_vs_cmn.modal.filtro')}}',
            type: 'POST',
            data: $(document).find('#filtro_resultado').serialize(),
            success: function(callback){
				table_dialog.clear().draw();
				dados = callback.response;
                if(dados.length > 0){
                    var fields_filter = [];
                    for(var field in dados){
						if(dados[field].grupo == 'Devolução'){
							$(document).find(".content-dialog-table").find('.td_quantidade_devolucao').html(dados[field].quantidade);
							$(document).find(".content-dialog-table").find('.td_quantidade_resultado').html(dados[field].resultado);
						}else{
							var temp_field = [
                                dados[field].estabelecimento,
                                dados[field].codigo,
								dados[field].grupo,
								dados[field].subgrupo,
								dados[field].marca,
								dados[field].linha,
                                dados[field].segmento,
								dados[field].quantidade,
								dados[field].resultado,
								dados[field].valor_medio,
								dados[field].custo,
								dados[field].margem
							];
							fields_filter.push(temp_field);
						}
                    }
                    table_dialog.rows.add(fields_filter).draw().nodes();
				}
				esconderColunas();
			}
		});
	}
	function esconderColunas(){
        table_dialog.columns( '.td_estabelecimento' ).visible( false );
        table_dialog.columns( '.td_codigo' ).visible( false );
		table_dialog.columns( '.td_grupo' ).visible( false );
		table_dialog.columns( '.td_subgrupo' ).visible( false );
		table_dialog.columns( '.td_marca' ).visible( false );
		table_dialog.columns( '.td_linha' ).visible( false );
        table_dialog.columns( '.td_segmento' ).visible( false );
        if($(document).find('#filtro_resultado').find("#estabelecimento").is(":checked") === true){
			table_dialog.columns( '.td_estabelecimento' ).visible( true );
		}
        if($(document).find('#filtro_resultado').find("#codigo").is(":checked") === true){
			table_dialog.columns( '.td_codigo' ).visible( true );
		}
		if($(document).find('#filtro_resultado').find("#grupo").is(":checked") === true){
			table_dialog.columns( '.td_grupo' ).visible( true );
		}
		if($(document).find('#filtro_resultado').find("#subgrupo").is(":checked") === true){
		table_dialog.columns( '.td_subgrupo' ).visible( true );
		}
		if($(document).find('#filtro_resultado').find("#marca").is(":checked") === true){
		table_dialog.columns( '.td_marca' ).visible( true );
		}
		if($(document).find('#filtro_resultado').find("#linha").is(":checked") === true){
			table_dialog.columns( '.td_linha' ).visible( true );
		}
        if($(document).find('#filtro_resultado').find("#segmento").is(":checked") === true){
			table_dialog.columns( '.td_segmento' ).visible( true );
		}
		table_dialog.draw().nodes();
	}
</script>
@endsection 
