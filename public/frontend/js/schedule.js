/*------------------
       Call Shedule
    --------------------*/
var currentTime = JSON.parse(localStorage.getItem("currentTime")) || [];
var currentLocation = window.location.pathname;

/*------------------
        Popup Notification
    --------------------*/
function successMsg(msg) {
    $(".notifications-popup-success").addClass("active");
    $(".notifications-icon").html(
        '<i class="fas fa-solid fa-check notifications-success"></i>'
    );
    $(".message-text").text(msg);
    setTimeout(function () {
        $(".notifications-popup-success").removeClass("active");
    }, 3000);
    $(".notifications-close").click(function () {
        $(".notifications-popup-success").removeClass("active");
    });
}

function errorMsg(msg) {
    $(".notifications-popup-error").addClass("active");
    $(".notifications-icon").html(
        '<i class="fas fa-times notifications-error"></i>'
    );
    $(".message-text").text(msg);
    setTimeout(function () {
        $(".notifications-popup-error").removeClass("active");
    }, 3000);
    $(".notifications-close").click(function () {
        $(".notifications-popup-error").removeClass("active");
    });
}

function getValues() {
    return [
        {
            name: "order_quantity_details",
            value: $(".order_quantity_details").val(),
        },
        {
            name: "accountant_doctor_read",
            value: $(".accountant-doctor-read").val(),
        },
        {
            name: "accountant_35X43",
            value: $(".accountant-35X43").val(),
        },
        {
            name: "accountant_polime",
            value: $(".accountant-polime").val(),
        },
        {
            name: "accountant_8X10",
            value: $(".accountant-8X10").val(),
        },
        {
            name: "accountant_10X12",
            value: $(".accountant-10X12").val(),
        },
        {
            name: "accountant_note",
            value: $(".accountant-note").val(),
        },
        {
            name: "ord_delivery_date",
            value: $(".ord_delivery_date").val(),
        }
    ];
}

function getValuesClone() {
    return [
        {
            name: "ord_cty_name",
            value: $(".ord-cty-name").val(),
        },
        {
            name: "customer_address",
            value: $(".customer-address").val(),
        },
        {
            name: "customer_note",
            value: $(".customer-note").val(),
        },
        {
            name: "customer_name",
            value: $(".customer-name").val(),
        },
        {
            name: "customer_phone",
            value: $(".customer-phone").val(),
        },
        {
            name: "ord_time",
            value: $(".ord-time").val(),
        },
        {
            name: "ord_doctor_read",
            value: $('input[name="ord_doctor_read"]:checked').val(),
        },
        {
            name: "ord_film",
            value: $('input[name="ord_film"]:checked').val(),
        },
        {
            name: "ord_form",
            value: $('input[name="ord_form"]:checked').val(),
        },
        {
            name: "ord_print",
            value: $('input[name="ord_print"]:checked').val(),
        },
        {
            name: "ord_form_print",
            value: $('input[name="ord_form_print"]:checked').val(),
        },
        {
            name: "ord_print_result",
            value: $('input[name="ord_print_result"]:checked').val(),
        },
        {
            name: "ord_film_sheet",
            value: $('input[name="ord_film_sheet"]:checked').val(),
        },
        {
            name: "order_warning",
            value: $('input[name="order_warning"]:checked').val(),
        },
        {
            name: "ord_note",
            value: $(".ord-note").val(),
        },
        {
            name: "ord_deadline",
            value: $(".ord-deadline").val(),
        },
    ];
}

$(document).ready(function () {
    setTimeout(function () {
        $(".notifications-popup-success").removeClass("active");
    }, 3000);
    setTimeout(function () {
        $(".notifications-popup-error").removeClass("active");
    }, 3000);
});

function pushCurrentTime(month, year) {
    var newItem = {
        month: month,
        year: year,
    };
    localStorage.setItem("currentTime", JSON.stringify(newItem));
}

if (currentLocation == "/show-schedule-details") {
    $.ajax({
        url: url_get_schedule_details,
        method: "POST",
        data: {
            _token: _token,
            currentTime: currentTime,
        },
        success: function (data) {
            $(".schedule-render").html(data.html);
            shedule(data.day);
        },
    });
} else {
    shedule(day);
}

/*------------------
       Function Shedule
    --------------------*/
