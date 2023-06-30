function loader(){
    var $html_loader = "<div class='content-loader'><div class='loader'></div></div>";
    $("body").append($html_loader);
}
function hide_loader(){
    $(document).find(".content-loader").remove();
}
$.ajaxSetup({
    beforeSend: function(){
        loader();
        $('input').each(function(){
            if($(this).attr("autocomplete") != "off"){
                $(this).attr("autocomplete","off");   
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
    },
    complete: function(){
        hide_loader();
        $('input').each(function(){
            if($(this).attr("autocomplete") != "off"){
                $(this).attr("autocomplete","off");   
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
    },
    error: function(){
        hide_loader();
        $('input').each(function(){
            if($(this).attr("autocomplete") != "off"){
                $(this).attr("autocomplete","off");   
            }
        });
        $('[data-toggle="tooltip"]').tooltip();
    },
    statusCode: {
        409: function() {
            window.location.reload();
        },
        419: function() {
            window.location.reload();
        }
    }
});
