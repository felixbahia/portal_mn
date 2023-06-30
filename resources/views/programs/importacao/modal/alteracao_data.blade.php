@extends('layouts.page-dialog')

@section('content')
<div id="tudo">
    <form id='mudar_data' name='mudar_data'>
        @csrf
        <div class="border-bottom" id="info-pedido">
            <div class="row mt-3">
                <div class="col-sm-5">
                    @if($tipo == 'embarque_etd')
                        <b>{!! Form::label('data_embarque_etd', 'Data Embarque ETD', ['class' => 'bold']) !!}</b>
                        {!! Form::text('data_embarque_etd', '', ['id' => 'data_embarque_etd', 'class' => 'form-control data_muda', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                    @else 
                        <b>{!! Form::label('data_chegada_eta', 'Chegada no Porto ETA', ['class' => 'bold']) !!}</b>
                        {!! Form::text('data_chegada_eta', '', ['id' => 'data_chegada_eta', 'class' => 'form-control data_muda', "data-tipo" => "data", 'onchange' => "salvarEmMudanca($(this))"]) !!}
                    @endif
                </div>
            </div>

            <div class="row my-3">
                <div class="col-sm-12 text-right">
                    <button type='submit'class="btn btn-success" id="salvar">Salvar</button>	        
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    $(document).ready( function() {

        form_mudar_data = $(document).find('#mudar_data');
        form_mudar_data.find('.data_muda').mask('00/00/0000');
        form_mudar_data.find('.data_muda').datepicker({
            language: 'pt-BR',
            format: 'dd/mm/yyyy',
            zIndex: $(document).find("#alterar_data_modal").css("z-index") + 1,
            autoHide: true
        });

        form_mudar_data.on('submit', function(){
            event.preventDefault();
            event.stopPropagation();

            @if($tipo == 'embarque_etd')
                form_modal_importacao.find('.data_embarque_realizado').html(form_mudar_data.find('#data_embarque_etd').val());
                form_modal_importacao.find('#embarque_realizado').val(form_mudar_data.find('#data_embarque_etd').val());
                form_modal_importacao.find("#label_etd_booking").html('Confirmado('+form_mudar_data.find('#data_embarque_etd').val()+')');
                form_modal_importacao.find("#etd_booking").prop('checked', true);
            @else 
                form_modal_importacao.find('.data_chegada_porto_realizado').html(form_mudar_data.find('#data_chegada_eta').val());
                form_modal_importacao.find('#chegada_porto_realizado').val(form_mudar_data.find('#data_chegada_eta').val());
                form_modal_importacao.find("#label_eta_booking").html('Confirmado('+form_mudar_data.find('#data_chegada_eta').val()+')');
                form_modal_importacao.find("#eta_booking").prop('checked', true);
            @endif

            calculoCycleTime();

            $(document).find('#alterar_data_modal').modal('hide');
        });
    });
</script>
@endsection