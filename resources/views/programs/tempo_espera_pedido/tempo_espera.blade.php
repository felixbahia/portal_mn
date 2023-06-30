@extends('layouts.app-sem-header')

@section('content')
<div class="tempo-espera-body  bg-light">
    <div class="head">
        <div class="texto">
            <b>STATUS DE PEDIDO</b>
        </div>
        <div class="relogio-digital">
            <div id="relogio-digital">
            </div>
        </div>
        <div class="relogio-analogico">
            <div class="clock">
                <div class="hour"></div>
                <div class="min"></div>
                <div class="sec"></div>
                <span style="transform: rotate(30deg);">|</span>
                <span style="transform: rotate(60deg);">|</span>
                <span style="transform: rotate(90deg);">|</span>
                <span style="transform: rotate(120deg);">|</span>
                <span style="transform: rotate(150deg);">|</span>
                <span style="transform: rotate(180deg);">|</span>
                <span style="transform: rotate(210deg);">|</span>
                <span style="transform: rotate(240deg);">|</span>
                <span style="transform: rotate(270deg);">|</span>
                <span style="transform: rotate(300deg);">|</span>
                <span style="transform: rotate(330deg);">|</span>
                <span style="transform: rotate(0deg);">|</span>
              </div>
        </div>
    </div>
    <div class="barra">
    </div>
    <div class="table-tempo-espera float-left mt-5 w-75">
        <div class="">
            <table class="h1 text-justify" id="table-filter">
                <thead>
                <tr>
                    <th scope="col"><strong>NÚMERO DE PEDIDO</strong></th>
                    <th scope="col"><strong>SEPARAÇÃO</strong></th>
                    <th scope="col"><strong>FATURAMENTO</strong></th>
                    <th scope="col"><strong>RETIRADA</strong></th>
                </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="tempo-espera-footer mb-2">
    <div class="row">
        <div class="col">
            <hr>
            <div class="texto-footer text-center">
                <b>LOJA {{ $estabelecimento }}</b>
            </div>
        </div>
        <div class="col">
            <img class="float-right mr-4" src="{{ URL::asset('images/logomn.jpg') }}">
        </div>
    </div>
</div>
@endsection

@section('script-footer')        
$(document).ready(function(){
    var myVar = setInterval(myTimer ,1000);
    function myTimer() {
        var d = new Date(), displayDate;
       if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1){
          displayDate = d.toLocaleTimeString('pt-BR');
       }else{
          displayDate = d.toLocaleTimeString('pt-BR', {timeZone: 'America/Belem'});
       }
          document.getElementById("relogio-digital").innerHTML = displayDate;
    }

    table_filters = $('#table-filter')
    .on( 'error.dt', function ( e, settings, techNote, men ) {
        hide_loader();
        message("Atenção", "Ocorreu uma instabilidade!<br />Tente novamene mais tarde!");
    }).DataTable({
        "searching": false,
        "lengthChange": false,
        "info": false,
        "paging": true,
        "orderMulti": false,
        "pageLength": 5,
        "ordering": false,
        "language": {
            "decimal":        ",",
            "emptyTable":     "Sem Pedidos.",
            "infoPostFix":    "",
            "thousands":      ".",
            "loadingRecords": "Carregando...",
            "processing":     "Processando...",
            "zeroRecords":    "Sem Pedidos.",
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
        ]
    });
    
    buscarPedidos();

    var pageInfo = table_filters.page.info();
   
    endInt = pageInfo.pages;
   
    currentInt = 0;
   
    interval = setInterval(function(){
        table_filters.page( currentInt ).draw( 'page' );
   
        currentInt++;
   
        if ( currentInt ===  endInt){
          currentInt = 0;
        }
   
    }, 6000);


    $(document).find(".dataTables_paginate").hide();

    interval = setInterval(function(){
        buscarPedidos();
        $(document).find(".dataTables_paginate").hide();
        
    }, 60000);
});

const hour = document.querySelector(".hour");
const min = document.querySelector(".min");
const sec = document.querySelector(".sec");

function getTime() {
const time = new Date();

const getHourRot = (360 / 12) * time.getHours();
const getMinRot = (360 / 60) * time.getMinutes();
const getSecRot = (360 / 60) * time.getSeconds();

hour.style.transform = `rotate(${getHourRot}deg)`;
min.style.transform = `rotate(${getMinRot}deg)`;
sec.style.transform = `rotate(${getSecRot}deg)`;
}

setInterval(() => {
getTime();
}, 1000);

getTime();

function buscarPedidos(){
    $.ajax({
        url: '{{ route("tempo_espera_pedido.monitorar") }}',
        dataType: 'json',
        method: 'POST',
        async: false,
        data: {_token: "{{ csrf_token() }}", estabelecimento: "{{ $codigo }}"},
        success: function(data){

            var response = data.response.pedidos;
            var linhas = [];

            for(var field in response){
                
                titulo_linha = [
                    response[field].pedido_nasajon,
                    response[field].inicio,
                    response[field].em_faturamento_nasajon,
                    response[field].fim_separacao_nasajon
                ];

                linhas.push(titulo_linha);

            }

            table_filters.clear().draw();
            table_filters.rows.add(linhas).nodes().draw();

        },
        error: function (data){
            var errors = data.responseJSON.error;
            for(var field in errors){
                showErrorsInputs(form, field, errors[field])
            }
        }
    });
}

@endsection