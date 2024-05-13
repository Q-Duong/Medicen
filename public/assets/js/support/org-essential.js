function errorsMsgInput($class, $message) {
    $("." + $class).parent().addClass("is-error");
    $("." + $class).removeClass("hidden");
    $("." + $class + "_message").text($message[0]);
}
function errorMsgInput($class, $message) {
    $("." + $class).parent().addClass("is-error");
    $("." + $class).removeClass("hidden");
    $("." + $class + "_message").text($message);
}
$(".form-textbox").on("keyup", function () {
    $(this).next().addClass("hidden");
    $(this).parent().removeClass("is-error");
});
$(".file").on("change", function () {
    $(this).next().next().addClass("hidden");
    $(this).parent().removeClass("is-error");
});
$(".select-textbox").on("change", function () {
    $(this).next().addClass("hidden");
    $(this).parent().removeClass("is-error");
});
$(".button-submit").click(function () {
    $(".loader-over").fadeIn();
});