function shedule(day) {
    jQuery(document).ready(function ($) {
        var transitionEnd =
            "webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend";
        var transitionsSupported = $(".csstransitions").length > 0;
        //if browser does not support transitions - use a different event to trigger them
        if (!transitionsSupported) transitionEnd = "noTransition";

        //should add a loding while the events are organized

        function SchedulePlan(element) {
            this.element = element;
            this.timeline = this.element.find(".timeline");
            this.timelineItems = this.timeline.find("li");
            this.timelineItemsNumber = this.timelineItems.length;
            this.timelineStart = getScheduleTimestamp(
                this.timelineItems.eq(0).text()
            );
            //need to store delta (in our case half hour) timestamp
            this.timelineUnitDuration =
                getScheduleTimestamp(this.timelineItems.eq(1).text()) -
                getScheduleTimestamp(this.timelineItems.eq(0).text());
            this.eventsWrapper = this.element.find(".events");
            this.eventsGroup = this.eventsWrapper.find(".events-group");
            this.singleEvents = this.eventsGroup.find(".single-event");
            this.eventSlotHeight = this.eventsGroup
                .eq(0)
                .children(".top-info")
                .outerHeight();
            this.modal = this.element.find(".event-modal");
            this.modalHeader = this.modal.find(".header");
            this.modalHeaderBg = this.modal.find(".header-bg");
            this.modalBody = this.modal.find(".body");
            this.modalBodyBg = this.modal.find(".body-bg");
            this.modalMaxWidth = 900;
            this.modalMaxHeight = 640;

            this.animating = false;

            this.initSchedule();
        }

        SchedulePlan.prototype.initSchedule = function () {
            this.scheduleReset();
            this.initEvents();
        };

        SchedulePlan.prototype.scheduleReset = function () {
            var mq = this.mq();
            if (mq == "desktop") {
                //in this case you are on a desktop version (first load or resize from mobile)
                this.eventSlotHeight = this.eventsGroup
                    .eq(0)
                    .children(".top-info")
                    .outerHeight();
                this.element.addClass("js-full");
                this.placeEvents();
                this.element.hasClass("modal-is-open") &&
                    this.checkEventModal();
                if (day == 30) {
                    this.eventsGroup.children("ul").css({
                        height: "3000px",
                    });
                } else {
                    this.eventsGroup.children("ul").css({
                        height: "3100px",
                    });
                }
            } else if (mq == "mobile") {
                //in this case you are on a mobile version (first load or resize from desktop)
                this.element.removeClass("js-full loading");
                this.eventsGroup
                    .children("ul")
                    .add(this.singleEvents)
                    .removeAttr("style");
                this.eventsWrapper.children(".grid-line").remove();
                this.element.hasClass("modal-is-open") &&
                    this.checkEventModal();
            } else if (
                mq == "desktop" &&
                this.element.hasClass("modal-is-open")
            ) {
                //on a mobile version with modal open - need to resize/move modal window
                this.checkEventModal("desktop");
                this.element.removeClass("loading");
            } else {
                this.element.removeClass("loading");
            }
        };

        SchedulePlan.prototype.initEvents = function () {
            var self = this;

            this.singleEvents.each(function () {
                //create the .event-date element for each event
                var start = getDate($(this).data("start")),
                    end = getDate($(this).data("end"));

                var durationLabel =
                    '<span class="event-date">Ngày chụp: ' +
                    start +
                    " - " +
                    end +
                    "</span>";
                $(this).children("a").prepend($(durationLabel));

                //detect click on the event and open the modal
                $(this).on("click", "a", function (event) {
                    event.preventDefault();
                    if (!self.animating) self.openModal($(this));
                });
            });

            //close modal window
            this.modal.on("click", ".close", function (event) {
                event.preventDefault();
                if (!self.animating)
                    self.closeModal(self.eventsGroup.find(".selected-event"));
            });
            this.element.on("click", ".cover-layer", function (event) {
                if (!self.animating && self.element.hasClass("modal-is-open"))
                    self.closeModal(self.eventsGroup.find(".selected-event"));
            });
        };

        SchedulePlan.prototype.placeEvents = function () {
            var self = this;
            var duration = 0;
            this.singleEvents.each(function () {
                //place each event in the grid -> need to set top position and height
                var start = getScheduleTimestamp($(this).attr("data-start")),
                    end = getScheduleTimestamp($(this).attr("data-end")),
                    child = $(this).attr("data-child");
                if (child == 1) {
                    $(this).css({
                        left: 0 + "px",
                    });
                } else if (child == 2) {
                    $(this).css({
                        left: 23 + "px",
                    });
                } else {
                    $(this).css({
                        right: 0 + "px",
                    });
                }
                if (start == end) {
                    duration = 60;
                } else {
                    duration = end - start + 60;
                }
                var eventTop =
                        (self.eventSlotHeight * (start - self.timelineStart)) /
                        self.timelineUnitDuration,
                    eventHeight =
                        (self.eventSlotHeight * duration) /
                        self.timelineUnitDuration;

                $(this).css({
                    top: eventTop - 1 + "px",
                    height: eventHeight + 1 + "px",
                });
            });
            this.element.removeClass("loading");
        };

        SchedulePlan.prototype.openModal = function (event) {
            var self = this;
            var mq = self.mq();
            const now = new Date();
            const start_date = new Date(event.find(".event-start-day").html());
            this.animating = true;

            //update event name and time
            this.modalHeader
                .find(".event-name-id")
                .text(event.find(".event-name-id").text());
            this.modalHeader
                .find(".event-name")
                .text(event.find(".event-name").text());
            this.modalHeader
                .find(".event-name-unit")
                .text(event.find(".event-name-unit").text());
            this.modalHeader
                .find(".event-date")
                .text(event.find(".event-date").text());
            this.modal.attr("data-event", event.parent().attr("data-event"));

            //update event content
            this.modalBody
                .find(".event-info")
                .load(
                    event.parent().attr("data-content") +
                        ".html .event-info > *",
                    function (data) {
                        //once the event content has been loaded
                        self.element.addClass("content-loaded");
                    }
                );
            this.modalBody
                .find(".event-car-id")
                .html(event.find(".event-car-id").html());
            this.modalBody
                .find(".event-id")
                .html(event.find(".event-id").html());
            this.modalBody
                .find(".event-unit")
                .html(event.find(".event-unit").html());
            this.modalBody
                .find(".event-ord-unit")
                .html(event.find(".event-unit").html());
            this.modalBody
                .find(".event-cty-name")
                .html(event.find(".event-cty-name").html());
            this.modalBody
                .find(".event-select")
                .html(event.find(".event-select").html());
            this.modalBody
                .find(".event-address")
                .html(event.find(".event-address").html());
            if (event.find(".event-note").html() == "") {
                this.modalBody.find(".event-note").addClass("hidden");
            } else {
                this.modalBody.find(".event-note").removeClass("hidden");
                this.modalBody
                    .find(".event-note-content")
                    .html(event.find(".event-note").html());
            }
            this.modalBody.find(".event-list-file").html(function () {
                var href = event.find(".event-list-file p").attr("href");
                var name_file = event.find(".event-list-file").text();
                return (
                    '<a href="' +
                    href +
                    '" target="_blank" class="dowload_file">' +
                    name_file +
                    ' <i class="fa fa-download"></i></a>'
                );
            });

            this.modalBody
                .find(".event-info-contact")
                .html(event.find(".event-info-contact").html());
            this.modalBody
                .find(".event-time")
                .html(event.find(".event-time").html());
            this.modalBody
                .find(".event-quantity")
                .html(event.find(".event-quantity").html());
            if (event.find(".event-quantity-draft").text() != 0 || event.find(".event-note-ktv").text() != '') {
                this.modalBody
                    .find(".order_quantity_draft")
                    .addClass("hidden");
                this.modalBody.find(".event-draft").removeClass("hidden");
                this.modalBody
                    .find(".event-draft")
                    .html(event.find(".event-quantity-draft").html() + ' Cas');
                this.modalBody
                    .find(".order_note_ktv")
                    .addClass("hidden");
                this.modalBody.find(".event-noteKtv").removeClass("hidden");
                this.modalBody
                    .find(".event-noteKtv")
                    .html(event.find(".event-note-ktv").html());
                this.modalBody
                    .find(".send_quantity_draft")
                    .addClass("hidden");
            } else {
                this.modalBody
                    .find(".order_quantity_draft")
                    .removeClass("hidden");
                this.modalBody
                    .find(".send_quantity_draft")
                    .removeClass("hidden");
                this.modalBody
                    .find(".order_note_ktv")
                    .removeClass("hidden");
                this.modalBody.find(".event-noteKtv").addClass("hidden");   
                this.modalBody.find(".event-draft").addClass("hidden");
            }
            this.modalBody
                .find(".event-doctor-read")
                .html(event.find(".event-doctor-read").html());
            this.modalBody
                .find(".event-film")
                .html(event.find(".event-film").html());
            this.modalBody
                .find(".event-form")
                .html(event.find(".event-form").html());
            this.modalBody
                .find(".event-print")
                .html(event.find(".event-print").html());
            this.modalBody
                .find(".event-form-print")
                .html(event.find(".event-form-print").html());
            this.modalBody
                .find(".event-print-result")
                .html(event.find(".event-print-result").html());
            this.modalBody
                .find(".event-film-sheet")
                .html(event.find(".event-film-sheet").html());
            this.modalBody
                .find(".event-order-note")
                .html(event.find(".event-order-note").html());
            this.modalBody
                .find(".event-deadline")
                .html(event.find(".event-deadline").html());
            this.modalBody
                .find(".event-deliver-results")
                .html(event.find(".event-deliver-results").html());
            this.modalBody
                .find(".event-email")
                .html(event.find(".event-email").html());
            this.modalBody
                .find(".event-quantity")
                .html(event.find(".event-quantity").html());
            this.modalBody
                .find(".event-accountant-note-clone")
                .html(event.find(".event-accountant-note").text());
            this.modalBody
                .find(".event-delivery-date-clone")
                .html(event.find(".event-delivery-date").text());
            this.modalBody
                .find(".event-ord-warning")
                .html(event.find(".event-warning").html());
            this.modalBody
                .find(".event-35X43-clone")
                .html(event.find(".event-35X43").text());
            this.modalBody
                .find(".event-polime-clone")
                .html(event.find(".event-polime").text());
            this.modalBody
                .find(".event-8X10-clone")
                .html(event.find(".event-8X10").text());
            this.modalBody
                .find(".event-10X12-clone")
                .html(event.find(".event-10X12").text());
            this.modalBody
                .find(".event-accountant-doctor-read-clone")
                .html(event.find(".event-accountant-doctor-read").text());

            if (start_date < now) {
                this.modalBody.find(".section-form-sale").html("");
                this.modalBody.find(".row").addClass("hidden");
                this.modalBody.find(".ord-cty-name").addClass("hidden");
                this.modalBody
                    .find(".event-ord-cty-name")
                    .html(event.find(".event-cty-name").html());
                this.modalBody.find(".customer-address").addClass("hidden");
                this.modalBody
                    .find(".event-customer-address")
                    .html(event.find(".event-address").html());
                this.modalBody.find(".customer-note").addClass("hidden");
                this.modalBody
                    .find(".event-customer-note")
                    .html(event.find(".event-note").html());
                this.modalBody.find(".customer-name").addClass("hidden");
                this.modalBody
                    .find(".event-customer-name")
                    .html(event.find(".event-customer-name").html());
                this.modalBody.find(".customer-phone").addClass("hidden");
                this.modalBody
                    .find(".event-customer-phone")
                    .html(event.find(".event-customer-phone").html());
                this.modalBody.find(".ord-time").addClass("hidden");
                this.modalBody
                    .find(".event-ord-time")
                    .html(event.find(".event-time").html());
                this.modalBody
                    .find(".event-ord-doctor-read")
                    .html(event.find(".event-doctor-read").html());
                this.modalBody
                    .find(".event-ord-film")
                    .html(event.find(".event-film").html());
                this.modalBody
                    .find(".event-ord-form")
                    .html(event.find(".event-form").html());
                this.modalBody
                    .find(".event-ord-print")
                    .html(event.find(".event-print").html());
                this.modalBody
                    .find(".event-ord-form-print")
                    .html(event.find(".event-form-print").html());
                this.modalBody
                    .find(".event-ord-print-result")
                    .html(event.find(".event-print-result").html());
                this.modalBody
                    .find(".event-ord-film-sheet")
                    .html(event.find(".event-film-sheet").html());
                this.modalBody
                    .find(".event-order-warning")
                    .html(event.find(".event-warning").html());
                this.modalBody.find(".ord-note").addClass("hidden");
                this.modalBody.find(".ord-deadline").addClass("hidden");
                this.modalBody
                    .find(".event-ord-deadline")
                    .html(event.find(".event-deadline").html());
                this.modalBody.find(".ord-deliver-results").addClass("hidden");
                this.modalBody
                    .find(".event-deliver-results")
                    .html(event.find(".event-deliver-results").html());
                this.modalBody
                    .find(".event-email")
                    .html(event.find(".event-email").html());
            } else {
                this.modalBody
                    .find(".section-form-sale")
                    .html(
                        '<button type="button" name="update_order"  class="primary-btn-submit update-order">Cập nhật</button>'
                    );
                this.modalBody.find(".row").removeClass("hidden");
                this.modalBody.find(".ord-cty-name").removeClass("hidden");
                this.modalBody.find(".event-ord-cty-name").html("");
                this.modalBody.find(".customer-address").removeClass("hidden");
                this.modalBody.find(".event-customer-address").html("");
                this.modalBody.find(".customer-note").removeClass("hidden");
                this.modalBody.find(".event-customer-note").html("");
                this.modalBody.find(".customer-name").removeClass("hidden");
                this.modalBody.find(".event-customer-name").html("");
                this.modalBody.find(".customer-phone").removeClass("hidden");
                this.modalBody.find(".event-customer-phone").html("");
                this.modalBody.find(".ord-time").removeClass("hidden");
                this.modalBody.find(".event-ord-time").html("");
                this.modalBody.find(".event-ord-doctor-read").html("");
                this.modalBody.find(".event-ord-film").html("");
                this.modalBody.find(".event-ord-form").html("");
                this.modalBody.find(".event-ord-print").html("");
                this.modalBody.find(".event-ord-form-print").html("");
                this.modalBody.find(".event-ord-print-result").html("");
                this.modalBody.find(".event-ord-film-sheet").html("");
                this.modalBody.find(".event-order-warning").html("");
                this.modalBody.find(".ord-note").removeClass("hidden");
                this.modalBody.find(".ord-deadline").removeClass("hidden");
                this.modalBody.find(".event-ord-deadline").html("");
            }
            if (event.find(".event-status").text() == 3) {
                this.modalBody
                    .find(".order_quantity_details")
                    .addClass("hidden");
                this.modalBody
                    .find(".send_quantity_details")
                    .addClass("hidden");
                this.modalBody
                    .find(".event-quantity-details")
                    .removeClass("hidden");
                this.modalBody
                    .find(".event-quantity-details")
                    .html(event.find(".event-quantity").html());
                this.modalBody.find(".section-form-sale").html("");
                this.modalBody.find(".row-datails").addClass("hidden");
                this.modalBody
                    .find(".event-ord-form")
                    .html(event.find(".event-form").html());
                this.modalBody
                    .find(".event-35X43")
                    .html(event.find(".event-35X43").text());
                this.modalBody.find(".accountant-35X43").addClass("hidden");
                this.modalBody
                    .find(".event-polime")
                    .html(event.find(".event-polime").text());
                this.modalBody.find(".accountant-polime").addClass("hidden");
                this.modalBody
                    .find(".event-8X10")
                    .html(event.find(".event-8X10").text());
                this.modalBody.find(".accountant-8X10").addClass("hidden");
                this.modalBody
                    .find(".event-10X12")
                    .html(event.find(".event-10X12").text());
                this.modalBody.find(".accountant-10X12").addClass("hidden");
                this.modalBody
                    .find(".event-accountant-note")
                    .html(event.find(".event-accountant-note").text());
                this.modalBody.find(".accountant-note").addClass("hidden");
                this.modalBody
                    .find(".event-delivery-date")
                    .html(event.find(".event-delivery-date").text());
                this.modalBody.find(".ord_delivery_date").addClass("hidden");
                this.modalBody
                    .find(".event-accountant-doctor-read")
                    .html(event.find(".event-accountant-doctor-read").text());
                this.modalBody
                    .find(".accountant-doctor-read")
                    .addClass("hidden");
            } else {
                this.modalBody
                    .find(".order_quantity_details")
                    .removeClass("hidden");
                this.modalBody
                    .find(".order_quantity_details")
                    .val(event.find(".event-quantity").text());
                this.modalBody
                    .find(".send_quantity_details")
                    .removeClass("hidden");
                this.modalBody
                    .find(".event-quantity-details")
                    .addClass("hidden");
                this.modalBody.find(".row-datails").removeClass("hidden");
                // this.modalBody.find('.event-ord-form').html('');
                this.modalBody.find(".accountant-35X43").removeClass("hidden");
                this.modalBody.find(".event-35X43").html("");
                this.modalBody
                    .find(".accountant-35X43")
                    .val(event.find(".event-35X43").text());
                this.modalBody.find(".accountant-polime").removeClass("hidden");
                this.modalBody.find(".event-polime").html("");
                this.modalBody
                    .find(".accountant-polime")
                    .val(event.find(".event-polime").text());
                this.modalBody.find(".accountant-8X10").removeClass("hidden");
                this.modalBody.find(".event-8X10").html("");
                this.modalBody
                    .find(".accountant-8X10")
                    .val(event.find(".event-8X10").text());
                this.modalBody.find(".accountant-10X12").removeClass("hidden");
                this.modalBody.find(".event-10X12").html("");
                this.modalBody
                    .find(".accountant-10X12")
                    .val(event.find(".event-10X12").text());
                this.modalBody.find(".accountant-note").removeClass("hidden");
                this.modalBody.find(".event-accountant-note").html("");
                this.modalBody
                    .find(".accountant-note")
                    .val(event.find(".event-accountant-note").text());
                this.modalBody.find(".ord_delivery_date").removeClass("hidden");
                this.modalBody.find(".event-delivery-date").html("");
                this.modalBody
                    .find(".ord_delivery_date")
                    .val(event.find(".event-delivery-date").text());

                this.modalBody.find(".event-accountant-doctor-read").html("");
                this.modalBody
                    .find(".accountant-doctor-read")
                    .removeClass("hidden");
                if (
                    event.find(".event-accountant-doctor-read").html() == "Nhân"
                ) {
                    this.modalBody.find(".doctor-N").prop("selected", true);
                    this.modalBody.find(".doctor-T").prop("selected", false);
                    this.modalBody.find(".doctor-G").prop("selected", false);
                    this.modalBody
                        .find(".doctor-empty")
                        .prop("selected", false);
                } else if (
                    event.find(".event-accountant-doctor-read").html() ==
                    "Trung"
                ) {
                    this.modalBody.find(".doctor-T").prop("selected", true);
                    this.modalBody.find(".doctor-N").prop("selected", false);
                    this.modalBody.find(".doctor-G").prop("selected", false);
                    this.modalBody
                        .find(".doctor-empty")
                        .prop("selected", false);
                } else if (
                    event.find(".event-accountant-doctor-read").html() ==
                    "Giang"
                ) {
                    this.modalBody.find(".doctor-G").prop("selected", true);
                    this.modalBody.find(".doctor-T").prop("selected", false);
                    this.modalBody.find(".doctor-N").prop("selected", false);
                    this.modalBody
                        .find(".doctor-empty")
                        .prop("selected", false);
                } else {
                    this.modalBody.find(".doctor-empty").prop("selected", true);
                    this.modalBody.find(".doctor-G").prop("selected", false);
                    this.modalBody.find(".doctor-N").prop("selected", false);
                    this.modalBody.find(".doctor-T").prop("selected", false);
                }
            }

            if (event.find(".event-status").text() == 2) {
                this.modalBody.find(".section-form-sale").html("");
            }
            if (event.find(".event-status").html() == 1) {
                this.modalBody.find(".event-status").html("Đang xử lý");
                this.modalBody.find(".event-status").removeClass("updated");
                this.modalBody.find(".event-status").removeClass("processed");
                this.modalBody.find(".event-status").removeClass("update-acc");
                this.modalBody.find(".event-status").addClass("processing");
            } else if (event.find(".event-status").html() == 2) {
                this.modalBody
                    .find(".event-status")
                    .html("Đã cập nhật số Cas thực tế");
                this.modalBody.find(".event-status").removeClass("processing");
                this.modalBody.find(".event-status").removeClass("processed");
                this.modalBody.find(".event-status").removeClass("update-acc");
                this.modalBody.find(".event-status").addClass("updated");
            } else if (event.find(".event-status").html() == 4) {
                this.modalBody
                    .find(".event-status")
                    .html("Đã cập nhật doanh thu");
                this.modalBody.find(".event-status").addClass("update-acc");
                this.modalBody.find(".event-status").removeClass("processing");
                this.modalBody.find(".event-status").removeClass("processed");
                this.modalBody.find(".event-status").removeClass("updated");
            } else {
                this.modalBody.find(".event-status").html("Đã xử lý");
                this.modalBody.find(".event-status").removeClass("updated");
                this.modalBody.find(".event-status").removeClass("processing");
                this.modalBody.find(".event-status").removeClass("update-acc");
                this.modalBody.find(".event-status").addClass("processed");
            }

            this.modalBody
                .find(".ord-unit")
                .val(
                    event.find(".event-name-unit").text().replace("Đơn vị:", "")
                );
            this.modalBody
                .find(".code-unit")
                .val(event.find(".event-code-unit").text());
            this.modalBody
                .find(".ord-cty-name")
                .val(event.find(".event-cty-name").text());
            this.modalBody
                .find(".customer-address")
                .val(event.find(".event-address").text());
            this.modalBody
                .find(".customer-note")
                .val(event.find(".event-note").text());
            this.modalBody
                .find(".customer-name")
                .val(event.find(".event-customer-name").text());
            this.modalBody
                .find(".customer-phone")
                .val(event.find(".event-customer-phone").text());
            this.modalBody
                .find(".ord-time")
                .val(event.find(".event-time").text());
            this.modalBody
                .find(".ord-note")
                .val(event.find(".event-order-note").text());
            this.modalBody
                .find(".ord-deadline")
                .val(event.find(".event-deadline").text());
            this.modalBody
                .find(".ord-deliver-results")
                .val(event.find(".event-deliver-results").text());
            if (event.find(".event-doctor-read").html() == "") {
                this.modalBody.find("#id1").prop("checked", true);
                this.modalBody.find("#id2").prop("checked", false);
                this.modalBody.find("#id3").prop("checked", false);
            } else if (event.find(".event-doctor-read").html() == "Có") {
                this.modalBody.find("#id1").prop("checked", false);
                this.modalBody.find("#id2").prop("checked", true);
                this.modalBody.find("#id3").prop("checked", false);
            } else {
                this.modalBody.find("#id1").prop("checked", false);
                this.modalBody.find("#id2").prop("checked", false);
                this.modalBody.find("#id3").prop("checked", true);
            }

            if (event.find(".event-film").html() == "") {
                this.modalBody.find("#id4").prop("checked", true);
                this.modalBody.find("#id5").prop("checked", false);
                this.modalBody.find("#id6").prop("checked", false);
                this.modalBody.find("#id7").prop("checked", false);
            } else if (event.find(".event-film").html() == "Bình thường") {
                this.modalBody.find("#id4").prop("checked", false);
                this.modalBody.find("#id5").prop("checked", true);
                this.modalBody.find("#id6").prop("checked", false);
                this.modalBody.find("#id7").prop("checked", false);
            } else if (event.find(".event-film").html() == "Bất thường") {
                this.modalBody.find("#id4").prop("checked", false);
                this.modalBody.find("#id5").prop("checked", false);
                this.modalBody.find("#id6").prop("checked", true);
                this.modalBody.find("#id7").prop("checked", false);
            } else {
                this.modalBody.find("#id4").prop("checked", false);
                this.modalBody.find("#id5").prop("checked", false);
                this.modalBody.find("#id6").prop("checked", false);
                this.modalBody.find("#id7").prop("checked", true);
            }

            if (event.find(".event-form").html() == "ko in") {
                this.modalBody.find("#id8").prop("checked", true);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "IN4") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", true);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "IN12") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", true);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "IN16") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", true);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "IN8X10") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", true);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "IN10X12") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", true);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", false);
            } else if (event.find(".event-form").html() == "PhimLon") {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", true);
                this.modalBody.find("#id32").prop("checked", false);
            } else {
                this.modalBody.find("#id8").prop("checked", false);
                this.modalBody.find("#id9").prop("checked", false);
                this.modalBody.find("#id10").prop("checked", false);
                this.modalBody.find("#id11").prop("checked", false);
                this.modalBody.find("#id12").prop("checked", false);
                this.modalBody.find("#id13").prop("checked", false);
                this.modalBody.find("#id14").prop("checked", false);
                this.modalBody.find("#id32").prop("checked", true);
            }

            if (event.find(".event-print").html() == "") {
                this.modalBody.find("#id15").prop("checked", true);
                this.modalBody.find("#id16").prop("checked", false);
                this.modalBody.find("#id17").prop("checked", false);
                this.modalBody.find("#id18").prop("checked", false);
            } else if (event.find(".event-print").html() == "Bình thường") {
                this.modalBody.find("#id15").prop("checked", false);
                this.modalBody.find("#id16").prop("checked", true);
                this.modalBody.find("#id17").prop("checked", false);
                this.modalBody.find("#id18").prop("checked", false);
            } else if (event.find(".event-print").html() == "Bất thường") {
                this.modalBody.find("#id15").prop("checked", false);
                this.modalBody.find("#id16").prop("checked", false);
                this.modalBody.find("#id17").prop("checked", true);
                this.modalBody.find("#id18").prop("checked", false);
            } else {
                this.modalBody.find("#id15").prop("checked", false);
                this.modalBody.find("#id16").prop("checked", false);
                this.modalBody.find("#id17").prop("checked", false);
                this.modalBody.find("#id18").prop("checked", true);
            }

            if (event.find(".event-form-print").html() == "") {
                this.modalBody.find("#id19").prop("checked", true);
                this.modalBody.find("#id20").prop("checked", false);
                this.modalBody.find("#id21").prop("checked", false);
            } else if (event.find(".event-form-print").html() == "A4") {
                this.modalBody.find("#id19").prop("checked", false);
                this.modalBody.find("#id20").prop("checked", true);
                this.modalBody.find("#id21").prop("checked", false);
            } else {
                this.modalBody.find("#id19").prop("checked", false);
                this.modalBody.find("#id20").prop("checked", false);
                this.modalBody.find("#id21").prop("checked", true);
            }

            if (event.find(".event-print-result").html() == "") {
                this.modalBody.find("#id22").prop("checked", true);
                this.modalBody.find("#id23").prop("checked", false);
                this.modalBody.find("#id24").prop("checked", false);
            } else if (event.find(".event-print-result").html() == "Có") {
                this.modalBody.find("#id22").prop("checked", false);
                this.modalBody.find("#id23").prop("checked", true);
                this.modalBody.find("#id24").prop("checked", false);
            } else {
                this.modalBody.find("#id22").prop("checked", false);
                this.modalBody.find("#id23").prop("checked", false);
                this.modalBody.find("#id24").prop("checked", true);
            }

            if (event.find(".event-film-sheet").html() == "") {
                this.modalBody.find("#id25").prop("checked", true);
                this.modalBody.find("#id26").prop("checked", false);
                this.modalBody.find("#id27").prop("checked", false);
                this.modalBody.find("#id28").prop("checked", false);
            } else if (
                event.find(".event-film-sheet").html() == "Bấm flim vào phiếu"
            ) {
                this.modalBody.find("#id25").prop("checked", false);
                this.modalBody.find("#id26").prop("checked", true);
                this.modalBody.find("#id27").prop("checked", false);
                this.modalBody.find("#id28").prop("checked", false);
            } else if (
                event.find(".event-film-sheet").html() ==
                "Bỏ flim và phiếu vào bao thư"
            ) {
                this.modalBody.find("#id25").prop("checked", false);
                this.modalBody.find("#id26").prop("checked", false);
                this.modalBody.find("#id27").prop("checked", true);
                this.modalBody.find("#id28").prop("checked", false);
            } else {
                this.modalBody.find("#id25").prop("checked", false);
                this.modalBody.find("#id26").prop("checked", false);
                this.modalBody.find("#id27").prop("checked", false);
                this.modalBody.find("#id28").prop("checked", true);
            }

            if (event.find(".event-warning").html() == "Có") {
                this.modalBody.find("#id29").prop("checked", false);
                this.modalBody.find("#id30").prop("checked", true);
            } else {
                this.modalBody.find("#id29").prop("checked", true);
                this.modalBody.find("#id30").prop("checked", false);
            }

            this.element.addClass("modal-is-open");

            setTimeout(function () {
                //fixes a flash when an event is selected - desktop version only
                event.parent("li").addClass("selected-event");
            }, 10);

            if (mq == "mobile") {
                self.modal.one(transitionEnd, function () {
                    self.modal.off(transitionEnd);
                    self.animating = false;
                });
            } else {
                var eventTop = event.offset().top - $(window).scrollTop(),
                    eventLeft = event.offset().left,
                    eventHeight = event.innerHeight(),
                    eventWidth = event.innerWidth();

                var windowWidth = $(window).width(),
                    windowHeight = $(window).height();

                var modalWidth =
                        windowWidth * 0.8 > self.modalMaxWidth
                            ? self.modalMaxWidth
                            : windowWidth * 0.8,
                    modalHeight =
                        windowHeight * 0.8 > self.modalMaxHeight
                            ? self.modalMaxHeight
                            : windowHeight * 0.8;

                var modalTranslateX = parseInt(
                        (windowWidth - modalWidth) / 2 - eventLeft
                    ),
                    modalTranslateY = parseInt(
                        (windowHeight - modalHeight) / 2 - eventTop
                    );

                var HeaderBgScaleY = modalHeight / eventHeight,
                    BodyBgScaleX = modalWidth - eventWidth;

                //change modal height/width and translate it
                self.modal.css({
                    top: eventTop + "px",
                    left: eventLeft + "px",
                    height: modalHeight + "px",
                    width: modalWidth + "px",
                });
                transformElement(
                    self.modal,
                    "translateY(" +
                        modalTranslateY +
                        "px) translateX(" +
                        modalTranslateX +
                        "px)"
                );

                //set modalHeader width
                self.modalHeader.css({
                    width: eventWidth + "px",
                });
                //set modalBody left margin
                self.modalBody.css({
                    marginLeft: eventWidth + "px",
                });

                //change modalBodyBg height/width ans scale it
                // self.modalBodyBg.css({
                //     height: eventHeight + 'px',
                //     width: '1px',
                // });
                // transformElement(self.modalBodyBg, 'scaleY(' + HeaderBgScaleY + ') scaleX(' +
                //     BodyBgScaleX + ')');

                //change modal modalHeaderBg height/width and scale it
                self.modalHeaderBg.css({
                    // height: eventHeight + 'px',
                    width: eventWidth + "px",
                });
                // transformElement(self.modalHeaderBg, 'scaleY(' + HeaderBgScaleY + ')');

                self.modalHeaderBg.one(transitionEnd, function () {
                    //wait for the  end of the modalHeaderBg transformation and show the modal content
                    self.modalHeaderBg.off(transitionEnd);
                    self.animating = false;
                    self.element.addClass("animation-completed");
                });
            }

            //if browser do not support transitions -> no need to wait for the end of it
            if (!transitionsSupported)
                self.modal.add(self.modalHeaderBg).trigger(transitionEnd);
        };

        SchedulePlan.prototype.closeModal = function (event) {
            var self = this;
            var mq = self.mq();

            this.animating = true;

            if (mq == "mobile") {
                this.element.removeClass("modal-is-open");
                this.modal.one(transitionEnd, function () {
                    self.modal.off(transitionEnd);
                    self.animating = false;
                    self.element.removeClass("content-loaded");
                    event.removeClass("selected-event");
                });
            } else {
                var eventTop = event.offset().top - $(window).scrollTop(),
                    eventLeft = event.offset().left,
                    eventHeight = event.innerHeight(),
                    eventWidth = event.innerWidth();

                var modalTop = Number(self.modal.css("top").replace("px", "")),
                    modalLeft = Number(
                        self.modal.css("left").replace("px", "")
                    );

                var modalTranslateX = eventLeft - modalLeft,
                    modalTranslateY = eventTop - modalTop;

                self.element.removeClass("animation-completed modal-is-open");

                //change modal width/height and translate it
                this.modal.css({
                    width: eventWidth + "px",
                    height: eventHeight + "px",
                });
                transformElement(
                    self.modal,
                    "translateX(" +
                        modalTranslateX +
                        "px) translateY(" +
                        modalTranslateY +
                        "px)"
                );

                //scale down modalBodyBg element
                transformElement(self.modalBodyBg, "scaleX(0) scaleY(1)");
                //scale down modalHeaderBg element
                transformElement(self.modalHeaderBg, "scaleY(1)");

                this.modalHeaderBg.one(transitionEnd, function () {
                    //wait for the  end of the modalHeaderBg transformation and reset modal style
                    self.modalHeaderBg.off(transitionEnd);
                    self.modal.addClass("no-transition");
                    setTimeout(function () {
                        self.modal
                            .add(self.modalHeader)
                            .add(self.modalBody)
                            .add(self.modalHeaderBg)
                            .add(self.modalBodyBg)
                            .attr("style", "");
                    }, 10);
                    setTimeout(function () {
                        self.modal.removeClass("no-transition");
                    }, 20);

                    self.animating = false;
                    self.element.removeClass("content-loaded");
                    event.removeClass("selected-event");
                });
            }

            //browser do not support transitions -> no need to wait for the end of it
            if (!transitionsSupported)
                self.modal.add(self.modalHeaderBg).trigger(transitionEnd);
        };

        SchedulePlan.prototype.mq = function () {
            //get MQ value ('desktop' or 'mobile')
            var self = this;
            return window
                .getComputedStyle(this.element.get(0), "::before")
                .getPropertyValue("content")
                .replace(/["']/g, "");
        };

        SchedulePlan.prototype.checkEventModal = function (device) {
            this.animating = true;
            var self = this;
            var mq = this.mq();

            if (mq == "mobile") {
                //reset modal style on mobile
                self.modal
                    .add(self.modalHeader)
                    .add(self.modalHeaderBg)
                    .add(self.modalBody)
                    .add(self.modalBodyBg)
                    .attr("style", "");
                self.modal.removeClass("no-transition");
                self.animating = false;
            } else if (
                mq == "desktop" &&
                self.element.hasClass("modal-is-open")
            ) {
                self.modal.addClass("no-transition");
                self.element.addClass("animation-completed");
                var event = self.eventsGroup.find(".selected-event");

                var eventTop = event.offset().top - $(window).scrollTop(),
                    eventLeft = event.offset().left,
                    eventHeight = event.innerHeight(),
                    eventWidth = event.innerWidth();

                var windowWidth = $(window).width(),
                    windowHeight = $(window).height();

                var modalWidth =
                        windowWidth * 0.8 > self.modalMaxWidth
                            ? self.modalMaxWidth
                            : windowWidth * 0.8,
                    modalHeight =
                        windowHeight * 0.8 > self.modalMaxHeight
                            ? self.modalMaxHeight
                            : windowHeight * 0.8;

                var HeaderBgScaleY = modalHeight / eventHeight,
                    BodyBgScaleX = modalWidth - eventWidth;

                setTimeout(function () {
                    self.modal.css({
                        width: modalWidth + "px",
                        height: modalHeight + "px",
                        top: windowHeight / 2 - modalHeight / 2 + "px",
                        left: windowWidth / 2 - modalWidth / 2 + "px",
                    });
                    transformElement(self.modal, "translateY(0) translateX(0)");
                    //change modal modalBodyBg height/width
                    // self.modalBodyBg.css({
                    //     height: modalHeight + 'px',
                    //     width: '1px',
                    // });
                    // transformElement(self.modalBodyBg, 'scaleX(' + BodyBgScaleX + ')');
                    //set modalHeader width
                    self.modalHeader.css({
                        width: eventWidth + "px",
                    });
                    //set modalBody left margin
                    self.modalBody.css({
                        marginLeft: eventWidth + "px",
                    });
                    //change modal modalHeaderBg height/width and scale it
                    self.modalHeaderBg.css({
                        // height: eventHeight + 'px',
                        width: eventWidth + "px",
                    });
                    // transformElement(self.modalHeaderBg, 'scaleY(' + HeaderBgScaleY + ')');
                }, 10);

                setTimeout(function () {
                    self.modal.removeClass("no-transition");
                    self.animating = false;
                }, 20);
            }
        };

        var schedules = $(".cd-schedule");
        var objSchedulesPlan = [],
            windowResize = false;

        if (schedules.length > 0) {
            schedules.each(function () {
                //create SchedulePlan objects
                objSchedulesPlan.push(new SchedulePlan($(this)));
            });
        }

        $(window).on("resize", function () {
            if (!windowResize) {
                windowResize = true;
                !window.requestAnimationFrame
                    ? setTimeout(checkResize)
                    : window.requestAnimationFrame(checkResize);
            }
        });

        $(window).keyup(function (event) {
            if (event.keyCode == 27) {
                objSchedulesPlan.forEach(function (element) {
                    element.closeModal(
                        element.eventsGroup.find(".selected-event")
                    );
                });
            }
        });

        function checkResize() {
            objSchedulesPlan.forEach(function (element) {
                element.scheduleReset();
            });
            windowResize = false;
        }

        function getScheduleTimestamp(time) {
            //accepts hh:mm format - convert hh:mm to timestamp
            time = time.replace(/ /g, "");
            var timeArray = time.split("/");
            var timeStamp = parseInt(timeArray[0]) * 60;
            return timeStamp;
        }

        function getDate(date) {
            date = date.replace(/ /g, "");
            var startArray = date.split("/");
            var startStamp = startArray[0] + "/" + startArray[1];
            return startStamp;
        }

        function transformElement(element, value) {
            element.css({
                "-moz-transform": value,
                "-webkit-transform": value,
                "-ms-transform": value,
                "-o-transform": value,
                transform: value,
            });
        }
    });
}

