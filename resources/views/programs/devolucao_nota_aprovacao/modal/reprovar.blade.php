@extends('layouts.page-dialog')

@section('content')
<form action="#" id='form-reprovacao-devolucao' onsubmit='return false;'>
    @csrf
    {{ Form::hidden('id', $id) }}
    <h5>Reprovação da requisição</h5>

    <div class="row">
        <div class="col-sm-6">
            <p>
                <b>Estabelecimento</b><br>
                {{ $estabelecimento }}
            </p>
        </div>
        <div class="col-sm-3">
            <p>
                <b>Nota</b><br>
                <a href="#" data-toggle="tooltip" data-placement="top" title="Detalhes no Nasajon" onclick="showModalNotaModal('{!! $nota_id !!}')">{!! $nota_fiscal !!}</a>
            </p>
        </div>
        <div class="col-sm-3">
            <p>
                <b>Valor</b><br>
                {{ $valor }}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm">
            <p>
                <b>Cliente</b><br>
                {{ $cliente }}
            </p>
        </div>
    </div>
    <div class="row">
        <div class="col-sm" id='valor-div'>
            <p>
                @if($valor_parcial === true)             
                <b>Valor da devolução</b><br>
                {!! $valor_devolvido !!}
                @else
                <b>Devolução total</b>
                @endif
            </p>
        </div>
        <div class="col-sm">
            <p>
                <b>Motivo</b><br>
                {!! $motivo !!}
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col">
            <b>Motivo da reprovação</b>
            {{ Form::textarea('motivo_reprovacao', '', ['id' => 'motivo_reprovacao', 'class' => 'form form-control', 'col' => '5', 'rows' => '4']) }}
        </div>
    </div>

    @if($status == 1)
    <div class="row">
        <div class="col">
            {!! Form::label('laudo_imagem', 'Laudo técnico') !!}
            {!! Form::file('laudo_imagem', ['class' => 'form form-control']) !!}
        </div>
    </div>
    @endif
    <div class="row @if(empty($produtos))d-none @endif valor-div">
        <div class="content-dialog-table">
            <table class="table table-striped table-filter-dialog" id="table-filters-dialog-produtos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Grupo</th>
                        <th>Descrição</th>
                        <th class="tb_number">Quantidade</th>
                        <th class="tb_number">Devolvida</th>
                        <th>Recebida</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($produtos as $produto)
                    <tr>
                        <td>{{ $produto['codigo'] }}</td>
                        <td>{{ $produto['grupo'] }}</td>
                        <td><div><div data-toggle='tooltip' data-html='true' title='' data-original-title='{{ $produto['descricao'] }}'>{{ $produto['descricao'] }}</div></div></td>
                        <td>{{ $produto['quantidade'] }}</td>
                        <td>{{ $produto['quantidade_devolvida'] }}</td>
                        <td>{!! Form::text('quantidade_recebida', '', ['data-id' => $produto['id'], 'class' => 'text-right form-control form-float quantidade_recebida', 'size' => '10']) !!}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>        
    </div>

    <div class="row">
        <div class="col">
            {{ Form::button('Salvar reprovação', ['class' => 'btn btn-success float-right mt-3', 'id' => 'btn-enviar-modal']) }}
        </div>
    </div>
</form>

<script>

    $(document).ready(function(){
        $(document).find('#btn-enviar-modal').on('click', function(){
            enviar();
        });

        $(document)
            .find('.form-float')
            .maskMoney({thousands:'.', decimal:','});
    })

    table_filters_dialog_produtos = $(document).find('#table-filters-dialog-produtos').DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "autoWidth": false,
        "paging": false,
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
            { "targets": -1, "width": '75px', 'sortable': false},
            { "class": "tb_number", type: 'num-fmt', targets: "tb_number", "width": '1%'},
            { "class": "tb_date", targets: "sort-date" }
        ],
        "order": [[ 1, 'asc' ]]
    });

    function enviar(){

        var form = $(document).find('#form-reprovacao-devolucao');

        var dados = new FormData($(document).find('#form-reprovacao-devolucao')[0]);

        form.find('.error-message').remove();
        form.find('.error-input').removeClass('error-input');

        @if($status == 4){
            $(document)
                .find("#form-reprovacao-devolucao")
                .find('.quantidade_recebida')
                .each(function(i, e){

                    var id = $(this).data('id');
                    var valor = $(this).val();

                    dados.append('produtos['+i+'][id]', id);
                    dados.append('produtos['+i+'][quantidade_recebida]', valor);
                });
        }
        @endif

        $.ajax({
            url: '{{ route("devolucao_nota_aprovacao.reprovar") }}',
            method: 'POST',
            data: dados,
            processData: false,
            contentType: false,
            success: function(){
                $(document).find("#reprovar-devolucao-modal").modal('hide');
                buscarNotas();
            },
            error: function callback(data){
                var errors = data.responseJSON.error;

                form.find('.error-message').remove();
                for(var field in errors){
                    showErrorsInputs(form, field, errors[field])
                }
            }
        })
    }

    function showErrorsInputs(form, input, message){
        var $input = form.find("input[name='"+input+"'], textarea[name='"+input+"']");

        $input.after("<label class='error-message' for='"+input+"'>"+message+"</label>");
        $input.addClass('error-input');
    }
</script>
@endsection