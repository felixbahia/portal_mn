@extends('layouts.page-dialog')
@section('content')

                    <p><img src="{{ $produto['logo_ficha'] }}" align="right"></p>
                    <div id="detalhes_ficha_comercial" name="detalhes_ficha_comercial"    style="
                    border-right-color: #d4d4d4 !important;
                    border-right: 1px;
                    border-right-style: solid;">
                        <h4><b>Grupo:</b> {{ $produto['grupo']}}</h4></br>

                            <hr>
                            <table class="table table-striped"  id="table-filters" >
                                <tbody>
                                    <tr>
                                        <td colspan="3"><b>Característica:</b> {{ $produto['caracteristica']}}</td>
                                    </tr>
                                    <tr>
                                        <td><b>Peças de:</b> {{ $produto['tamanho_pecas']}}</td>
                                        <td><b>Origem:</b> {{ $produto['origem']}}</td>
                                        <td><b>Gramatura Linear G/M:</b> {{ $produto['gramatura']}}</td>
                                         </tr>
                                    <tr>
                                      
                                        <td ><b>Encolhimento %:</b> {{ $produto['encolhimento']}}</td>
                                        <td  colspan="2"><b>Rendimento MT/KG:</b> {{ $produto['rendimento']}}</td>
                                        
                                    </tr>
                                    <tr>
                                        <td><b>Titulo Trama:</b> {{ $produto['titulo_urdume']}}</td>
                                        <td><b>Titulo Urdume:</b> {{ $produto['titulo_trama']}}</td>
                                    </tr>
                                    <tr>
                                        <td><b>Ligamento:</b> {{ $produto['ligamento']}}</td>
                                        <td  colspan="2"><b>Construção:</b> {{ $produto['construcao']}}</td>
                                    </tr>
                                    <tr>
                                        <td><b>Instrução de Lavagem:</b></td>
                                        <td colspan="2"><img src="{{ $produto['img_instrucoes_lavagem'] }}" width= "30%""></td>
                                    </tr>
                                </tbody>
                            </table>
      

            </td>
        </tr>
    </table>
</div>
@endsection