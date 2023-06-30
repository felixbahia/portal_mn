@extends('layouts.page-dialog')

@section('content')
<div class="row justify-content-between">
    <div class="col-lg-12">
        <a href="{{ $imagem }}" class="foto-thumb"><img class="img-thumbnail" src="{{ $imagem }}" /></a>
    </div>
</div>
<script>
    $('[data-toggle="tooltip"]').tooltip();
    $(document).ready(function(){
        $(document).find(".foto-thumb").fancybox(
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
</script>
@endsection
