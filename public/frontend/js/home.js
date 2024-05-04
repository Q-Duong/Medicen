/*  ---------------------------------------------------
    Template Name: Medicen
    Description: Medicen - ecommerce teplate
    Author: Colorib
    Author URI: https://www.colorib.com/
    Version: 1.0
    Created: Colorib
---------------------------------------------------------  */

"use strict";

(function ($) {
    /*-----------------------
            Hero Slider
        ------------------------*/
    $(".hero__slider").owlCarousel({
        loop: true,
        margin: 0,
        items: 1,
        dots: false,
        nav: true,
        navText: ["<span class='arrow_left'>", "<span class='arrow_right'>"],
        animateOut: "fadeOut",
        animateIn: "fadeIn",
        smartSpeed: 1200,
        autoHeight: false,
        autoplay: true,
    });

    $(".service").owlCarousel({
        margin: 30,
        autoWidth: false,
        dots: false,
        animateOut: "fadeOut",
        animateIn: "fadeIn",
        smartSpeed: 1200,
        responsive: {
            0: {
                items: 1,
            },
            576: {
                items: 2,
            },
            1300: {
                items: 2,
                nav: true,
                navText: [
                    "<span class='arrow_carrot-left'>",
                    "<span class='arrow_carrot-right'>",
                ],
            },
        },
    });

    $(".post").owlCarousel({
        margin: 30,
        autoWidth: false,
        dots: false,
        animateOut: "fadeOut",
        animateIn: "fadeIn",
        smartSpeed: 1200,
        responsive: {
            0: {
                items: 1,
            },
            576: {
                items: 2,
            },
            1300: {
                items: 2,
                nav: true,
                navText: [
                    "<span class='arrow_carrot-left'>",
                    "<span class='arrow_carrot-right'>",
                ],
            },
        },
    });
})(jQuery);
