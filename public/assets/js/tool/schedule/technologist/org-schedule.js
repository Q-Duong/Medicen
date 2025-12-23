schedule(day);
// --- Helper Functions ---

function getShortName(fullName) {
    if (!fullName) return "";
    return fullName.trim().split(" ").pop();
}

function formatDate(dateString) {
    if (!dateString) return "";
    var date = new Date(dateString);
    var day = date.getDate();
    var month = date.getMonth() + 1;
    var year = date.getFullYear();
    day = day < 10 ? "0" + day : day;
    month = month < 10 ? "0" + month : month;
    return day + "/" + month + "/" + year;
}

function carRenameFunction(car) {
    const carNames = {
        6: "Xe Thuê",
        7: "Xe Tăng Cường",
        8: "Xe Siêu Âm",
    };

    return carNames[car] || "Xe " + car;
}

function getCarColor(carId) {
    var id = parseInt(carId);
    switch (id) {
        case 1:
            return "bg-blue";
        case 2:
            return "bg-gray";
        case 3:
            return "bg-green";
        case 4:
            return "bg-red";
        case 5:
            return "bg-yellow";
        case 6:
            return "bg-purple";
        case 7:
            return "bg-orange";
        case 8:
            return "bg-teal";
        default:
            return "bg-blue";
    }
}

/*------------------
                       Function Schedule
                    --------------------*/
