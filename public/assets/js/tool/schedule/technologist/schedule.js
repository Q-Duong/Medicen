function schedule(e){jQuery(document).ready((function(t){var n="webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend",i=t(".csstransitions").length>0;function d(e){this.element=e,this.timeline=this.element.find(".timeline"),this.timelineItems=this.timeline.find("li"),this.timelineItemsNumber=this.timelineItems.length,this.timelineStart=m(this.timelineItems.eq(0).text()),this.timelineUnitDuration=m(this.timelineItems.eq(1).text())-m(this.timelineItems.eq(0).text()),this.eventsWrapper=this.element.find(".events"),this.eventsGroup=this.eventsWrapper.find(".events-group"),this.singleEvents=this.eventsGroup.find(".single-event"),this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.modal=this.element.find(".event-modal"),this.modalHeader=this.modal.find(".header"),this.modalHeaderBg=this.modal.find(".header-bg"),this.modalBody=this.modal.find(".body"),this.modalBodyBg=this.modal.find(".body-bg"),this.modalMaxWidth=900,this.modalMaxHeight=640,this.animating=!1,this.initSchedule()}i||(n="noTransition"),d.prototype.initSchedule=function(){this.scheduleReset(),this.initEvents()},d.prototype.scheduleReset=function(){var t=this.mq();"desktop"==t?(this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.element.addClass("js-full"),this.placeEvents(),this.element.hasClass("modal-is-open")&&this.checkEventModal(),30==e?this.eventsGroup.children("ul").css({height:"3000px"}):this.eventsGroup.children("ul").css({height:"3100px"})):"mobile"==t?(this.element.removeClass("js-full loading"),this.eventsGroup.children("ul").add(this.singleEvents).removeAttr("style"),this.eventsWrapper.children(".grid-line").remove(),this.element.hasClass("modal-is-open")&&this.checkEventModal()):"desktop"==t&&this.element.hasClass("modal-is-open")?(this.checkEventModal("desktop"),this.element.removeClass("loading")):this.element.removeClass("loading")},d.prototype.initEvents=function(){var e=this;this.singleEvents.each((function(){var n='<span class="event-date">Ngày chụp: '+h(t(this).data("start"))+" - "+h(t(this).data("end"))+"</span>";t(this).children("a").prepend(t(n)),t(this).on("click","a",(function(n){n.preventDefault(),e.animating||e.openModal(t(this))}))})),this.modal.on("click",".close",(function(t){t.preventDefault(),e.animating||e.closeModal(e.eventsGroup.find(".selected-event"))})),this.element.on("click",".cover-layer",(function(t){!e.animating&&e.element.hasClass("modal-is-open")&&e.closeModal(e.eventsGroup.find(".selected-event"))}))},d.prototype.placeEvents=function(){var e=this,n=0;this.singleEvents.each((function(){var i=m(t(this).attr("data-start")),d=m(t(this).attr("data-end")),a=t(this).attr("data-child");1==a?t(this).css({left:"0px"}):2==a?t(this).css({left:"23px"}):t(this).css({right:"0px"}),n=i==d?60:d-i+60;var o=e.eventSlotHeight*(i-e.timelineStart)/e.timelineUnitDuration,s=e.eventSlotHeight*n/e.timelineUnitDuration;t(this).css({top:o-1+"px",height:s+1+"px"})})),this.element.removeClass("loading")},d.prototype.openModal=function(e){var d=this,a=d.mq();new Date,new Date(e.find(".event-start-day").html());if(this.animating=!0,this.modalHeader.find(".event-name-id").text(e.find(".event-name-id").text()),this.modalHeader.find(".event-name").text(e.find(".event-name").text()),this.modalHeader.find(".event-name-unit").text(e.find(".event-name-unit").text()),this.modalHeader.find(".event-date").text(e.find(".event-date").text()),this.modal.attr("data-event",e.parent().attr("data-event")),this.modalBody.find(".event-info").load(e.parent().attr("data-content")+".html .event-info > *",(function(e){d.element.addClass("content-loaded")})),this.modalBody.find(".event-car-id").html(e.find(".event-car-id").html()),this.modalBody.find(".event-id").html(e.find(".event-id").html()),this.modalBody.find(".event-unit").html(e.find(".event-unit").html()),this.modalBody.find(".event-ord-unit").html(e.find(".event-unit").html()),this.modalBody.find(".event-cty-name").html(e.find(".event-cty-name").html()),this.modalBody.find(".event-select").html(e.find(".event-select").html()),this.modalBody.find(".event-address").html(e.find(".event-address").html()),""==e.find(".event-note").html()?this.modalBody.find(".event-note").addClass("hidden"):(this.modalBody.find(".event-note").removeClass("hidden"),this.modalBody.find(".event-note-content").html(e.find(".event-note").html())),this.modalBody.find(".event-list-file").html((function(){var n=e.find(".event-list-file-path").text(),i=e.find(".event-list-file").text();if(""!=n||0!=i){var d=n.split(","),a=i.split(","),o="";return Array.prototype.associate=function(e){var t={};return this.forEach((function(n,i){t[e[i]]=n})),t},t.each(d.associate(a),((e,t)=>{o+='<div class="main-file"><div class="file-content"><div class="file-name">'+e+'</div><div class="file-action"><a href="https://drive.google.com/file/d/'+t+'/view"target="_blank" class="download-file"><i class="far fa-eye"></i></a></div></div></div>'})),'<div class="section-file">'+o+"</div>"}return"Không có danh sách"})),this.modalBody.find(".event-info-contact").html(e.find(".event-info-contact").html()),this.modalBody.find(".event-time").html(e.find(".event-time").html()),this.modalBody.find(".event-quantity").html(e.find(".event-quantity").html()),0!=e.find(".event-quantity-ktv").text()||""!=e.find(".event-note-ktv").text()?(this.modalBody.find(".order-quantity-ktv").addClass("hidden"),this.modalBody.find(".event-draft").removeClass("hidden"),this.modalBody.find(".event-draft").html(e.find(".event-quantity-ktv").html()+" Cas"),this.modalBody.find(".order_note_ktv").addClass("hidden"),this.modalBody.find(".event-noteKtv").removeClass("hidden"),this.modalBody.find(".event-noteKtv").html(e.find(".event-note-ktv").html()),this.modalBody.find(".submit-quantity-technologist").addClass("hidden")):(this.modalBody.find(".order-quantity-ktv").removeClass("hidden"),this.modalBody.find(".order_note_ktv").removeClass("hidden"),this.modalBody.find(".event-noteKtv").addClass("hidden"),this.modalBody.find(".event-draft").addClass("hidden"),this.modalBody.find(".submit-quantity-technologist").removeClass("hidden")),this.modalBody.find(".event-order-note").html(e.find(".event-order-note").html()),this.modalBody.find(".event-quantity").html(e.find(".event-quantity").html()),this.element.addClass("modal-is-open"),setTimeout((function(){e.parent("li").addClass("selected-event")}),10),"mobile"==a)d.modal.one(n,(function(){d.modal.off(n),d.animating=!1}));else{var o=e.offset().top-t(window).scrollTop(),s=e.offset().left,l=(e.innerHeight(),e.innerWidth()),m=t(window).width(),h=t(window).height(),c=.8*m>d.modalMaxWidth?d.modalMaxWidth:.8*m,f=.8*h>d.modalMaxHeight?d.modalMaxHeight:.8*h,v=parseInt((m-c)/2-s),u=parseInt((h-f)/2-o);d.modal.css({top:o+"px",left:s+"px",height:f+"px",width:c+"px"}),r(d.modal,"translateY("+u+"px) translateX("+v+"px)"),d.modalHeader.css({width:l+"px"}),d.modalBody.css({marginLeft:l+"px"}),d.modalHeaderBg.css({width:l+"px"}),d.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.animating=!1,d.element.addClass("animation-completed")}))}i||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.closeModal=function(e){var d=this,a=d.mq();if(this.animating=!0,"mobile"==a)this.element.removeClass("modal-is-open"),this.modal.one(n,(function(){d.modal.off(n),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}));else{var o=e.offset().top-t(window).scrollTop(),s=e.offset().left,l=e.innerHeight(),m=e.innerWidth(),h=Number(d.modal.css("top").replace("px","")),c=s-Number(d.modal.css("left").replace("px","")),f=o-h;d.element.removeClass("animation-completed modal-is-open"),this.modal.css({width:m+"px",height:l+"px"}),r(d.modal,"translateX("+c+"px) translateY("+f+"px)"),r(d.modalBodyBg,"scaleX(0) scaleY(1)"),r(d.modalHeaderBg,"scaleY(1)"),this.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.modal.addClass("no-transition"),setTimeout((function(){d.modal.add(d.modalHeader).add(d.modalBody).add(d.modalHeaderBg).add(d.modalBodyBg).attr("style","")}),10),setTimeout((function(){d.modal.removeClass("no-transition")}),20),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}))}i||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.mq=function(){return window.getComputedStyle(this.element.get(0),"::before").getPropertyValue("content").replace(/["']/g,"")},d.prototype.checkEventModal=function(e){this.animating=!0;var n=this,i=this.mq();if("mobile"==i)n.modal.add(n.modalHeader).add(n.modalHeaderBg).add(n.modalBody).add(n.modalBodyBg).attr("style",""),n.modal.removeClass("no-transition"),n.animating=!1;else if("desktop"==i&&n.element.hasClass("modal-is-open")){n.modal.addClass("no-transition"),n.element.addClass("animation-completed");var d=n.eventsGroup.find(".selected-event"),a=(d.offset().top,t(window).scrollTop(),d.offset().left,d.innerHeight(),d.innerWidth()),o=t(window).width(),s=t(window).height(),l=.8*o>n.modalMaxWidth?n.modalMaxWidth:.8*o,m=.8*s>n.modalMaxHeight?n.modalMaxHeight:.8*s;setTimeout((function(){n.modal.css({width:l+"px",height:m+"px",top:s/2-m/2+"px",left:o/2-l/2+"px"}),r(n.modal,"translateY(0) translateX(0)"),n.modalHeader.css({width:a+"px"}),n.modalBody.css({marginLeft:a+"px"}),n.modalHeaderBg.css({width:a+"px"})}),10),setTimeout((function(){n.modal.removeClass("no-transition"),n.animating=!1}),20)}};var a=t(".cd-schedule"),o=[],s=!1;function l(){o.forEach((function(e){e.scheduleReset()})),s=!1}function m(e){var t=(e=e.replace(/ /g,"")).split("/");return 60*parseInt(t[0])}function h(e){var t=(e=e.replace(/ /g,"")).split("/");return t[0]+"/"+t[1]}function r(e,t){e.css({"-moz-transform":t,"-webkit-transform":t,"-ms-transform":t,"-o-transform":t,transform:t})}a.length>0&&a.each((function(){o.push(new d(t(this)))})),t(window).on("resize",(function(){s||(s=!0,window.requestAnimationFrame?window.requestAnimationFrame(l):setTimeout(l))})),t(window).keyup((function(e){27==e.keyCode&&o.forEach((function(e){e.closeModal(e.eventsGroup.find(".selected-event"))}))}))}))}schedule(day),$(".select-month").on("change",(function(){var e=$(this).val(),t=$(".select-year").val();$(".loader-over").fadeIn(),$.ajax({url:url_select_technologist,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),year:t,month:e},success:function(e){schedule(e.day),$(".cd-schedule").html(e.html),$(".loader-over").fadeOut()}})})),$(".select-year").on("change",(function(){$(".define-month").prop("selected",!0)})),$(document).on("click",".submit-quantity-technologist",(function(){var e=$(".event-info").find(".event-id").text(),t=$(".order-quantity-ktv").val(),n=$(".order_note_ktv").val();$(".loader-over").fadeIn(),$.ajax({url:url_update_technologist,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),id:e,order_quantity_draft:t,order_note_ktv:n},success:function(){$(".event-modal").fadeOut(),$(".cover-layer").css({opacity:"0"}),$(".loader-over").fadeOut(),successMsg("Bạn đã cập nhật số Cas chụp thành công"),setTimeout((function(){location.reload()}),1e3)}})}));