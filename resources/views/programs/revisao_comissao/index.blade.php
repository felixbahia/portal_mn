@extends('layouts.app')
@section('content-filter')

<form action="{{ route('revisao_comissao.pesquisa') }}" name="form_filter" id="form_filter" onsubmit="return false;">
    @csrf
    <h3>{{ CustomView::programaName() }}</h3>
    <div class="content-fields">
        <div class="row">
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::select('estabelecimento', $estabelecimentos, '',  ['id' => 'estabelecimento', 'class' => 'form-control', 'placeholder' => 'Estabelecimento']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('pedido', '', ['id' => 'pedido', 'class' => 'form-control', 'placeholder' => 'Código do Pedido']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('nome', '', ['id' => 'nome', 'class' => 'form-control', 'placeholder' => 'Nome do cliente']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('data_inicio', date('d/m/Y', strtotime('-1 month')), ['id' => 'data_inicio', 'class' => 'form-control data', 'placeholder' => 'Início do Período']) }}
            </div>
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('data_fim', date('d/m/Y'), ['id' => 'data_fim', 'class' => 'form-control data', 'placeholder' => 'Fim do Período']) }}
            </div>
        </div>
        <div class="row">
            @if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") !== false || strtolower(Auth::user()->tipo_usuario->nome) === 'administrador')
            <div class="form-group col-sm-2">
                {{ Form::select('diretor', $dropdown_diretores, '', ["id" => 'diretor_filtro', 'class' => 'form-control', 'placeholder' => 'Todos os diretores', 'onchange' => 'retornaGerentesEVendedores($(this).val())' ])}}
            </div>
            <div class="form-group col-sm-2">
                {{ Form::select('gerente', $dropdown_gerentes, '', ["id" => 'gerente_filtro', 'class' => 'form-control', 'placeholder' => 'Todos os gerentes', 'onchange' => 'retornaVendedores($(this).val())'])}}
            </div>
            @endif
            @if (count($dropdown_usuarios) > 0)
            <div class="form-group col-sm-2">
                {{ Form::select('usuario', $dropdown_usuarios, '', ["id" => 'usuario_filtro', 'class' => 'form-control', 'placeholder' => 'Todos os vendedores'])}}
            </div>
            @endif
            <div class="form-group col-lg-2 col-xl-2">
                {{ Form::text('pedido_gerado', '', ['id' => 'pedido_gerado', 'class' => 'form-control', 'placeholder' => 'Pedido Prologos']) }}
            </div>
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
        <table class="table table-striped table-filters table-not-edit table-not-view" id="table-filters">
        <thead>
            <tr>
                <th>Estabelecimento</th>
                <th>Pedido</th>
                <th>Cliente</th>
                <th class='data_field'>Data do pedido</th>
                <th>Condição de pagamento</th>
                <th>Vendedor</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    </div>
@endsection

<script>
@section('script-footer')

    $(document).ready(function(){
        $(document).find("#btn-filterform").on('click', function(){
            filterAjax($(this).parents('form'));
        });

        $('.data').mask('00/00/0000');
        $('.data').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            endDate: new Date(),
            zIndex: 100,
            autoHide: true
        });

        $(document).find('#btn-clearform').on('click', function(){
            table_filters.clear().draw();
        })
    });

    function filterAjax(data_form){

        table_filters.clear().draw();

        $.ajax({
            url: data_form.prop('action'),
            dataType: 'json',
            data: data_form.serialize(),
            method: 'POST',
            success: function(data){

                if(data.length > 0){
                    var fields_filter = [];
                    for(var field in data){
                        var temp_field = [
                            data[field].estabelecimento,
                            createBtPedido(data[field]),
                            data[field].cliente,
                            data[field].data,
                            data[field].condicao_pagamento,
                            data[field].vendedor,
                            createBtView(data[field]),
                        ];
                        fields_filter.push(temp_field);
                    }
                    table_filters.rows.add(fields_filter).draw().nodes();
                }

            }
        });
    }

    function createBtView($this){

        html = "<a href=\"#\" class=\"bt-view\" data-toggle='tooltip' data-html='true' title='Visualizar' onclick=\"viewModal("+$this.pedido+", '"+$this.origem+"', '" + $this.id + "', '" + $this.vendedor_id + "', '" + $this.user_id + "')\"></a>";

        return html;
    }

    function viewModal($pedido, $origem, $id, $vendedor_id = null, $user_id = null){

        $.ajax({
            url: '{{ route('revisao_comissao.retorna_pesquisa') }}',
            data: {
                pedido: $pedido,
                origem: $origem,
                id: $id,
                _token: '{{ csrf_token() }}',
                vendedor_id: $vendedor_id,
                user_id: $user_id
            },
            method: 'POST',
            success: function(data){

                console.log(data);

                $id = 'pedido_modal_comissoes';
                $title = 'Demonstrativo dos Cálculos';
                $class = 'modal-lg';

                createModal($id, $title, data, $class);
            
            },
        });
    }

    function createBtPedido(obj){

        var html = "<a href='#' class=\"bt-view_pedido\" data-toggle=\"tooltip\" data-trigger='hover' title=\"Pedido\" onclick=\"abrirPedido('"+obj.pedido+"')\">"+obj.pedido+"</a>";

        return html;
    }

    function abrirPedido($id){
        $.ajax({
            url: '{{ route('pedido_portal.detalhes') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                pedido_id: $id 
            },
            success: function (data){
                createModal('detalhes', 'Detalhes do Pedido', data, 'modal-lg');
            }
        });
    }

    @if (strpos(strtolower(Auth::user()->tipo_usuario->nome), "diretor") === false)
    function retornaGerentesEVendedores($diretor){

        $.ajax({
            url: '{{ route('usuario.gerentes_vendedores') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                diretor: $diretor
            },
        })
        .done(function(data) {
            $('#gerente_filtro').empty();
            $('#usuario_filtro').empty();

            $('#gerente_filtro').append("<option value=''>Todos os gerentes</option>");
            $('#usuario_filtro').append("<option value=''>Todos os vendedores</option>");

            for (var fields in data.gerentes){
                $('#gerente_filtro').append("<option value='"+fields+"'>"+data.gerentes[fields]+"</option>");
            }

            for (var fields in data.vendedores){
                $('#usuario_filtro').append("<option value='"+fields+"'>"+data.vendedores[fields]+"</option>");
            }
        });
    }

    function retornaVendedores($gerente){

        $.ajax({
            url: '{{ route('usuario.vendedores') }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                gerente: $gerente
            },
        })
        .done(function(data) {
            $('#usuario_filtro').empty();

            $('#usuario_filtro').append("<option value=''>Todos os vendedores</option>");

            for (var fields in data.vendedores){
                $('#usuario_filtro').append("<option value='"+fields+"'>"+data.vendedores[fields]+"</option>");
            }
        });
    }
    @endif

@endsection
</script>
