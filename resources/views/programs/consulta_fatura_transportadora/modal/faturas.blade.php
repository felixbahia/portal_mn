@extends('layouts.page-dialog')

@section('content')
<div class="content-dialog-table">
    <table class="table table-striped table-filter-dialog footer-pequeno" id="table-filters-dialog_faturas">
        <thead>
            <tr>
                <th></th>
                <th>Transportadora</th>
                <th class="tb_number">Fatura</th>
                <th class="tb_number">Valor Fatura</th>
                <th class="tb_number">Peso Fatura</th>
                <th class="tb_number">Valor NF Fatura</th>
                <th class="tb_number">Notas</th>
                <th class="tb_number">Valor Notas</th>
                <th class="tb_number">Peso Notas</th>
                <th class="tb_number">% Frete</th>
                <th></th>
                <th>Situação</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dados as $key => $value)
            <tr>
                <td>
                    @if($value["status"] == 'Pendente') <a href="#" class="bt-reprove fatura{{ $key }}" data-toggle="tooltip" data-placement="top" onclick="reprovar('{{ $value['id'] }}','{{ $value['fatura'] }}','fatura{{ $key }}','valor-situacao{{ $key }}','valor-status{{ $key }}');"></a> @endif
                </td>
                <td>
                    <div><div data-toggle="tooltip" data-html="true" title="{{ $value["transportadora"] }}">{{ $value["transportadora"] }}</div></div>
                </td>
                <td>
                    <a href="#" class="modal-fatura" data-title="DETALHES DA FATURA: {{ $value["fatura"] }}" data-route='{{ route("ocorrencia_entrega.modal.exibir_fatura") }}' data-fatura='{{ $value["fatura"] }}'>{{ $value['fatura'] }}</a>
                </td>
                <td>{{ $value["valor_fatura"] }}</td>
                <td>{{ $value["peso_fatura"] }}</td>
                <td>{{ $value["notas_fatura"] }}</td>
                <td>{{ $value["notas"] }}</td>
                @if(substr($value["valor_notas"],0,3) == substr($value["notas_fatura"],0,3) && !empty($value["valor_notas"]))
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'>{{ $value["valor_notas"] }} <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
                    </td>
                @elseif(!empty($value["valor_notas"]))
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'>{{ $value["valor_notas"] }} <i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>
                    </td>
                @else
                    <td></td>
                @endif
                @if(substr($value["peso_notas"],0,3) == substr($value["peso_fatura"],0,3) && !empty($value["peso_notas"]))
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> {{ $value["peso_notas"] }} <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
                    </td>
                @elseif(!empty($value["peso_notas"]))
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> {{ $value["peso_notas"] }} <i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>
                    </td>
                @else
                    <td></td>
                @endif
                @if( $value["divergencia"] == false)
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='OK'> {{ $value["percentual_frete"] }} <i class='fa fa-check check-icon' aria-hidden='true'></i></div></div>
                    </td>
                @elseif($value["divergencia"] == true)
                    <td>
                        <div><div data-toggle='tooltip' data-html='true' title='' data-original-title='Não OK'> {{ $value["percentual_frete"] }} <i class='fa fa-times error-icon'  aria-hidden='true'></i></div></div>
                    </td>
                @else
                    <td>
                        {{ $value["percentual_frete"] }}
                    </td>
                @endif
                <td>
                    @if($value["status"] == 'Pendente')<a class="bt-aprove fatura{{ $key }}" data-toggle="tooltip" data-placement="top" onclick="aprovar('{{ $value['id'] }}','{{ $value['fatura'] }}','fatura{{ $key }}','valor-situacao{{ $key }}','valor-status{{ $key }}');"></a> @endif
                </td>
                <td>
                    <span class="valor-situacao{{ $key }}">{{ $value["situacao"] }}</span>
                </td>
                <td>
                    <span class="valor-status{{ $key }}">{{ $value["status"] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td class='text-right'>Total:</td>
                <td class='tb_number'>{{ $total['fatura_quantidade'] }}</td>
                <td class='tb_number'>{{ parserValor($total['valor_fatura']) }}</td>
                <td class='tb_number'>{{ parserQtd($total['peso_fatura']) }}</td>
                <td class='tb_number'>{{ parserValor($total['notas_fatura']) }}</td>
                <td class='tb_number'>{{ $total['notas'] }}</td>
                <td class='tb_number'>{{ parserValor($total['valor_notas']) }}</td>
                <td class='tb_number'>{{ parserValor($total['peso_notas']) }}</td>
                <td class='tb_number'>{{ parserValor($total['percentual_frete']).'%' }}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
<script type="text/javascript">
$(document).ready( function () {
    table_filters_dialog_faturas = $('#table-filters-dialog_faturas').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "pageLength": 20,
        "autoWidth": false,
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
                    "targets": "tb_number"
                },
            ],
    });    

    table_filters_dialog_faturas.on('draw', function () {
        $(document).find(".modal-fatura").off("click");
        $(document).find(".modal-fatura").on("click", function(event){
            event.stopPropagation();
            showModalFatura($(this));
        });
    });
    table_filters_dialog_faturas.draw();
});

