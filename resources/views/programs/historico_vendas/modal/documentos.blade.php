@extends('layouts.page-dialog')

@section('content')
    <p>
        <b>Estabelecimento:</b> {{ $nota_info->estabelecimento_descricao }}<br>
        <b>Nota:</b> {{ $nota_info->nota }}<br>
		<b>Chave:</b> {{ $nota_info->chave }}<br>
        <b>Cliente:</b> {{ $nota_info->cliente }}<br>
        @if(isset($nota_info->banco))
        <b>Carteira:</b> {{ $nota_info->banco }}
        @endif
    </p>
    <ul>
        <li>
            <a href='#' onclick="downloadDanfe()"><i class='btn-nota-pdf'></i>PDF</a>
        </li>
        <li>
            <a href='#' onclick="downloadXML()"><i class='btn-download'></i>XML</a>
        </li>
        @if(!empty($nota_info->foto_canhoto))
            <li>
                <a href="{{ $nota_info->foto_canhoto }}"  class="thumb" data-toggle="popover" data-trigger="hover" title="Foto Canhoto" data-content="<img src='{{ $nota_info->foto_canhoto }}' width='250' class='rounded mx-auto d-block' alt='Canhoto'>"><i class='btn-foto-canhoto'></i>Canhoto</a>
            </li>
        @endif
        
        @if(isset($nota_info->boleto))
            @if(count($nota_info->boleto) > 0)
                <li><b>CNPJ/CPF do Pagador:</b> {{ $nota_info->cnpj_sacado }}</li>
                <li><b>CNPJ/CPF do Beneficiário:</b> {{ $nota_info->cnpj_cedente }}</li>
                <li> <b>Título:</b>
                    <ul>
                        @foreach ($nota_info->boleto as $boleto)
                            @if($boleto['banco_codigo'] == 'boleto_nasajon')
                                <li> Título {{ $boleto['titulo'] }}:
                                    <ul>
                                        <li><b>Boleto Download:</b> <a href='#' onclick="downloadBoleto('{{ $boleto['id'] }}')"><i class='btn-nota-pdf'></i>BOLETO</a></li>
                                        <li><b>Vencimento:</b> {{ $boleto['vencimento'] }} </li>
                                    </ul>
                                </li>
                            @elseif($boleto['banco_codigo'] == 'BB')
                                <li> Título {{ $boleto['titulo'] }}:
                                    <ul>
                                        <li><b>Nosso número:</b> {{ $boleto['nosso_numero'] }}</li>
                                        <li><b>Vencimento:</b> {{ $boleto['vencimento'] }} </li>
                                    </ul>
                                </li>
                            @elseif($boleto['banco_codigo'] == 'ITAU')
                                <li> Título {{ $boleto['titulo'] }}:
                                    <ul>
                                        <li><b>Nosso número:</b> {{ $boleto['nosso_numero'] }}</li>
                                        <li><b>Vencimento:</b> {{ $boleto['vencimento'] }} </li>
                                        <li><b>Link:</b> <a target='_blank' href="{{ $boleto['link'] }}">{{ $boleto['link'] }}</a></li>
                                    </ul>
                                </li>
                            @elseif($boleto['banco_codigo'] == 'BRADESCO')
                                <li> Título {{ $boleto['titulo'] }}:
                                    <ul>
                                        <li><b>Nosso número:</b> {{ $boleto['nosso_numero'] }}</li>
                                        <li><b>Vencimento:</b> {{ $boleto['vencimento'] }} </li>
                                    </ul>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </li>
            @endif
        @endif
    </ul>

    <script>
        $(document).ready(function (){
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
        });

        function downloadDanfe(){

            var erro = false;

            $.ajax({
                url: '{{ route('notas_nasajon.testar_danfe') }}',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'id': '{{ $nota_info->id }}'
                },
                type: 'POST',
                error: function(){
                    message('Atenção', 'DANFE não disponível!')
                    erro = true;
                },
				success: function(){
                    $('<form action="{{ route('notas_nasajon.modal.documentos.pdf') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="id" value="{{ $nota_info->id }}">\
                    </form>').appendTo('body').submit().remove();
				}

            });
            

        }

        function downloadBoleto($id){
            $('<form action="{{ route('notas_nasajon.modal.documentos.boleto') }}" method="POST" target="_blank">\
                <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                <input type="hidden" name="id" value="'+$id+'">\
            </form>').appendTo('body').submit().remove();
        }

        function downloadXML(){

            var erro = false;
            
            $.ajax({
                url: '{{ route('notas_nasajon.testar_xml') }}',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'id': '{{ $nota_info->id }}'
                },
                type: 'POST',
                error: function(){
                    message('Atenção', 'XML não disponível!')
                    erro = true;
                },
				success: function(){
					$('<form action="{{ route('notas_nasajon.modal.documentos.xml') }}" method="POST" target="_blank">\
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">\
                        <input type="hidden" name="id" value="{{ $nota_info->id }}">\
                    </form>').appendTo('body').submit().remove();
				}
            });
        }
    </script>
@endsection
