function schedule(e){jQuery(document).ready((function(t){var n="webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend",i=t(".csstransitions").length>0;function d(e){this.element=e,this.timeline=this.element.find(".timeline"),this.timelineItems=this.timeline.find("li"),this.timelineItemsNumber=this.timelineItems.length,this.timelineStart=m(this.timelineItems.eq(0).text()),this.timelineUnitDuration=m(this.timelineItems.eq(1).text())-m(this.timelineItems.eq(0).text()),this.eventsWrapper=this.element.find(".events"),this.eventsGroup=this.eventsWrapper.find(".events-group"),this.singleEvents=this.eventsGroup.find(".single-event"),this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.modal=this.element.find(".event-modal"),this.modalHeader=this.modal.find(".header"),this.modalHeaderBg=this.modal.find(".header-bg"),this.modalBody=this.modal.find(".body"),this.modalBodyBg=this.modal.find(".body-bg"),this.modalMaxWidth=900,this.modalMaxHeight=640,this.animating=!1,this.initSchedule()}i||(n="noTransition"),d.prototype.initSchedule=function(){this.scheduleReset(),this.initEvents()},d.prototype.scheduleReset=function(){var t=this.mq();"desktop"==t?(this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.element.addClass("js-full"),this.placeEvents(),this.element.hasClass("modal-is-open")&&this.checkEventModal(),30==e?this.eventsGroup.children("ul").css({height:"3000px"}):this.eventsGroup.children("ul").css({height:"3100px"})):"mobile"==t?(this.element.removeClass("js-full loading"),this.eventsGroup.children("ul").add(this.singleEvents).removeAttr("style"),this.eventsWrapper.children(".grid-line").remove(),this.element.hasClass("modal-is-open")&&this.checkEventModal()):"desktop"==t&&this.element.hasClass("modal-is-open")?(this.checkEventModal("desktop"),this.element.removeClass("loading")):this.element.removeClass("loading")},d.prototype.initEvents=function(){var e=this;this.singleEvents.each((function(){var n='<span class="event-date">Ngày chụp: '+h(t(this).data("start"))+" - "+h(t(this).data("end"))+"</span>";t(this).children("a").prepend(t(n)),t(this).on("click","a",(function(n){n.preventDefault(),e.animating||e.openModal(t(this))}))})),this.modal.on("click",".close",(function(t){t.preventDefault(),e.animating||e.closeModal(e.eventsGroup.find(".selected-event"))})),this.element.on("click",".cover-layer",(function(t){!e.animating&&e.element.hasClass("modal-is-open")&&e.closeModal(e.eventsGroup.find(".selected-event"))}))},d.prototype.placeEvents=function(){var e=this,n=0;this.singleEvents.each((function(){var i=m(t(this).attr("data-start")),d=m(t(this).attr("data-end")),a=t(this).attr("data-child");1==a?t(this).css({left:"0px"}):2==a?t(this).css({left:"23px"}):t(this).css({right:"0px"}),n=i==d?60:d-i+60;var o=e.eventSlotHeight*(i-e.timelineStart)/e.timelineUnitDuration,s=e.eventSlotHeight*n/e.timelineUnitDuration;t(this).css({top:o-1+"px",height:s+1+"px"})})),this.element.removeClass("loading")},d.prototype.openModal=function(e){var d=this,a=d.mq();const o=new Date,s=new Date(e.find(".event-start-day").html());if(this.animating=!0,this.modalHeader.find(".event-name-id").text(e.find(".event-name-id").text()),this.modalHeader.find(".event-name").text(e.find(".event-name").text()),this.modalHeader.find(".event-name-unit").text(e.find(".event-name-unit").text()),this.modalHeader.find(".event-date").text(e.find(".event-date").text()),this.modal.attr("data-event",e.parent().attr("data-event")),this.modalBody.find(".event-info").load(e.parent().attr("data-content")+".html .event-info > *",(function(e){d.element.addClass("content-loaded")})),this.modalBody.find(".event-car-id").html(e.find(".event-car-id").html()),this.modalBody.find(".event-id").html(e.find(".event-id").html()),this.modalBody.find(".event-unit").html(e.find(".event-unit").html()),this.modalBody.find(".event-cty-name").html(e.find(".event-cty-name").html()),this.modalBody.find(".event-address").html(e.find(".event-address").html()),this.modalBody.find(".event-note-content").html(e.find(".event-note").html()),this.modalBody.find(".event-select").html(e.find(".event-select").html()),this.modalBody.find(".event-info-contact").html(e.find(".event-info-contact").html()),this.modalBody.find(".event-time").html(e.find(".event-time").html()),this.modalBody.find(".event-doctor-read").html(e.find(".event-doctor-read").html()),this.modalBody.find(".event-film").html(e.find(".event-film").html()),this.modalBody.find(".event-form").html(e.find(".event-form").html()),this.modalBody.find(".event-print").html(e.find(".event-print").html()),this.modalBody.find(".event-form-print").html(e.find(".event-form-print").html()),this.modalBody.find(".event-print-result").html(e.find(".event-print-result").html()),this.modalBody.find(".event-film-sheet").html(e.find(".event-film-sheet").html()),this.modalBody.find(".event-order-note").html(e.find(".event-order-note").html()),this.modalBody.find(".event-ord-warning").html(e.find(".event-warning").html()),this.modalBody.find(".event-deadline").html(e.find(".event-deadline").html()),this.modalBody.find(".event-deliver-results").html(e.find(".event-deliver-results").html()),this.modalBody.find(".event-email").html(e.find(".event-email").html()),1==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="processing">Đang xử lý</span>'):2==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="updated">Đã cập nhật số Cas thực tế</span>'):4==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="update-acc">Đã cập nhật doanh thu</span>'):this.modalBody.find(".event-status").html('<span class="processed">Đã xử lý</span>'),this.modalBody.find(".event-quantity").html(e.find(".event-quantity").html()),this.modalBody.find(".event-draft").html(e.find(".event-quantity-draft").html()+" Cas"),this.modalBody.find(".event-noteKtv").html(e.find(".event-note-ktv").html()),this.modalBody.find(".event-accountant-doctor-read-clone").html(e.find(".event-accountant-doctor-read").text()),this.modalBody.find(".event-35X43-clone").html(e.find(".event-35X43").text()),this.modalBody.find(".event-polime-clone").html(e.find(".event-polime").text()),this.modalBody.find(".event-8X10-clone").html(e.find(".event-8X10").text()),this.modalBody.find(".event-10X12-clone").html(e.find(".event-10X12").text()),this.modalBody.find(".event-accountant-note-clone").html(e.find(".event-accountant-note").text()),this.modalBody.find(".event-delivery-date-clone").html(e.find(".event-delivery-date").text()),s.getTime()>o.getTime()?this.modalBody.find(".edit-order").html("<a href="+e.find(".event-route-edit").text()+' target="_blank" class="primary-btn-submit">Chỉnh sửa</a>'):this.modalBody.find(".edit-order").html(""),this.element.addClass("modal-is-open"),setTimeout((function(){e.parent("li").addClass("selected-event")}),10),"mobile"==a)d.modal.one(n,(function(){d.modal.off(n),d.animating=!1}));else{var l=e.offset().top-t(window).scrollTop(),m=e.offset().left,h=(e.innerHeight(),e.innerWidth()),f=t(window).width(),c=t(window).height(),v=.8*f>d.modalMaxWidth?d.modalMaxWidth:.8*f,p=.8*c>d.modalMaxHeight?d.modalMaxHeight:.8*c,u=parseInt((f-v)/2-m),g=parseInt((c-p)/2-l);d.modal.css({top:l+"px",left:m+"px",height:p+"px",width:v+"px"}),r(d.modal,"translateY("+g+"px) translateX("+u+"px)"),d.modalHeader.css({width:h+"px"}),d.modalBody.css({marginLeft:h+"px"}),d.modalHeaderBg.css({width:h+"px"}),d.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.animating=!1,d.element.addClass("animation-completed")}))}i||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.closeModal=function(e){var d=this,a=d.mq();if(this.animating=!0,"mobile"==a)this.element.removeClass("modal-is-open"),this.modal.one(n,(function(){d.modal.off(n),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}));else{var o=e.offset().top-t(window).scrollTop(),s=e.offset().left,l=e.innerHeight(),m=e.innerWidth(),h=Number(d.modal.css("top").replace("px","")),f=s-Number(d.modal.css("left").replace("px","")),c=o-h;d.element.removeClass("animation-completed modal-is-open"),this.modal.css({width:m+"px",height:l+"px"}),r(d.modal,"translateX("+f+"px) translateY("+c+"px)"),r(d.modalBodyBg,"scaleX(0) scaleY(1)"),r(d.modalHeaderBg,"scaleY(1)"),this.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.modal.addClass("no-transition"),setTimeout((function(){d.modal.add(d.modalHeader).add(d.modalBody).add(d.modalHeaderBg).add(d.modalBodyBg).attr("style","")}),10),setTimeout((function(){d.modal.removeClass("no-transition")}),20),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}))}i||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.mq=function(){return window.getComputedStyle(this.element.get(0),"::before").getPropertyValue("content").replace(/["']/g,"")},d.prototype.checkEventModal=function(e){this.animating=!0;var n=this,i=this.mq();if("mobile"==i)n.modal.add(n.modalHeader).add(n.modalHeaderBg).add(n.modalBody).add(n.modalBodyBg).attr("style",""),n.modal.removeClass("no-transition"),n.animating=!1;else if("desktop"==i&&n.element.hasClass("modal-is-open")){n.modal.addClass("no-transition"),n.element.addClass("animation-completed");var d=n.eventsGroup.find(".selected-event"),a=(d.offset().top,t(window).scrollTop(),d.offset().left,d.innerHeight(),d.innerWidth()),o=t(window).width(),s=t(window).height(),l=.8*o>n.modalMaxWidth?n.modalMaxWidth:.8*o,m=.8*s>n.modalMaxHeight?n.modalMaxHeight:.8*s;setTimeout((function(){n.modal.css({width:l+"px",height:m+"px",top:s/2-m/2+"px",left:o/2-l/2+"px"}),r(n.modal,"translateY(0) translateX(0)"),n.modalHeader.css({width:a+"px"}),n.modalBody.css({marginLeft:a+"px"}),n.modalHeaderBg.css({width:a+"px"})}),10),setTimeout((function(){n.modal.removeClass("no-transition"),n.animating=!1}),20)}};var a=t(".cd-schedule"),o=[],s=!1;function l(){o.forEach((function(e){e.scheduleReset()})),s=!1}function m(e){var t=(e=e.replace(/ /g,"")).split("/");return 60*parseInt(t[0])}function h(e){var t=(e=e.replace(/ /g,"")).split("/");return t[0]+"/"+t[1]}function r(e,t){e.css({"-moz-transform":t,"-webkit-transform":t,"-ms-transform":t,"-o-transform":t,transform:t})}a.length>0&&a.each((function(){o.push(new d(t(this)))})),t(window).on("resize",(function(){s||(s=!0,window.requestAnimationFrame?window.requestAnimationFrame(l):setTimeout(l))})),t(window).keyup((function(e){27==e.keyCode&&o.forEach((function(e){e.closeModal(e.eventsGroup.find(".selected-event"))}))}))}))}schedule(day),$(".select-month").on("change",(function(){var e=$(this).val(),t=$(".select-year").val();$(".loader-over").fadeIn(),$.ajax({url:url_select_sales,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),year:t,month:e},success:function(e){schedule(e.day),$(".schedule").html(e.html),$(".loader-over").fadeOut()}})})),$(".select-year").on("change",(function(){$(".define-month").prop("selected",!0)}));