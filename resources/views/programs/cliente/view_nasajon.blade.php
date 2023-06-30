@extends('layouts.page-dialog')

@section('content')
    <div class="content-view-cliente">
        <div class="row-table">
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Código de cadastro</div></div>
                <div class="value">{!! $dados["codigo"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CPF / CNPJ</div></div>
                <div class="value">{!! $dados["cpf_cnpj"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Inscrição estadual</div></div>
                <div class="value">{!! $dados["inscricaoestadual"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Indicador da Inscrição Estadual</div></div>
                <div class="value">{!! $dados["indicadorinscricaoestadual"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome / Razão Social</div></div>
                <div class="value">{!! $dados["nome"] !!}</div>
            </div>
            <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nome Fantasia / Apelido</div></div>
                <div class="value">{!! $dados["nomefantasia"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-12">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Telefone</div></div>
                <div class="value">{!! $dados["telefones"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (NFe)</div></div>
                <div class="value">{!! $dados["email"] !!}</div>
            </div>
            {{--  <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (Compras; post.pedido, etc..)</div></div>
                <div class="value">{!! $dados["email_adic1"] !!}</div>
            </div>
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">E-mail (Cobrança)</div></div>
                <div class="value">{!! $dados["email_adic2"] !!}</div>
            </div>  --}}
        </div>
        @foreach($dados['contatos'] as $contato)
            <div class="row-table">
                <div class="cel-table col-md-3">
                    <div class="title">Contato: </div>
                    <div class="value">{!! $contato['nome'] !!}</div>
                </div>
                <div class="cel-table col-md-3">
                    <div class="title">Cargo: </div>
                    <div class="value">{!! $contato['cargo'] !!}</div>
                </div>
                <div class="cel-table col-md-3">
                    <div class="title">Telefone: </div>
                    <div class="value">{!! $contato['telefone'] !!}</div>
                </div>
                <div class="cel-table col-md-3">
                    <div class="title">E-mail: </div>
                    <div class="value">{!! $contato['email'] !!}</div>
                </div>
            </div>
        @endforeach
        <div class="row-table">
            <div class="cel-table col-lg-1">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">CEP</div></div>
                <div class="value">{!! $dados["cep"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Endereço</div></div>
                <div class="value">{!! $dados["endereco"] !!}</div>
            </div>
            <div class="cel-table col-lg-1">
                    <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Nº</div></div>
                    <div class="value">{!! $dados["numero"] !!}</div>
                </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Bairro</div></div>
                <div class="value">{!! $dados["bairro"] !!}</div>
            </div>
            <div class="cel-table col-lg-3">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cidade</div></div>
                <div class="value">{!! $dados["cidade"] !!}</div>
            </div>
            <div class="cel-table col-lg-1">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Estado</div></div>
                <div class="value">{!! $dados["uf"] !!}</div>
            </div>
        </div>
        <div class="row-table">
            {{--  <div class="cel-table col-lg-6">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Mensagem de Alerta</div></div>
                <div class="value">{!! $dados["alerta"] !!}</div>
            </div>  --}}
            <div class="cel-table col-lg-4">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Representante</div></div>
                <div class="value">{!! $dados["vendedor"] !!}</div>                
            </div>
            @if(!empty($dados["gerente"]))
            <div class="cel-table col-lg-3">
                <div class="title">Gerente</div>
                <div class="value">{!! $dados['gerente'] !!}</div>
            </div>
            @endif
            <div class="cel-table col-lg-2">
                <div class="title"><div data-toggle="tooltip" data-trigger="hover focus click" data-placement="top">Cliente Desde</div></div>
                <div class="value">{!! $dados["cliente_desde"] !!}</div>
            </div>
        </div>
        @if(!empty($dados["contrato_caminho"]))
        <div class="row-table">
            <div class="cel-table col-lg-3">
                <div class="title">Contrato</div>
                <div class="value"><a href="{!! $dados["contrato_caminho"] !!}" target="_blanck" class="bt_manual_cliente text-right"><i class='btn-nota-pdf'></i>Contrato Fornecimento</a></div>                
            </div>
            <div class="cel-table col-lg-3">
                <div class="title">E-mail</div>
                <div class="value">{!! $dados["contrato_email"] !!}</div>                
            </div>
            <div class="cel-table col-lg-3">
                <div class="title">Ip</div>
                <div class="value">{!! $dados["contrato_ip"] !!}</div>                
            </div>
            <div class="cel-table col-lg-3">
                <div class="title">Data</div>
                <div class="value">{!! $dados["contrato_criacao"] !!}</div>                
            </div>
        </div>
        @endif
        @if(!empty($dados["documentos"]))
        <div class="content-tab">
            <div class="row">
                <div class="col-md-12">
                    <h3><center>Documentos</center></h3>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="content-dialog-table">
                        <table class="table table-striped table-not-edit table-not-view" id="table-documentos">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($dados["documentos"] as $value)
                                <tr>
                                    <td>{{ $value["descricao"] }}</td>
                                    @if($value["extensao"] == 'jpg' || $value["extensao"] == 'png' || $value["extensao"] == 'PNG' || $value["extensao"] == 'JPG')
                                        <td><a href="{{ $value["documento"] }}" data-toggle="popover" data-trigger="hover" aria-readonly="true" title="Documento" data-content='<img src="{{ $value["documento"] }}" width="250" class="rounded mx-auto d-block" alt="Documento">' class="thumb" alt="Documento"><i class="btn-foto-canhoto"></i>Documento</a></td>
                                    @else
                                    <td><a href="{{ $value["documento"] }}" title="Documento" data-content="{{ $value["documento"] }}" target="blank"><i class="btn-download"></i>Documento</a></td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <script>
        $('.content-view-cliente').find(".value, .title").find("div").off('mouseenter');
        $('.content-view-cliente').find(".value, .title").find("div").on('mouseenter', function(){
            var $this = $(this);
            if(this.offsetWidth < this.scrollWidth && !$this.attr('title')){
                $this.attr('data-original-title', $this.text());
            }
        });

        $('#table-documentos').on( 'error.dt', function ( e, settings, techNote, men ) {
		    hide_loader();
		    message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
	    }).DataTable({
		"show": function(event, ui) {
			var oTable = $('div.dataTables_scrollBody>table.display', ui.panel).dataTable();
			if ( oTable.length > 0 ) {
				oTable.fnAdjustColumnSizing();
			}
		},
		"searching": false,
		"lengthChange": false,
		"info": false,
		"autoWidth": true,
		"pageLength": 15,
		"orderMulti": false,
		"sScrollY": "200px",
		"bScrollCollapse": true,
		"bPaginate": false,
		"bJQueryUI": true,
		"drawCallback": function(settings) {
			$('[data-toggle="popover"]').popover({
				container: 'body',
				html: true,
				show: true,
				template: '<div class="popover popover-estoque" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>'
			});
			$('[data-toggle="popover"]').on('show.bs.popover', function () {
				var $this = $(this);
				$('.popover').not($this).each(function(){
					$("[aria-describedby='"+$(this).attr("id")+"']").popover('hide');
				});
				$("body").on("keyup", function(e){
					if(e.keyCode == 27){
						$($this).popover('hide');
					}
				});
			});

			$(document).find("a.thumb").fancybox(
				{
					onComplete: function(){
						$('#fancybox-content')
							.on('mouseover', function(){
								$(this).children('#fancybox-img').css({'transform': 'scale(1.5)'});
							})
							.on('mouseout', function(){
								$(this).children('#fancybox-img').css({'transform': 'scale(1)'});
							})
							.on('mousemove', function(e){
								$(this).children('#fancybox-img').css({'transform-origin': ((e.pageX - $(this).offset().left) / $(this).width()) * 100 + '% ' + ((e.pageY - $(this).offset().top) / $(this).height()) * 100 +'%'});
						});
					}
				}
			);

		},
		"language": {
			"decimal":        ".",
			"emptyTable":     "Nenhum Documento Encontrado",
			"infoPostFix":    "",
			"thousands":      ",",
			"loadingRecords": "Carregando...",
			"processing":     "Processando...",
			"zeroRecords":    "Nenhum Documento Encontrado",
			"paginate": {
				"first":      "<<",
				"last":       ">>",
				"next":       ">",
				"previous":   "<"
			}
		},
	}).draw();
    </script>
@endsection