/*------------------
                   Function Schedule
                --------------------*/
schedule(day);
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
                var href = event.find(".event-list-file-path").text();
                var fileName = event.find(".event-list-file").text();
                if (href != "" || fileName != 0) {
                    var hrefConvert = href.split(",");
                    var fileNameConvert = fileName.split(",");
                    var result = "";
                    Array.prototype.associate = function (keys) {
                        var result = {};
                        this.forEach(function (el, i) {
                            result[keys[i]] = el;
                        });
                        return result;
                    };
                    $.each(hrefConvert.associate(fileNameConvert), (k, v) => {
                        result +=
                            '<div class="main-file"><div class="file-content"><div class="file-name">' +
                            k +
                            '</div><div class="file-action"><a href="https://drive.google.com/file/d/' +
                            v +
                            '/view"target="_blank" class="download-file"><i class="far fa-eye"></i></a></div></div></div>';
                    });
                    return '<div class="section-file">' + result + "</div>";
                }
                return "Không có danh sách";
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
            if (
                event.find(".event-quantity-ktv").text() != 0 ||
                event.find(".event-note-ktv").text() != ""
            ) {
                this.modalBody.find(".order-quantity-ktv").addClass("hidden");
                this.modalBody.find(".event-draft").removeClass("hidden");
                this.modalBody
                    .find(".event-draft")
                    .html(event.find(".event-quantity-ktv").html() + " Cas");
                this.modalBody.find(".order_note_ktv").addClass("hidden");
                this.modalBody.find(".event-noteKtv").removeClass("hidden");
                this.modalBody
                    .find(".event-noteKtv")
                    .html(event.find(".event-note-ktv").html());
                this.modalBody.find(".submit-quantity-technologist").addClass("hidden");
            } else {
                this.modalBody
                    .find(".order-quantity-ktv")
                    .removeClass("hidden");
                this.modalBody.find(".order_note_ktv").removeClass("hidden");
                this.modalBody.find(".event-noteKtv").addClass("hidden");
                this.modalBody.find(".event-draft").addClass("hidden");
                this.modalBody
                    .find(".submit-quantity-technologist")
                    .removeClass("hidden");
            }
            this.modalBody
                .find(".event-order-note")
                .html(event.find(".event-order-note").html());
            this.modalBody
                .find(".event-quantity")
                .html(event.find(".event-quantity").html());

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
            $(".cd-schedule").html(data.html);
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
    var id = $(".event-info").find(".event-id").text();
    var order_quantity_draft = $(".order-quantity-ktv").val();
    var order_note_ktv = $(".order_note_ktv").val();
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
    });
});