function showModalFatura($this){
    var $url = $($this).data("route");
    var $fatura = $($this).data("fatura");
    var $title = $($this).data("title");

    $.ajax({
        url: $url,
        method: 'POST',
        data: {_token: "{{ csrf_token() }}", fatura : $fatura},
        success: function(body){
            createModal("table-itens-fatura", $title, body, 'modal-lg');
        }
    });
}


function aprovar($id,$fatura,$identificador,$valor,$status){
    var $class = "dialog_option_deletar";
    var $name_option_sim = "aprovar_fatura_sim";
    var $option_sim = "Sim";
    var $name_option_nao = "aprovar_fatura_nao";
    var $option_nao = "Não";
    message_sim_nao("Atenção", "Deseja <b class='text-success'>APROVAR</b> a fatura "+$fatura+"?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
    
    $(document).off("aprovar_fatura_nao");
    $(document).on("aprovar_fatura_nao", function(){
        modal = $(this).parents(".modal");
        modal.modal('hide');
    });

    $(document).off("aprovar_fatura_sim");
    $(document).on("aprovar_fatura_sim", function(){
        modal = $(this).parents(".modal");
        
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id,
                situacao: true
            },
            url: '{{ route('ocorrencia_entrega.aprovar_fatura') }}',
            success: function(data){
                if(data.status == 'error'){
                    message("Atenção",data.message);
                    modal.modal('hide');
                }else{
                    $('.'+$identificador).prop("onclick", null).addClass('d-none');
                    $('.'+$valor).html('APROVADA');
                    $('.'+$status).html('Finalizada');
                    modal.modal('hide');
                }
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message);
                modal.modal('hide');
            }
        });
    });

}

function reprovar($id, $fatura,$identificador,$valor,$status){
    var $class = "dialog_option_deletar";
    var $name_option_sim = "aprovar_fatura_sim";
    var $option_sim = "Sim";
    var $name_option_nao = "aprovar_fatura_nao";
    var $option_nao = "Não";
    message_sim_nao("Atenção", "Deseja <b class='text-danger'>REPROVAR</b> a fatura "+$fatura+"?", $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao);
    
    $(document).off("aprovar_fatura_nao");
    $(document).on("aprovar_fatura_nao", function(){
        modal = $(this).parents(".modal");
        modal.modal('hide');
    });

    $(document).off("aprovar_fatura_sim");
    $(document).on("aprovar_fatura_sim", function(){
        modal = $(this).parents(".modal");
        
        $.ajax({
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: $id,
                situacao: false
            },
            url: '{{ route('ocorrencia_entrega.aprovar_fatura') }}',
            success: function(data){
                if(data.status == 'error'){
                    message("Atenção",data.message);
                    modal.modal('hide');
                }else{
                    $('.'+$identificador).prop("onclick", null).addClass('d-none');
                    $('.'+$valor).html('Reprovada');
                    $('.'+$status).html('Finalizada');
                    modal.modal('hide');
                }
            },
            error: function callback(data){
                message('Atenção!', data.responseJSON.message);
                modal.modal('hide');
            }
        });

    });
}

</script>
@endSection