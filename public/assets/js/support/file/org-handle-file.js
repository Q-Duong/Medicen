FilePond.registerPlugin(FilePondPluginImagePreview);
FilePond.setOptions({
    server: {
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        process: url_file_process,
        revert: url_file_revert,
    },
});

const inputElement = document.querySelector('input[type="file"]');
const pond = FilePond.create(inputElement, {
    labelIdle: `Kéo và thả tập tin của bạn hoặc <span class="filepond--label-action">Trình duyệt</span>`,
    credits: false,
    onaddfilestart: () => {
        $(".button-submit").attr("disabled", true);
    },
    onprocessfile: () => {
        $(".button-submit").removeAttr("disabled");
    },
});

if (typeof files !== "undefined") {
    pond.setOptions({
        files,
    });
}

function deleteFileOrder(n, p, i) {
    if (confirm("Do you want to delete this file?")) {
        $(".loader-over").fadeIn();
        $(".button-submit").attr("disabled", true);
        $.ajax({
            url: url_file_delete_order,
            type: "DELETE",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            data: {
                name: n,
                path: p,
                id: i,
            },
            success: function (data) {
                if (data.html) {
                    $(".section-file").html(data.html);
                } else {
                    $(".section-file").html("").addClass("hidden");
                }
                $(".loader-over").fadeOut();
                $(".button-submit").removeAttr("disabled");
                successMsg(data.message);
            },
        });
    }
}

$(document).on("click", ".del-total-file", function () {
    console.log("de");
    // var data = getValues();
    // data.push(
    //     {
    //         name: "id",
    //         value: $(".event-info").find(".event-id").text(),
    //     },
    //     {
    //         name: "_token",
    //         value: $('meta[name="csrf-token"]').attr("content"),
    //     }
    // );
    // $(".loader-over").fadeIn();
    // $.ajax({
    //     url: url_update_details,
    //     method: "POST",
    //     data: data,
    //     success: function () {
    //         $(".event-modal").fadeOut();
    //         $(".loader-over").fadeOut();
    //         successMsg("Bạn đã cập nhật số Cas chụp thành công");
    //         location.reload();
    //     },
    // });
});

// function deleteFileTotal(n, p, i) {
//     console.log("de");
    // if (confirm("Do you want to delete this file?")) {
    //     $(".loader-over").fadeIn();
    //     $(".button-submit").attr("disabled", true);
    //     $.ajax({
    //         url: url_file_delete_total,
    //         type: "DELETE",
    //         headers: {
    //             "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
    //         },
    //         data: {
    //             name: n,
    //             path: p,
    //             id: i,
    //         },
    //         success: function (data) {
    //             $(".total-file").removeClass("hidden");
    //             $(".event-total-file").html('');
    //             $(".loader-over").fadeOut();
    //             $(".button-submit").removeAttr("disabled");
    //             successMsg(data.message);
    //         },
    //     });
    // }
// }
