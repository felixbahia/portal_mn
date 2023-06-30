
function message($title, $text, $class, $width = 400, $minHeight = 200){
    $class_tratado = "message-alert message-"+$class;
    var mensagem = $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: $minHeight,
        width: $width,
        modal: true,
        buttons: {
            "OK": function () {
                $(this).dialog("close");
            }
        }
    });

	$(document).on("keyup", function(event){
        var $this = $(this);
        if(event.which == 27 || event.which == 13){
            mensagem.dialog("close");
        }
    });
}

function message_option($title, $text, $class, $name_option_ok, $option_ok, $name_option_cancelar, $option_cancelar){
    $class_tratado = "message-alert message-"+$class;
    $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: 200,
        width: 400,
        modal: true,
        buttons: {
            "Cancelar": function () {
                $(document).trigger($name_option_cancelar, [$option_cancelar]);
                $(this).dialog("close");
            },
            "Ok": function() {
                $(document).trigger($name_option_ok, [$option_ok]);
                $(this).dialog("close");
            }
        }
    });
}

function message_option_sim_nao($title, $text, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cancelar, $option_cancelar){
    $class_tratado = "message-alert message-"+$class;
    $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: 200,
        width: 400,
        modal: true,
        buttons: {
            "Cancelar": function () {
                $(document).trigger($name_option_cancelar, [$option_cancelar]);
                $(this).dialog("close");
            },
            "Não": function () {
                $(document).trigger($name_option_nao, [$option_nao]);
                $(this).dialog("close");
            },
            "Sim": function() {
                $(document).trigger($name_option_sim, [$option_sim]);
                $(this).dialog("close");
            }
        }
    });
}

function message_sim_nao($title, $text, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao){
    $class_tratado = "message-alert message-"+$class;
    $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: 200,
        width: 400,
        modal: true,
        buttons: {
            "Não": function () {
                $(document).trigger($name_option_nao, [$option_nao]);
                $(this).dialog("close");
            },
            "Sim": function() {
                $(document).trigger($name_option_sim, [$option_sim]);
                $(this).dialog("close");
            }
        }
    });
}

function message_sim_nao_campanha($title, $text, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao ,$width , $minHeight){
    $class_tratado = "message-alert message-"+$class;
    $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: $minHeight,
        width: $width,
        modal: true,
        buttons: {
            "Não": function () {
                $(document).trigger($name_option_nao, [$option_nao]);
                $(this).dialog("close");
            },
            "Sim": function() {
                $(document).trigger($name_option_sim, [$option_sim]);
                $(this).dialog("close");
            }
        }
    });
}

function createModal($id, $title, $body, $class){
    var $html = "\
        <div class=\"modal fade\" id=\""+$id+"\" tabindex=\"-1\" role=\"dialog\" aria-labelledby=\"ModalLabel\" aria-hidden=\"true\">\
            <div class=\"modal-dialog "+$class+"\" role=\"document\">\
                <div class=\"modal-content\">\
                    <div class=\"modal-header\">\
                        <h5 class=\"modal-title\" id=\"ModalLabel\">"+$title+"</h5>\
                        <button type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">\
                            <span aria-hidden=\"true\">&times;</span>\
                        </button>\
                    </div>\
                    <div class=\"modal-body\">\
                        "+$body+"\
                    </div>\
                </div>\
            </div>\
        </div>";
    $("body").append($html);
    $("#"+$id).modal({keyboard: false});
    if($class !== ""){
        $("#"+$id).find('.modal-dialog').height(($(window).height() - 60));   
    }else if($class === "modal-md"){
        $("#"+$id).find('.modal-dialog').height(($(window).height() - 60)); 
    }else{
        $("#"+$id).find('.modal-dialog').find(".modal-content").css("height", "auto");
    }
    $("#"+$id).on('hidden.bs.modal', function (e) {
        $("#"+$id).remove();
    });
}

function esconderPopoverTooltip(){
    $('[data-toggle="tooltip"]').tooltip('hide');
    $('[data-toggle="popover"]').popover('hide');
}

function message_option_sim_nao_cadastro($title, $text, $class, $name_option_sim, $option_sim, $name_option_nao, $option_nao, $name_option_cancelar, $option_cancelar){
    $class_tratado = "message-alert message-"+$class;
    $("<div></div>").html($text).dialog({
        title: $title,
        dialogClass: $class_tratado,
        minHeight: 200,
        width: 400,
        modal: true,
        buttons: {
            "Cadastro": function () {
                $(document).trigger($name_option_cancelar, [$option_cancelar]);
                $(this).dialog("close");
            },
            "Não": function () {
                $(document).trigger($name_option_nao, [$option_nao]);
                $(this).dialog("close");
            },
            "Sim": function() {
                $(document).trigger($name_option_sim, [$option_sim]);
                $(this).dialog("close");
            }
        }
    });
}