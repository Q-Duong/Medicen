const currentTime=JSON.parse(localStorage.getItem("currentTime"))||[],currentLocation=window.location.pathname;function getValues(){return[{name:"order_quantity_details",value:$(".order-quantity").val()},{name:"accountant_doctor_read",value:$(".accountant-doctor-read").val()},{name:"accountant_35X43",value:$(".accountant-35X43").val()},{name:"accountant_polime",value:$(".accountant-polime").val()},{name:"accountant_8X10",value:$(".accountant-8X10").val()},{name:"accountant_10X12",value:$(".accountant-10X12").val()},{name:"accountant_note",value:$(".accountant-note").val()},{name:"ord_delivery_date",value:$(".ord-delivery-date").val()},{name:"ord_total_file_name",value:$("input[name=ord_total_file_name]").val()}]}function pushCurrentTime(e,t){var n={month:e,year:t};localStorage.setItem("currentTime",JSON.stringify(n))}function schedule(e){jQuery(document).ready((function(t){var n="webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend",a=t(".csstransitions").length>0;function d(e){this.element=e,this.timeline=this.element.find(".timeline"),this.timelineItems=this.timeline.find("li"),this.timelineItemsNumber=this.timelineItems.length,this.timelineStart=m(this.timelineItems.eq(0).text()),this.timelineUnitDuration=m(this.timelineItems.eq(1).text())-m(this.timelineItems.eq(0).text()),this.eventsWrapper=this.element.find(".events"),this.eventsGroup=this.eventsWrapper.find(".events-group"),this.singleEvents=this.eventsGroup.find(".single-event"),this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.modal=this.element.find(".event-modal"),this.modalHeader=this.modal.find(".header"),this.modalHeaderBg=this.modal.find(".header-bg"),this.modalBody=this.modal.find(".body"),this.modalBodyBg=this.modal.find(".body-bg"),this.modalMaxWidth=900,this.modalMaxHeight=640,this.animating=!1,this.initSchedule()}a||(n="noTransition"),d.prototype.initSchedule=function(){this.scheduleReset(),this.initEvents()},d.prototype.scheduleReset=function(){var t=this.mq();"desktop"==t?(this.eventSlotHeight=this.eventsGroup.eq(0).children(".top-info").outerHeight(),this.element.addClass("js-full"),this.placeEvents(),this.element.hasClass("modal-is-open")&&this.checkEventModal(),30==e?this.eventsGroup.children("ul").css({height:"3000px"}):this.eventsGroup.children("ul").css({height:"3100px"})):"mobile"==t?(this.element.removeClass("js-full loading"),this.eventsGroup.children("ul").add(this.singleEvents).removeAttr("style"),this.eventsWrapper.children(".grid-line").remove(),this.element.hasClass("modal-is-open")&&this.checkEventModal()):"desktop"==t&&this.element.hasClass("modal-is-open")?(this.checkEventModal("desktop"),this.element.removeClass("loading")):this.element.removeClass("loading")},d.prototype.initEvents=function(){var e=this;this.singleEvents.each((function(){var n='<span class="event-date">Ngày chụp: '+h(t(this).data("start"))+" - "+h(t(this).data("end"))+"</span>";t(this).children("a").prepend(t(n)),t(this).on("click","a",(function(n){n.preventDefault(),e.animating||e.openModal(t(this))}))})),this.modal.on("click",".close",(function(t){t.preventDefault(),e.animating||e.closeModal(e.eventsGroup.find(".selected-event"))})),this.element.on("click",".cover-layer",(function(t){!e.animating&&e.element.hasClass("modal-is-open")&&e.closeModal(e.eventsGroup.find(".selected-event"))}))},d.prototype.placeEvents=function(){var e=this,n=0;this.singleEvents.each((function(){var a=m(t(this).attr("data-start")),d=m(t(this).attr("data-end")),i=t(this).attr("data-child");1==i?t(this).css({left:"0px"}):2==i?t(this).css({left:"23px"}):t(this).css({right:"0px"}),n=a==d?60:d-a+60;var o=e.eventSlotHeight*(a-e.timelineStart)/e.timelineUnitDuration,s=e.eventSlotHeight*n/e.timelineUnitDuration;t(this).css({top:o-1+"px",height:s+1+"px"})})),this.element.removeClass("loading")},d.prototype.openModal=function(e){var d=this,i=d.mq();new Date,new Date(e.find(".event-start-day").html());this.animating=!0,this.modalHeader.find(".event-name-id").text(e.find(".event-name-id").text()),this.modalHeader.find(".event-name").text(e.find(".event-name").text()),this.modalHeader.find(".event-name-unit").text(e.find(".event-name-unit").text()),this.modalHeader.find(".event-date").text(e.find(".event-date").text()),this.modal.attr("data-event",e.parent().attr("data-event")),this.modalBody.find(".event-info").load(e.parent().attr("data-content")+".html .event-info > *",(function(e){d.element.addClass("content-loaded")})),this.modalBody.find(".event-car-id").html(e.find(".event-car-id").html()),this.modalBody.find(".event-id").html(e.find(".event-id").html()),this.modalBody.find(".event-unit").html(e.find(".event-unit").html()),this.modalBody.find(".event-cty-name").html(e.find(".event-cty-name").html()),this.modalBody.find(".event-address").html(e.find(".event-address").html()),this.modalBody.find(".event-note-content").html(e.find(".event-note").html()),this.modalBody.find(".event-select").html(e.find(".event-select").html()),this.modalBody.find(".event-list-file").html((function(){var n=e.find(".event-list-file-path").text(),a=e.find(".event-list-file").text();if(""!=n||0!=a){var d=n.split(","),i=a.split(","),o="";return Array.prototype.associate=function(e){var t={};return this.forEach((function(n,a){t[e[a]]=n})),t},t.each(d.associate(i),((e,t)=>{o+='<div class="main-file"><div class="file-content"><div class="file-name">'+e+'</div><div class="file-action"><a href="https://drive.google.com/file/d/'+t+'/view"target="_blank" class="download-file"><i class="far fa-eye"></i></a></div></div></div>'})),'<div class="section-file">'+o+"</div>"}return"Không có danh sách"})),this.modalBody.find(".event-info-contact").html(e.find(".event-info-contact").html()),this.modalBody.find(".event-time").html(e.find(".event-time").html()),this.modalBody.find(".event-quantity").html(e.find(".event-quantity").html()),this.modalBody.find(".event-doctor-read").html(e.find(".event-doctor-read").html()),this.modalBody.find(".event-film").html(e.find(".event-film").html()),this.modalBody.find(".event-form").html(e.find(".event-form").html()),this.modalBody.find(".event-print").html(e.find(".event-print").html()),this.modalBody.find(".event-form-print").html(e.find(".event-form-print").html()),this.modalBody.find(".event-print-result").html(e.find(".event-print-result").html()),this.modalBody.find(".event-film-sheet").html(e.find(".event-film-sheet").html()),this.modalBody.find(".event-order-note").html(e.find(".event-order-note").html()),this.modalBody.find(".event-ord-warning").html(e.find(".event-warning").html()),this.modalBody.find(".event-deadline").html(e.find(".event-deadline").html()),this.modalBody.find(".event-deliver-results").html(e.find(".event-deliver-results").html()),this.modalBody.find(".event-email").html(e.find(".event-email").html()),1==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="processing">Đang xử lý</span>'):2==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="updated">Đã cập nhật số Cas thực tế</span>'):4==e.find(".event-status").html()?this.modalBody.find(".event-status").html('<span class="update-acc">Đã cập nhật doanh thu</span>'):this.modalBody.find(".event-status").html('<span class="processed">Đã xử lý</span>'),this.modalBody.find(".event-draft").html(e.find(".event-quantity-draft").html()+" Cas"),this.modalBody.find(".event-noteKtv").html(e.find(".event-note-ktv").html());var o=e.find(".event-total-file-path").text(),s=e.find(".event-total-file").text();if(""!=o||""!=s?(this.modalBody.find(".total-file").addClass("hidden"),this.modalBody.find(".event-total-file").html((function(){var t=e.find(".event-details-id").html();return'<div class="section-file">'+('<input type="hidden" name="path" value="'+o+'"><input type="hidden" name="file" value="'+s+'"><input type="hidden" name="id" value="'+t+'"><div class="main-file"><div class="file-content"><div class="file-name">'+s+'</div><div class="file-action"><a href="https://drive.google.com/file/d/'+o+'/view"target="_blank" class="download-file"><i class="far fa-eye"></i></a><button class="delete-file del-total-file" type="button"><i class="fas fa-times"></i></button></div></div></div>')+"</div>"}))):(this.modalBody.find(".total-file").removeClass("hidden"),this.modalBody.find(".event-total-file").html("")),3==e.find(".event-status").text()?(this.modalBody.find(".event-accountant-doctor-read").html(e.find(".event-accountant-doctor-read").text()),this.modalBody.find(".accountant-doctor-read").addClass("hidden"),this.modalBody.find(".event-35X43").html(e.find(".event-35X43").text()),this.modalBody.find(".accountant-35X43").addClass("hidden"),this.modalBody.find(".event-polime").html(e.find(".event-polime").text()),this.modalBody.find(".accountant-polime").addClass("hidden"),this.modalBody.find(".event-8X10").html(e.find(".event-8X10").text()),this.modalBody.find(".accountant-8X10").addClass("hidden"),this.modalBody.find(".event-10X12").html(e.find(".event-10X12").text()),this.modalBody.find(".accountant-10X12").addClass("hidden"),this.modalBody.find(".event-accountant-note").html(e.find(".event-accountant-note").text()),this.modalBody.find(".accountant-note").addClass("hidden"),this.modalBody.find(".event-delivery-date").html(e.find(".event-delivery-date").text()),this.modalBody.find(".ord-delivery-date").addClass("hidden"),this.modalBody.find(".order-quantity").addClass("hidden"),this.modalBody.find(".submit-quantity-details").addClass("hidden"),this.modalBody.find(".event-quantity-details").removeClass("hidden"),this.modalBody.find(".event-quantity-details").html(e.find(".event-quantity").html()),this.modalBody.find(".total-file").addClass("hidden")):(this.modalBody.find(".event-accountant-doctor-read").html(""),this.modalBody.find(".accountant-doctor-read").removeClass("hidden"),"Nhân"==e.find(".event-accountant-doctor-read").html()?(this.modalBody.find(".doctor-N").prop("selected",!0),this.modalBody.find(".doctor-T").prop("selected",!1),this.modalBody.find(".doctor-G").prop("selected",!1),this.modalBody.find(".doctor-empty").prop("selected",!1)):"Trung"==e.find(".event-accountant-doctor-read").html()?(this.modalBody.find(".doctor-T").prop("selected",!0),this.modalBody.find(".doctor-N").prop("selected",!1),this.modalBody.find(".doctor-G").prop("selected",!1),this.modalBody.find(".doctor-empty").prop("selected",!1)):"Giang"==e.find(".event-accountant-doctor-read").html()?(this.modalBody.find(".doctor-G").prop("selected",!0),this.modalBody.find(".doctor-T").prop("selected",!1),this.modalBody.find(".doctor-N").prop("selected",!1),this.modalBody.find(".doctor-empty").prop("selected",!1)):(this.modalBody.find(".doctor-empty").prop("selected",!0),this.modalBody.find(".doctor-G").prop("selected",!1),this.modalBody.find(".doctor-N").prop("selected",!1),this.modalBody.find(".doctor-T").prop("selected",!1)),this.modalBody.find(".accountant-35X43").removeClass("hidden").val(e.find(".event-35X43").text()),this.modalBody.find(".event-35X43").html(""),this.modalBody.find(".accountant-polime").removeClass("hidden").val(e.find(".event-polime").text()),this.modalBody.find(".event-polime").html(""),this.modalBody.find(".accountant-8X10").removeClass("hidden").val(e.find(".event-8X10").text()),this.modalBody.find(".event-8X10").html(""),this.modalBody.find(".accountant-10X12").removeClass("hidden").val(e.find(".event-10X12").text()),this.modalBody.find(".event-10X12").html(""),this.modalBody.find(".accountant-note").removeClass("hidden").val(e.find(".event-accountant-note").text()),this.modalBody.find(".event-accountant-note").html(""),this.modalBody.find(".ord-delivery-date").removeClass("hidden").val(e.find(".event-delivery-date").text()),this.modalBody.find(".event-delivery-date").html(""),this.modalBody.find(".order-quantity").removeClass("hidden").val(e.find(".event-quantity").text()),this.modalBody.find(".submit-quantity-details").removeClass("hidden"),this.modalBody.find(".event-quantity-details").addClass("hidden")),this.element.addClass("modal-is-open"),setTimeout((function(){e.parent("li").addClass("selected-event")}),10),"mobile"==i)d.modal.one(n,(function(){d.modal.off(n),d.animating=!1}));else{var l=e.offset().top-t(window).scrollTop(),m=e.offset().left,h=(e.innerHeight(),e.innerWidth()),c=t(window).width(),f=t(window).height(),v=.8*c>d.modalMaxWidth?d.modalMaxWidth:.8*c,u=.8*f>d.modalMaxHeight?d.modalMaxHeight:.8*f,p=parseInt((c-v)/2-m),y=parseInt((f-u)/2-l);d.modal.css({top:l+"px",left:m+"px",height:u+"px",width:v+"px"}),r(d.modal,"translateY("+y+"px) translateX("+p+"px)"),d.modalHeader.css({width:h+"px"}),d.modalBody.css({marginLeft:h+"px"}),d.modalHeaderBg.css({width:h+"px"}),d.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.animating=!1,d.element.addClass("animation-completed")}))}a||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.closeModal=function(e){var d=this,i=d.mq();if(this.animating=!0,"mobile"==i)this.element.removeClass("modal-is-open"),this.modal.one(n,(function(){d.modal.off(n),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}));else{var o=e.offset().top-t(window).scrollTop(),s=e.offset().left,l=e.innerHeight(),m=e.innerWidth(),h=Number(d.modal.css("top").replace("px","")),c=s-Number(d.modal.css("left").replace("px","")),f=o-h;d.element.removeClass("animation-completed modal-is-open"),this.modal.css({width:m+"px",height:l+"px"}),r(d.modal,"translateX("+c+"px) translateY("+f+"px)"),r(d.modalBodyBg,"scaleX(0) scaleY(1)"),r(d.modalHeaderBg,"scaleY(1)"),this.modalHeaderBg.one(n,(function(){d.modalHeaderBg.off(n),d.modal.addClass("no-transition"),setTimeout((function(){d.modal.add(d.modalHeader).add(d.modalBody).add(d.modalHeaderBg).add(d.modalBodyBg).attr("style","")}),10),setTimeout((function(){d.modal.removeClass("no-transition")}),20),d.animating=!1,d.element.removeClass("content-loaded"),e.removeClass("selected-event")}))}a||d.modal.add(d.modalHeaderBg).trigger(n)},d.prototype.mq=function(){return window.getComputedStyle(this.element.get(0),"::before").getPropertyValue("content").replace(/["']/g,"")},d.prototype.checkEventModal=function(e){this.animating=!0;var n=this,a=this.mq();if("mobile"==a)n.modal.add(n.modalHeader).add(n.modalHeaderBg).add(n.modalBody).add(n.modalBodyBg).attr("style",""),n.modal.removeClass("no-transition"),n.animating=!1;else if("desktop"==a&&n.element.hasClass("modal-is-open")){n.modal.addClass("no-transition"),n.element.addClass("animation-completed");var d=n.eventsGroup.find(".selected-event"),i=(d.offset().top,t(window).scrollTop(),d.offset().left,d.innerHeight(),d.innerWidth()),o=t(window).width(),s=t(window).height(),l=.8*o>n.modalMaxWidth?n.modalMaxWidth:.8*o,m=.8*s>n.modalMaxHeight?n.modalMaxHeight:.8*s;setTimeout((function(){n.modal.css({width:l+"px",height:m+"px",top:s/2-m/2+"px",left:o/2-l/2+"px"}),r(n.modal,"translateY(0) translateX(0)"),n.modalHeader.css({width:i+"px"}),n.modalBody.css({marginLeft:i+"px"}),n.modalHeaderBg.css({width:i+"px"})}),10),setTimeout((function(){n.modal.removeClass("no-transition"),n.animating=!1}),20)}};var i=t(".cd-schedule"),o=[],s=!1;function l(){o.forEach((function(e){e.scheduleReset()})),s=!1}function m(e){var t=(e=e.replace(/ /g,"")).split("/");return 60*parseInt(t[0])}function h(e){var t=(e=e.replace(/ /g,"")).split("/");return t[0]+"/"+t[1]}function r(e,t){e.css({"-moz-transform":t,"-webkit-transform":t,"-ms-transform":t,"-o-transform":t,transform:t})}i.length>0&&i.each((function(){o.push(new d(t(this)))})),t(window).on("resize",(function(){s||(s=!0,window.requestAnimationFrame?window.requestAnimationFrame(l):setTimeout(l))})),t(window).keyup((function(e){27==e.keyCode&&o.forEach((function(e){e.closeModal(e.eventsGroup.find(".selected-event"))}))}))}))}$.ajax({url:url_get_schedule,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),currentTime:currentTime},success:function(e){$(".schedule-render").html(e.html),schedule(e.day)}}),$(document).on("change",".select-month",(function(){var e=$(this).val(),t=$(".select-year").val();$(".schedule-search").removeClass("search-show").val(""),$(".btn-search").html('<button class="btn-schedule-search"><i class="fas fa-search"></i></button>'),$(".search-results").removeClass("search-results-show").html(""),$(".loader-over").fadeIn(),$.ajax({url:url_select_details,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),year:t,month:e},success:function(n){pushCurrentTime(e,t),schedule(n.day),$(".schedule").html(n.html),$(".loader-over").fadeOut()}})})),$(document).on("click",".btn-schedule-search",(function(){$("input[name=search-keywords]").focus(),$(".schedule-search").addClass("search-show"),$(".btn-search").html('<button class="btn-close-search"><i class="fas fa-times"></i></button>'),$(".search-results").addClass("search-results-show")})),$(document).on("click",".btn-close-search",(function(){var e=$(".select-month").val(),t=$(".select-year").val();$(".schedule-search").removeClass("search-show").val(""),$(".btn-search").html('<button class="btn-schedule-search"><i class="fas fa-search"></i></button>'),$(".search-results").removeClass("search-results-show").html(""),$(".loader-over").fadeIn(),$.ajax({url:url_select_details,method:"POST",data:{_token:$('meta[name="csrf-token"]').attr("content"),year:t,month:e},success:function(e){schedule(e.day),$(".schedule").html(e.html),$(".loader-over").fadeOut()}})})),$(document).on("keyup",".schedule-search",(function(){var e=$(this).val(),t=JSON.parse(localStorage.getItem("currentTime"))||[];""!=e?$.ajax({url:url_search_suggest,method:"POST",data:{currentTime:t,query:e,_token:$('meta[name="csrf-token"]').attr("content")},success:function(e){$(".search-results").addClass("search-results-show").html(e.html)}}):$(".search-results").removeClass("search-results-show").html("")})),$(document).on("click",".li_search",(function(){var e=$(this).text(),t=JSON.parse(localStorage.getItem("currentTime"))||[];$(".loader-over").fadeIn(),$(".search-results").removeClass("search-results-show").html(""),$(".schedule-search").val(e),$.ajax({url:url_schedule_search,method:"POST",data:{currentTime:t,param:e,_token:$('meta[name="csrf-token"]').attr("content")},success:function(e){schedule(e.day),$(".schedule").html(e.html),$(".loader-over").fadeOut()}})})),$(document).on("change",".select-year",(function(){$(".define-month").prop("selected",!0)})),$(document).on("click",".submit-quantity-details",(function(){var e=getValues();e.push({name:"id",value:$(".event-info").find(".event-id").text()},{name:"_token",value:$('meta[name="csrf-token"]').attr("content")}),$(".loader-over").fadeIn(),$.ajax({url:url_update_details,method:"POST",data:e,success:function(){$(".event-modal").fadeOut(),$(".cover-layer").css({opacity:"0"}),$(".loader-over").fadeOut(),successMsg("Bạn đã cập nhật số Cas chụp thành công"),setTimeout((function(){location.reload()}),1e3)}})}));