/*------------------
       Select Month Shedule
    --------------------*/
$(".select-month").on("change", function () {
    var month = $(this).val();
    var year = $(".select-year").val();
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_select_month,
        method: "POST",
        data: {
            _token: _token,
            year: year,
            month: month,
        },
        success: function (data) {
            shedule(data.day);
            $(".cd-schedule").html(data.html);
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
        },
    });
});

$(document).on("change", ".select-month-details", function () {
    var month = $(this).val();
    var year = $(".select-year-details").val();
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_select_month_details,
        method: "POST",
        data: {
            _token: _token,
            year: year,
            month: month,
        },
        success: function (data) {
            pushCurrentTime(month, year);
            shedule(data.day);
            $(".schedule").html(data.html);
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
        },
    });
});

$(document).on("click", ".btn-schedule-search", function () {
    $("input[name=search-keywords]").focus();
    $(".schedule-search").addClass("search-show");
    $(".btn-search").html(
        '<button class="btn-close-search"><i class="fas fa-times"></i></button>'
    );
    $(".search-results").addClass("search-results-show");
});

$(document).on("click", ".btn-close-search", function () {
    var month = $(".select-month-details").val();
    var year = $(".select-year-details").val();
    $(".schedule-search").removeClass("search-show");
    $(".btn-search").html(
        '<button class="btn-schedule-search"><i class="fas fa-search"></i></button>'
    );
    $(".search-results").removeClass("search-results-show");
    $(".schedule-search").val("");
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_select_month_details,
        method: "POST",
        data: {
            _token: _token,
            year: year,
            month: month,
        },
        success: function (data) {
            shedule(data.day);
            $(".schedule").html(data.html);
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
        },
    });
});

