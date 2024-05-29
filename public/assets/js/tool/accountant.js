var typingTimer,doneTypingInterval=600;function quantityFunction(a){var t=a.target.name;$("input[name="+t+"]").on({keyup:function(){formatQuantity($(this))},input:function(){var a=t.split("_")[2],n=$("input[name="+t+"]").val(),e=$(".order_cost_"+a).val();if(""!=n&&""!=e){var o=e.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),c=parseInt(n)*parseInt(o),r=new Intl.NumberFormat("vi-VN").format(c);$(".order_price_"+a).val(r)}else $(".order_price_"+a).val(0)}})}function costFunction(a){var t=a.target.name;$("input[name="+t+"]").on({keyup:function(){formatCurrency($(this))},blur:function(){formatCurrency($(this),"blur")},input:function(){var a=t.split("_")[2],n=$(".order_quantity_"+a).val(),e=$("input[name="+t+"]").val();if(""!=n&&""!=e){var o=e.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),c=parseInt(n)*parseInt(o),r=new Intl.NumberFormat("vi-VN").format(c);$(".order_price_"+a).val(r)}else $(".order_price_"+a).val(0);var u=e.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");o=new Intl.NumberFormat("vi-VN").format(u);$(".order_cost_"+a).val(o)}})}function priceFunction(a){var t=a.target.name;$("input[name="+t+"]").on({keyup:function(){formatCurrency($(this))},blur:function(){formatCurrency($(this),"blur")},input:function(){var a=t.split("_")[2],n=$(this).val().replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),e=new Intl.NumberFormat("vi-VN").format(n);$(".order_price_"+a).val(e)}})}function amountPaidFunction(a){var t=a.target.name;$("input[name="+t+"]").on({keyup:function(){formatCurrency($(this))},blur:function(){formatCurrency($(this),"blur")},input:function(){var a=t.split("_")[3],n=$(".order_price_"+a).val(),e=$(this).val();if(""!=e&&""!=n){var o=n.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),c=e.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),r=parseInt(o)-parseInt(c),u=new Intl.NumberFormat("vi-VN").format(r);$(".accountant_owe_"+a).val(u)}else $(".accountant_owe_"+a).val(n);var l=e.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");c=new Intl.NumberFormat("vi-VN").format(l);$(".accountant_amount_paid_"+a).val(c)}})}function discountFunction(a){var t=a.target.name,n=t.split("_")[2];$("input[name="+t+"]").on({keyup:function(){formatCurrency($(this))},blur:function(){formatCurrency($(this),"blur")},input:function(){var a=$(".order_price_"+n).val(),t=$(this).val(),e=a.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");if(""!=t&&""!=a){var o=t.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),c=parseInt(e)-parseInt(o),r=new Intl.NumberFormat("vi-VN").format(c);$(".order_profit_"+n).val(r)}else $(".order_profit_"+n).val(a);var u=t.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");o=new Intl.NumberFormat("vi-VN").format(u);$(".order_discount_"+n).val(o)},click:function(){var a=$(".order_price_"+n).val(),t=$(this).val(),e=a.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");if(""!=t&&""!=a){var o=t.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,""),c=parseInt(e)-parseInt(o),r=new Intl.NumberFormat("vi-VN").format(c);$(".order_profit_"+n).val(r)}else $(".order_profit_"+n).val(a);var u=t.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"");o=new Intl.NumberFormat("vi-VN").format(u);$(".order_discount_"+n).val(o)}})}function ordFormFunction(a){var t=a.target.name,n=t.split("_")[2];$("input[name="+t+"]").on("keyup change",(function(){var a=$(this).val(),t=$(".accountant_35X43_"+n).val(),e=$(".order_quantity_"+n).val();if("ko in"==a){if(""==t)var o=0;else o=t;var c=4*parseInt(o);$(".accountant_film_bag_"+n).val(c)}else $(".accountant_film_bag_"+n).val(e)}))}function accountant35X43Function(a){var t=a.target.name,n=t.split("_")[2];$("input[name="+t+"]").on("keyup change",(function(){var a=$(this).val(),t=$(".ord_form_"+n).val(),e=$(".order_quantity_"+n).val();if("ko in"==t)if(""!=a){var o=4*parseInt(a);$(".accountant_film_bag_"+n).val(o)}else $(".accountant_film_bag_"+n).val(0);else $(".accountant_film_bag_"+n).val(e)}))}function deadlineFunction(a){var t=a.target.name,n=t.split("_")[2];$("input[name="+t+"]").on("keyup change click",(function(){var a=parseInt($(this).val()),t=$(".accountant_date_"+n).val();if(""!=t){var e=t.split("/"),o=e[2]+"-"+e[1]+"-"+e[0];const r=new Date(o),u=r.getDate()+a;r.setDate(u);var c=r.toLocaleDateString("en-GB",{day:"numeric",month:"numeric",year:"numeric"}).split(" ").join("-");$(".accountant_payment_"+n).val(c)}else $(".accountant_payment_"+n).val("")}))}function dateFunction(a){var t=a.target.name,n=t.split("_")[2];$("input[name="+t+"]").on("keyup change",(function(){var a=$(this).val(),t=$(".accountant_deadline_"+n).val();if(""!=t&&a.length>=10){var e=a.split("/"),o=e[2]+"-"+e[1]+"-"+e[0];const r=new Date(o),u=r.getDate()+parseInt(t);r.setDate(u);var c=r.toLocaleDateString("en-GB",{day:"numeric",month:"numeric",year:"numeric"}).split(" ").join("-");$(".accountant_payment_"+n).val(c)}else $(".accountant_payment_"+n).val("")}))}function getValues(a){return[{name:"accountant_id",value:$('input[name="accountant_id_'+a+'"]').val()},{name:"accountant_deadline",value:$('input[name="accountant_deadline_'+a+'"]').val()},{name:"accountant_number",value:$('input[name="accountant_number_'+a+'"]').val()},{name:"accountant_date",value:$('input[name="accountant_date_'+a+'"]').val()},{name:"order_vat",value:$('input[name="order_vat_'+a+'"]').val()},{name:"order_quantity",value:$('input[name="order_quantity_'+a+'"]').val()},{name:"order_cost",value:$('input[name="order_cost_'+a+'"]').val()},{name:"order_price",value:$('input[name="order_price_'+a+'"]').val()},{name:"accountant_status",value:$(".accountant_status_"+a).val()},{name:"accountant_day_payment",value:$('input[name="accountant_day_payment_'+a+'"]').val()},{name:"accountant_method",value:$(".accountant_method_"+a).val()},{name:"accountant_amount_paid",value:$('input[name="accountant_amount_paid_'+a+'"]').val()},{name:"accountant_owe",value:$('input[name="accountant_owe_'+a+'"]').val()},{name:"order_percent_discount",value:$('input[name="order_percent_discount_'+a+'"]').val()},{name:"order_discount",value:$('input[name="order_discount_'+a+'"]').val()},{name:"accountant_discount_day",value:$('input[name="accountant_discount_day_'+a+'"]').val()},{name:"order_profit",value:$('input[name="order_profit_'+a+'"]').val()},{name:"accountant_doctor_read",value:$('input[name="accountant_doctor_read_'+a+'"]').val()},{name:"accountant_doctor_date_payment",value:$('input[name="accountant_doctor_date_payment_'+a+'"]').val()},{name:"accountant_35X43",value:$('input[name="accountant_35X43_'+a+'"]').val()},{name:"accountant_polime",value:$('input[name="accountant_polime_'+a+'"]').val()},{name:"accountant_8X10",value:$('input[name="accountant_8X10_'+a+'"]').val()},{name:"accountant_10X12",value:$('input[name="accountant_10X12_'+a+'"]').val()},{name:"accountant_film_bag",value:$('input[name="accountant_film_bag_'+a+'"]').val()},{name:"accountant_note",value:$('input[name="accountant_note_'+a+'"]').val()}]}function getValuesFilter(){return[{name:"order_id",value:$(".order-id").val()},{name:"status_id",value:$(".status-id").val()},{name:"car_name",value:$(".car-name").val()},{name:"unit_code",value:$(".unit-code").val()},{name:"unit_name",value:$(".unit-name").val()},{name:"ord_start_day",value:$(".ord-start-day").val()},{name:"ord_cty_name",value:$(".ord-cty-name").val()},{name:"ord_form",value:$(".ord-form").val()},{name:"accountant_month",value:$(".accountant-month").val()},{name:"accountant_distance",value:$(".accountant-distance").val()},{name:"accountant_deadline",value:$(".accountant-deadline").val()},{name:"accountant_number",value:$(".accountant-number").val()},{name:"accountant_date",value:$(".accountant-date").val()},{name:"accountant_status",value:$(".accountant-status").val()},{name:"accountant_day_payment",value:$(".accountant-day-payment").val()},{name:"accountant_method",value:$(".accountant-method").val()},{name:"accountant_amount_paid",value:$(".accountant-amount-paid").val()},{name:"accountant_owe",value:$(".accountant-owe").val()},{name:"accountant_discount_day",value:$(".accountant-discount-day").val()},{name:"accountant_doctor_read",value:$(".accountant-doctor-read").val()},{name:"accountant_doctor_date_payment",value:$(".accountant-doctor-date-payment").val()},{name:"accountant_35X43",value:$(".accountant-35X43").val()},{name:"accountant_polime",value:$(".accountant-polime").val()},{name:"accountant_8X10",value:$(".accountant-8X10").val()},{name:"accountant_10X12",value:$(".accountant-10X12").val()},{name:"accountant_film_bag",value:$(".accountant-film-bag").val()},{name:"order_vat",value:$(".order-vat").val()},{name:"order_quantity",value:$(".order-quantity").val()},{name:"order_cost",value:$(".order-cost").val()},{name:"order_price",value:$(".order-price").val()},{name:"order_percent_discount",value:$(".order-percent-discount").val()},{name:"order_discount",value:$(".order-discount").val()},{name:"order_profit",value:$(".order-profit").val()}]}function getListAccountant(a){$.ajax({url:url_get_accountant,method:"POST",async:!0,headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:{year:a},beforeSend:function(){}}).then((function(a){$(".table-content").html(a.html);var t=new Intl.NumberFormat("vi-VN").format(a.totalPrice),n=new Intl.NumberFormat("vi-VN").format(a.totalOwe),e=new Intl.NumberFormat("vi-VN").format(a.totalAmountPaid),o=new Intl.NumberFormat("vi-VN").format(a.totalQuantity),c=new Intl.NumberFormat("vi-VN").format(a.totalDiscount);$("#total-price").text(t),$("#total-owe").text(n),$("#total-amount-paid").text(e),$("#total-quantity").text(o),$("#total-discount").text(c)})).always((function(){$(".loader-over").fadeOut()}))}$(document).ready((function(){setTimeout((function(){getListAccountant()}),1e3)})),$(".order_profit").on({keyup:function(){formatCurrency($(this))},blur:function(){formatCurrency($(this),"blur")},input:function(){var a=$(this).val();if(""==a){var t=0;$(".order_profit").val(t)}else t=a.replace(/\D/g,"").replace(/\B(?=(\d{3})+(?!\d))/g,"")}}),$(".year-filter").on("change",(function(){$(".loader-over").fadeIn(),getListAccountant($(this).val())})),$(document).on("change",".accountant_status",(function(){0==$(this).val()?$(this).removeClass("acc-status-paid").addClass("acc-status-unpaid"):$(this).removeClass("acc-status-unpaid").addClass("acc-status-paid")})),$(document).on("change",".order-id, .accountant-month, .ord-start-day, .car-name, .accountant-distance, .unit-code, .unit-name, .ord-cty-name, .accountant-deadline, .accountant-number, .accountant-date, .order-vat, .order-quantity, .order-cost, .order-price, .accountant-status, .accountant-day-payment, .accountant-method, .accountant-amount-paid, .accountant-owe, .order-percent-discount, .order-discount, .accountant-discount-day, .order-profit, .accountant-doctor-read, .accountant-doctor-date-payment, .ord-form, .accountant-35X43, .accountant-polime, .accountant-8X10, .accountant-10X12, .accountant-film-bag, .status-id",(function(){var a=getValuesFilter();$(".loader-over").fadeIn(),$.ajax({url:url_filter_accountant,method:"POST",headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:a,success:function(a){var t=new Intl.NumberFormat("vi-VN").format(a.totalPrice),n=new Intl.NumberFormat("vi-VN").format(a.totalOwe),e=new Intl.NumberFormat("vi-VN").format(a.totalAmountPaid),o=new Intl.NumberFormat("vi-VN").format(a.totalQuantity),c=new Intl.NumberFormat("vi-VN").format(a.totalDiscount);$(".clear-filter").removeClass("hidden"),$(".tbody-content").html(a.html),$("#total-price").text(t),$("#total-owe").text(n),$("#total-amount-paid").text(e),$("#total-quantity").text(o),$("#total-discount").text(c),$(".loader-over").fadeOut()}})})),$(document).on("click",".btn-clear-filter",(function(){$(".loader-over").fadeIn(),getListAccountant(),$(".clear-filter").addClass("hidden")})),$(document).on("click",".completeAccount",(function(){var a=$(this).data("id"),t=getValues(a);t.push({name:"order_id",value:a}),$(".loader-over").fadeIn(),$.ajax({url:url_complete_accountant,method:"Patch",headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:t,success:function(t){$(".status_id_"+a).html('<span style="color: #0071e3;">Đã xử lý</span>'),$(".update-account-"+a).html(""),$(".loader-over").fadeOut(),successMsg(t.success)}})})),$(document).on("change","input[type=text], .select-update",(function(){var a=$(this).attr("name").split("_"),t=a.pop(),n=getValues(t);n.push({name:"order_id",value:t},{name:"currentChange",value:a.join("_")}),$.ajax({url:url_update_accountant,method:"Patch",headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:n,success:function(a){void 0!==a.html&&null!==a.html&&(console.log(a.multi),a.multi?($("."+a.className).html(a.html),$("."+a.subClassName).html(a.subHtml)):$("."+a.className).html(a.html)),$(".status_id_"+t).html('<span style="color: #00d0e3;">Đã cập nhật doanh thu</span>')}})}));