function schedule(day) {
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
            this.modalMaxWidth = 800;
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
                // detect click on the event and open the modal
                $(this).on("click", "a", function (event) {
                    event.preventDefault();
                    if (!self.animating) self.openModal($(this));
                });
            });

            $(document)
                .off("click", ".single-card-event a")
                .on("click", ".single-card-event a", function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!self.animating) {
                        self.openModal($(this));
                    }
                });

            //close modal window
            this.modal.on("click", ".close", function (event) {
                event.preventDefault();
                if (!self.animating)
                    self.closeModal(self.eventsGroup.find(".selected-event"));
            });
            this.element.on("click", ".cover-layer", function (event) {
                event.preventDefault();
                console.log(event);
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
                    end = getScheduleTimestamp($(this).attr("data-start")),
                    extraTop = 0,
                    subtractWidth = 20;

                if ($(this).hasClass("bg-back")) {
                    extraTop = 5;
                    subtractWidth = 36;
                    $(this).css({
                        left: 18 + "px",
                    });
                } else {
                    $(this).css({
                        left: 10 + "px",
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
                    top: eventTop + 10 + extraTop + "px",
                    height: eventHeight - 20 + "px",
                    width: "calc(100% - " + subtractWidth + "px)",
                });
            });
            this.element.removeClass("loading");
        };

        SchedulePlan.prototype.openModal = function (event) {
            var self = this;
            var mq = self.mq();
            this.animating = true;
            $("body").css("overflow", "hidden");

            // Kiểm tra xem #calendarModal có đang hiển thị không
            if ($("#calendarModal").is(":visible")) {
                this.isFromMulti = true;
                $("#calendarModal").hide();
            } else {
                this.isFromMulti = false;
            }

            // 1. LẤY DỮ LIỆU TỪ JSON
            var parentItem = event.closest("[data-json]");
            if (parentItem.length === 0)
                parentItem = event.closest(".single-event");

            var rawJson = parentItem.attr("data-json");
            var parsedData = {};
            try {
                if (rawJson)
                    parsedData =
                        typeof rawJson === "string"
                            ? JSON.parse(rawJson)
                            : rawJson;
            } catch (e) {}
            var data = Array.isArray(parsedData) ? parsedData[0] : parsedData;

            // 2. HEADER
            var fullKtv1 = data.car_ktv_name_1;
            var fullKtv2 = data.car_ktv_name_2;
            var name1 = getShortName(fullKtv1);
            var name2 = getShortName(fullKtv2);
            var displayName = name1 + (name2 ? ", " + name2 : "");

            this.modalHeader
                .find(".event-date")
                .html(formatDate(data.ord_start_day));
            this.modalHeader.find(".event-technician").html(displayName);
            this.modalHeader
                .find(".event-order-id")
                .html("<b>Mã đơn hàng:</b> " + data.order_id);
            this.modalHeader
                .find(".event-unit")
                .html("<b>Đơn vị:</b> " + data.unit_abbreviation);

            // Set màu cho Modal
            this.modal.attr("data-event", event.parent().attr("data-event"));

            var mb = self.modalBody;
            // Helper function để điền text/html an toàn
            var setHtml = (selector, val) =>
                mb
                    .find(selector)
                    .html(val !== null && val !== undefined ? val : "");
            var setText = (selector, val) =>
                mb
                    .find(selector)
                    .text(val !== null && val !== undefined ? val : "");

            setText(".event-car-id").text(data.id);
            setText(".event-order-id").text(data.order_id);
            setText(".event-unit").text(data.unit_abbreviation);
            setText(".event-cty-name").text(data.ord_cty_name);
            setText(".event-address").text(data.customer_address);
            setText(".event-other-address").text(data.customer_note);
            setText(".event-select").text(data.ord_select);
            setText(".event-info-contact").html(
                (data.customer_name || "") +
                    " (" +
                    (data.customer_phone || "") +
                    ")"
            );
            setText(".event-time").html(data.ord_time);
            setText(".event-quantity").html(data.order_quantity);
            setText(".event-order-note").html(data.ord_note);
            setText(".event-email").html(data.ord_email);
            setText(".event-draft").html(
                (data.order_quantity_draft || 0) + " Cas"
            );
            setText(".event-noteKtv").html(data.order_note_ktv);

            // Files
            var href = data.ord_list_file_path || "";
            var fileName = data.ord_list_file || "";
            if (href != "" || fileName != 0) {
                var hrefConvert = href.split(",");
                var fileNameConvert = fileName.split(",");
                var result = "";
                if (!Array.prototype.associate) {
                    Array.prototype.associate = function (keys) {
                        var result = {};
                        this.forEach(function (el, i) {
                            result[keys[i]] = el;
                        });
                        return result;
                    };
                }
                $.each(hrefConvert.associate(fileNameConvert), (k, v) => {
                    result +=
                        '<div class="main-file"><div class="file-content"><div class="file-name">' +
                        k +
                        '</div><div class="file-action"><a href="https://drive.google.com/file/d/' +
                        v +
                        '/view"target="_blank" class="download-file"><i class="far fa-eye"></i></a></div></div></div>';
                });
                mb.find(".event-list-file").html(
                    '<div class="section-file">' + result + "</div>"
                );
            } else {
                mb.find(".event-list-file").html("Không có danh sách");
            }

            // Locking Logic
            var quantity_draft = parseInt(data.order_quantity_draft || 0);
            var technologist_note = data.order_note_ktv || "";
            var hasData = quantity_draft !== 0 || technologist_note !== "";
            var htmlContent = hasData
                ? ""
                : `<button type="button" class="form-button button-submit rs-lookup-submit submit-quantity-technologist">Cập nhật</button>`;

            mb.find(".order-quantity-ktv")
                .val(hasData ? quantity_draft : "")
                .toggleClass("form-textbox-entered", hasData);

            mb.find(".order-note-ktv").val(technologist_note);
            mb.find(".order-quantity-ktv, .order-note-ktv").prop(
                "disabled",
                hasData
            );

            mb.find(".rs-overlay-change").html(htmlContent);

            // 4. ANIMATION
            self.element.addClass("content-loaded");
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
                    eventLeft = event.offset().left;

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
            self.modalBody.find(".rs-overlay-change").html('');
            $("body").removeAttr("style");

            //HIỆN LẠI POPUP DANH SÁCH (NẾU CẦN)
            if (this.isFromMulti) {
                $("body").css("overflow", "hidden");
                $("#calendarModal").css("display", "flex");
            }

            if (mq == "mobile") {
                this.element.removeClass("modal-is-open");
                this.modal.one(transitionEnd, function () {
                    self.modal.off(transitionEnd);
                    self.animating = false;
                    self.element.removeClass("content-loaded");
                    event.removeClass("selected-event");
                });
            } else {
                var eventHeight = event.innerHeight(),
                    eventWidth = event.innerWidth();

                var modalTop = Number(self.modal.css("top").replace("px", "")),
                    modalLeft = Number(
                        self.modal.css("left").replace("px", "")
                    );

                var modalTranslateX = modalLeft,
                    modalTranslateY = modalTop;

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

                (eventHeight = event.innerHeight()),
                    (eventWidth = event.innerWidth());

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
                    // self.modalHeader.css({
                    //     width: eventWidth + "px",
                    // });
                    //set modalBody left margin
                    // self.modalBody.css({
                    //     marginLeft: eventWidth + "px",
                    // });
                    //change modal modalHeaderBg height/width and scale it
                    // self.modalHeaderBg.css({
                    //     // height: eventHeight + 'px',
                    //     width: eventWidth + "px",
                    // });
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

function openMultiModal(element) {
    $("body").css("overflow", "hidden");
    var rawData = element.getAttribute("data-json");
    if (!rawData) return;
    var allOrders = JSON.parse(rawData);
    var modalBody = document.getElementById("modalBody");
    var modalDateLabel = document.getElementById("header-date-label");
    var modalHeadline = document.getElementById("header-headline");
    if (allOrders.length > 0) {
        modalDateLabel.innerText =
            " Ngày " + formatDate(allOrders[0].ord_start_day);

        modalHeadline.innerText =
            "Danh Sách Lịch " + carRenameFunction(allOrders[0].car_name);
    }
    modalBody.innerHTML = "";

    allOrders.forEach(function (order) {
        var div = document.createElement("div");
        var colorClass = getCarColor(order.car_name);
        div.className = `single-event single-card-event ${colorClass} border-child`;
        div.setAttribute("data-start", formatDate(order.ord_start_day));
        div.setAttribute("data-content", "event-rowing-workout");
        div.setAttribute("data-event", "event-" + order.car_name);
        div.setAttribute("data-json", JSON.stringify(order));
        var name1 = getShortName(order.car_ktv_name_1);
        var name2 = getShortName(order.car_ktv_name_2);
        var displayName = name1 + (name2 ? ", " + name2 : "");

        div.innerHTML = `
            <div class="notification-icon-block">
                ${
                    order.order_warning == "Có"
                        ? `<div class="order-warning"><i class="fa fa-exclamation-triangle"></i></div>`
                        : ""
                }
                ${
                    order.order_updated == 1
                        ? `<div class="order-status"><i class="fas fa-check-circle"></i></div>`
                        : ""
                }
            </div>
            <a href="javascript:;" style="padding: 10px; display: block;">
                <div class="event-title">
                    <div class="sub-title">
                        <span class="sub-title-date"><b>Ngày:</b>
                            ${formatDate(order.ord_start_day)}
                        </span>
                        <p class="line">-</p>
                        <span class="sub-title-technician"><b>KTV:</b>
                            ${displayName}
                        </span>
                    </div>
                    <span class="event-name-unit">
                        <b>Mã đơn hàng:</b> ${order.order_id}
                    </span>
                    <p class="event-name-unit">
                        <b>Đơn vị:</b> ${order.unit_abbreviation}
                    </p>
                </div>
            </a>
        `;

        modalBody.appendChild(div);
    });
    document.getElementById("calendarModal").style.display = "flex";
}

function closeMultiModal() {
    $("body").removeAttr("style");
    document.getElementById("calendarModal").style.display = "none";
}

window.onclick = function (event) {
    if (event.target == document.getElementById("calendarModal")) {
        closeMultiModal();
    }
};

/*------------------
                                                   Select Month Schedule
                                                --------------------*/

$(".select-month").on("change", function () {
    var month = $(this).val();
    var year = $(".select-year").val();
    $(".loader-over").fadeIn();
    $.ajax({
        url: url_select_technologist,
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr("content"),
            year: year,
            month: month,
        },
        success: function (data) {
            schedule(data.day);
            $(".schedule").html(data.html);
            $(".loader-over").fadeOut();
        },
        error: function (textStatus) {
            popupNotificationSessionExpired();
        },
        complete: function () {
            $(".loader-over").fadeOut();
        },
    });
});
/*------------------
                                                    Set month when year change
                                                    --------------------*/
$(".select-year").on("change", function () {
    $(".define-month").prop("selected", true);
});
/*------------------
                                                   Handle Schedule
                                                --------------------*/
$(document).on("click", ".submit-quantity-technologist", function () {
    var id = $(".event-info").find(".event-order-id").text();
    var order_quantity_draft = $(".order-quantity-ktv").val();
    var order_note_ktv = $(".order-note-ktv").val();
    $(".loader-over").fadeIn();
    $.ajax({
        url: url_update_technologist,
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr("content"),
            id: id,
            order_quantity_draft: order_quantity_draft,
            order_note_ktv: order_note_ktv,
        },
        success: function () {
            $(".event-modal").fadeOut();
            $(".cover-layer").css({ opacity: "0" });
            $(".loader-over").fadeOut();
            successMsg("Bạn đã cập nhật số Cas chụp thành công");
            setTimeout(function () {
                location.reload();
            }, 1000);
        },
        error: function (textStatus) {
            popupNotificationSessionExpired();
        },
        complete: function () {
            $(".event-modal").fadeOut();
            $(".cover-layer").css({ opacity: "0" });
            $(".loader-over").fadeOut();
        },
    });
});