$(document).on("keyup", ".schedule-search", function () {
    var query = $(this).val();
    var currentTime = JSON.parse(localStorage.getItem("currentTime")) || [];
    if (query != "") {
        $.ajax({
            url: url_suggest_schedule_search,
            method: "POST",
            data: {
                currentTime: currentTime,
                query: query,
                _token: _token,
            },
            success: function (data) {
                $(".search-results").addClass("search-results-show");
                $(".search-results").html(data.html);
            },
        });
    } else {
        $(".search-results").removeClass("search-results-show");
        $(".search-results").html("");
    }
});

$(document).on("click", ".li_search", function () {
    var value = $(this).text();
    var currentTime = JSON.parse(localStorage.getItem("currentTime")) || [];
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $(".search-results").removeClass("search-results-show");
    $(".search-results").html("");
    $(".schedule-search").val(value);
    $.ajax({
        url: url_schedule_search,
        method: "POST",
        data: {
            currentTime: currentTime,
            value: value,
            _token: _token,
        },
        success: function (data) {
            shedule(data.day);
            $(".schedule").html(data.html);
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
        },
    });
});

$(".select-month-details-clone").on("change", function () {
    var month = $(this).val();
    var year = $(".select-year-details-clone").val();
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_select_month_details_clone,
        method: "POST",
        data: {
            _token: _token,
            year: year,
            month: month,
        },
        success: function (data) {
            shedule(data.day);
            $(".schedule").html(data.html);
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
        },
    });
});

