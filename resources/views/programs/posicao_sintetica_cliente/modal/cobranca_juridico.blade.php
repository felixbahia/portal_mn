@extends('layouts.page-dialog')

@section('content')
<form action="#" name="form_titulo_para_renegociacao_filter_titulos_para_renegociacao" id="form_titulo_para_renegociacao_filter_titulos_para_renegociacao" onsubmit="return false;">
    @csrf
    <div class="content-filter-dialog">	
        <div class="content-fields">
            <div class="col-lg-2">
                {!! Form::text('titulo_cobranca_ragazzi', '', ['id' => 'titulo_cobranca_ragazzi', 'placeholder' => 'Título', 'class' => 'form-control']) !!}
            </div>
        </div>
        <div class="content-buttons">
            <button name="btn-filterform_titulo_para_renegociacao-baixa-titulos" id="btn-filterform_titulo_para_renegociacao-baixa-titulos" class="btn btn-primary">Buscar</button>
        </div>
    </div>
    <div class="content-dialog-table">
        <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-cobranca-ragazzi">
            <thead>
                <tr>
                    <th class="tb_selecionar">Selec.<input id="selecione_todos" name="selecione_todos" type="checkbox" autocomplete="off"></th>
                    <th>Estab.</th>
                    <th>Título</th>
                    <th class="tb_number">Pa.</th>
                    <th>Banco</th>
                    <th class="tb_date">Data de Emissão</th>
                    <th class="tb_date">Data de Vencimento</th>
                    <th class="tb_number">Valor Original</th>
                    <th class="tb_number">Juros</th>
                    <th class="tb_number">Valor (Saldo)</th>
                    <th class="tb_number">Juros Diários</th>
                    <th class="tb_date">Início Juros</th>
                    <th class="tb_number">Desconto</th>
                    <th class='icone'>Nota</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dados as $value)
                <tr>
                    <td>{!! $value["link"] !!}</td>
                    <td>
                        <div><div data-toggle="tooltip" data-html="true" title="{{ $value["estabelecimento_nome"] }}">{{ $value["estabelecimento_nome"] }}</div></div>
                     </td>
                    <td>{{ $value["numero"] }}</td>
                    <td>{!! $value["parcela"] !!}</td>
                    <td>
                        <div><div data-toggle="tooltip" data-html="true" title="{{ $value["banco"] }}">{{ $value["banco"] }}</div></div>
                    </td>
                    <td>{{ $value["data_emissao"] }}</td>
                    <td>{{ $value["data_vencimento"] }}</td>
                    <td>{{ $value["valor_original"] }}</td>
                    <td>{{ $value["juros_cobrados"] }}</td>
                    <td>{{ $value["valor"] }}</td>
                    <td>{!! $value["percentual_juros_diarios"] !!}</td>
                    <td>{!! $value["data_juros"] !!}</td>
                    <td>{!! $value["desconto"] !!}</td>
                    <td>{!! $value["nota_numero"] !!}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
            </tfoot>
        </table>
    </div>
    <div class="col-sm-12 mt-1" id="button-bottom">
        <button type="button" id="bt_alterar_juridico" class="btn btn-success float-right">Alterar para Judicial</button>
    </div>
</form>

<script type="text/javascript">

$(document).ready( function () {
    $(document).find('#bt_alterar_juridico').on('click', function(){
		modalAlterarJuridico();
	});
	$(document).find('#btn-filterform_titulo_para_renegociacao-baixa-titulos').on('click', function(){
		comissaoRagazzi();
	});
	$(document).find('#selecione_todos').on('click', function(){
		if ($(document).find('#selecione_todos').is(':checked')){
			$('input:checkbox').prop("checked", true);
		  }else{
			$('input:checkbox').prop("checked", false);
		  }
	});

    table_filters_cobranca_ragazzi = $('#table-filters-cobranca-ragazzi').DataTable({
		"searching": false,
		"lengthChange": false,
		"info": false,
		"pageLength": 15,
		"orderMulti": false,
		"autoWidth": false,
		'paging': false,
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
				"targets": "tb_number",
				"class": "tb_number",
			},
			{
				"targets": "tb_date",
				"class": "tb_date",
			},
			{ 
				targets: 'tb_selecionar', 
				"class": "text-center",
				width: '13px'
			}
		]
	});
});
 
function modalAlterarJuridico(){
	var myCheckboxes = new Array();
	$(document).find(".titulos_selecionados_juridico:checked").each(function() {
		myCheckboxes.push($(this).val());
	});

	if(myCheckboxes.length <= 0){
		message('Atenção','Selecione pelo menos um título.');
		return false;
	}

	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.alterar_titulo_judicial') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
			titulos_selecionado: myCheckboxes
		},
        success: function(callback){
			if(callback.status == 'success'){
				comissaoRagazzi();
				message('Atenção',callback.message);
			}else{
				comissaoRagazzi();
				message('Atenção',callback.message + "<br>" + callback.error);
			}
        },
        error: function(callback) {
			comissaoRagazzi();
			message('Atenção.','Ocorreu um erro ao tentar alterar os títulos, por favor tente mais tarte');
        }
    });
}

function comissaoRagazzi(){
	table_filters_cobranca_ragazzi.clear().draw();
    $('label.error-message').remove();
	$.ajax({
        url: '{{ route('cliente.posicao_sintetica.cobranca_ragazzi') }}',
        type: 'post',
        dataType: 'json',
        data: {
			_token: '{{ csrf_token() }}',
            abertura: true,
            titulo_cobranca_ragazzi: $(document).find("#titulo_cobranca_ragazzi").val(),
			codigo_cliente: $(document).find("#codigo").val()
		},
        success: function(callback){

			if(callback.response){
				titulos = [];

				for (var fields in callback.response){
					temp_array = [
						callback.response[fields].link,
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + callback.response[fields].estabelecimento_nome + "'>" + callback.response[fields].estabelecimento_nome + "</div></div>",
						callback.response[fields].numero,
						callback.response[fields].parcela,
						"<div><div data-toggle='tooltip' data-html='true' title='' data-original-title='" + callback.response[fields].banco + "'>" + callback.response[fields].banco + "</div></div>",
						callback.response[fields].data_emissao,
						callback.response[fields].data_vencimento,
						callback.response[fields].valor_original,
						callback.response[fields].juros_cobrados,
						callback.response[fields].valor,
						callback.response[fields].percentual_juros_diarios,
						callback.response[fields].data_juros,
						callback.response[fields].desconto,
						callback.response[fields].nota_numero,
					];
					titulos.push(temp_array)
				}


				table_filters_cobranca_ragazzi.rows.add(titulos).draw();   
			}

        },
        error: function(callback) {
			if((callback.responseJSON)){
				var data = callback.responseJSON.error;
				$.each(data, function(index, el) {
					$(document).find('input[name="'+index+'"]').eq(0).parent().append('<label class="error-message error-'+index+'">'+el+'</label>'); 
					$(document).find('input[name="'+index+'"]').eq(0).addClass('error');
				});
				$(document).find('input.error').eq(0).focus();
			}
			
        }
    });
}
</script>
@endsection