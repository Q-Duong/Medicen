function scheduleCancel(e) {
    var order_id = $('input[name="order_id"]').val();
    $(".loader-over").fadeIn();
    $.ajax({
        url: url_schedule_cancel,
        method: "POST",
        data: {
            order_id: order_id,
            _token: $('meta[name="csrf-token"]').attr("content")
        },
        success: function(data) {
            successMsg(data.success);
            $(".loader-over").fadeOut();
            window.setTimeout(function() {
                location.reload();
            }, 1000);
        }
    });
}