/*------------------
        Set month when year change
        --------------------*/
$(".select-year").on("change", function () {
    $(".define-month").prop("selected", true);
});
$(document).on("change", ".select-year-details", function () {
    $(".define-month-details").prop("selected", true);
});
$(".select-year-details-clone").on("change", function () {
    $(".define-month-details-clone").prop("selected", true);
});

/*------------------
       Handle Shedule
    --------------------*/
$(document).on("click", ".send_quantity_draft", function () {
    var id = $(".event-info").find(".event-id").text();
    var order_quantity_draft = $(".order_quantity_draft").val();
    var order_note_ktv = $(".order_note_ktv").val();
    url_update_order_quantity_draft = url_update_order_quantity_draft.replace(":id",id);
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_update_order_quantity_draft,
        method: "POST",
        data: {
            _token: _token,
            order_quantity_draft: order_quantity_draft,
            order_note_ktv: order_note_ktv
        },
        success: function () {
            $(".event-modal").fadeOut();
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
            successMsg("Bạn đã cập nhật số Cas chụp thành công");
            location.reload();
        },
    });
});

$(document).on("click", ".send_quantity_details", function () {
    var id = $(".event-info").find(".event-id").text();
    var data = getValues();
    data.push({ name: "_token", value: _token });
    url_update_order_quantity_details = url_update_order_quantity_details.replace(":id", id);
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_update_order_quantity_details,
        method: "POST",
        data: data,
        success: function () {
            $(".event-modal").fadeOut();
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
            successMsg("Bạn đã cập nhật số Cas chụp thành công");
            location.reload();
        },
    });
});

$(document).on("click", ".update-order", function () {
    var id = $(".event-info").find(".event-id").text();
    var data = getValuesClone();
    data.push({ name: "_token", value: _token });
    url_update_order_schedule = url_update_order_schedule.replace(":id", id);
    $(".loader").fadeIn();
    $("#preloder").fadeIn("slow");
    $.ajax({
        url: url_update_order_schedule,
        method: "POST",
        data: data,
        success: function () {
            $(".event-modal").fadeOut();
            $(".loader").fadeOut();
            $("#preloder").fadeOut("slow");
            successMsg("Bạn đã cập nhật thông tin đơn hàng thành công");
            location.reload();
        },
    });
});
