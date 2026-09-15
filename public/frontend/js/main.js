(function ($) {
    /*------------------
        Preloader
    --------------------*/
    $(window).on("load", function () {
        $(".loader-over").fadeOut();
        /*------------------
            Background Set
        --------------------*/
        $(".set-bg").each(function () {
            var bg = $(this).data("setbg");
            $(this).css("background-image", "url(" + bg + ")");
        });
    });

    /*------------------
        Scroll Up
    --------------------*/
    var btn = $("#button");

    $(window).scroll(function () {
        if ($(window).scrollTop() > 200) {
            btn.addClass("show");
        } else {
            btn.removeClass("show");
        }
    });

    btn.on("click", function (e) {
        e.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, "200");
    });

    //Globalnav Menu
    $(".globalnav-menutrigger-button").on("click", function () {
        var isExpanded = $(this).attr("aria-expanded") === "true";
        $(this).attr("aria-expanded", !isExpanded);
        if (!isExpanded) {
            $("#globalnav-anim-menutrigger-bread-top-open")[0].beginElement();
            $(
                "#globalnav-anim-menutrigger-bread-bottom-open"
            )[0].beginElement();
            $(this).attr("aria-label", "Close");
            $(".offcanvas-menu-wrapper").addClass("active");
        } else {
            $("#globalnav-anim-menutrigger-bread-top-close")[0].beginElement();
            $(
                "#globalnav-anim-menutrigger-bread-bottom-close"
            )[0].beginElement();
            $(this).attr("aria-label", "Menu");
            $(".offcanvas-menu-wrapper").removeClass("active");
        }
    });

    $(".ac-gf-directory-column-section-title-button").each(function () {
        var $iconContainer = $(this).find(
            ".ac-gf-directory-column-section-title-icon"
        );
        var expandAnimation = $iconContainer.find(
            '[data-footer-animate="expand"]'
        )[0];
        var collapseAnimation = $iconContainer.find(
            '[data-footer-animate="collapse"]'
        )[0];
        $(this).on("click", function () {
            var isExpanded = $(this).attr("aria-expanded");
            var controls = $(this).attr("aria-controls");
            if (isExpanded === "false") {
                expandAnimation.beginElement();
                $(this).attr("aria-expanded", true);
                $("#" + controls)
                    .parent()
                    .addClass("ac-gf-directory-column-expanded");
            } else {
                collapseAnimation.beginElement();
                $(this).attr("aria-expanded", false);
                $("#" + controls)
                    .parent()
                    .removeClass("ac-gf-directory-column-expanded");
            }
        });
    });
})(jQuery);
