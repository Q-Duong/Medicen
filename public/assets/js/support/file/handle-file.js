function deleteFileOrder(e,t,l){confirm("Do you want to delete this file?")&&($(".loader-over").fadeIn(),$(".button-submit").attr("disabled",!0),$.ajax({url:url_file_delete_order,type:"DELETE",headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:{name:e,path:t,id:l},success:function(e){e.html?$(".section-file").html(e.html):$(".section-file").html("").addClass("hidden"),$(".loader-over").fadeOut(),$(".button-submit").removeAttr("disabled"),successMsg(e.message)}}))}FilePond.registerPlugin(FilePondPluginImagePreview),FilePond.setOptions({server:{headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},process:url_file_process,revert:url_file_revert}}),FilePond.create(document.querySelector('input[type="file"]'),{labelIdle:'Kéo và thả tập tin của bạn hoặc <span class="filepond--label-action">Trình duyệt</span>',credits:!1,onaddfilestart:()=>{$(".button-submit").attr("disabled",!0)},onprocessfile:()=>{$(".button-submit").removeAttr("disabled")}}),"undefined"!=typeof files&&FilePond.setOptions({files:files}),$(document).on("click",".del-total-file",(function(){var e=$("input[name=file]").val(),t=$("input[name=path]").val(),l=$("input[name=id]").val();$(".loader-over").fadeIn(),$.ajax({url:url_file_delete_total,method:"DELETE",headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},data:{name:e,path:t,id:l},success:function(){$(".loader-over").fadeOut(),$(".event-total-file").html(""),$(".total-file").removeClass("hidden"),successMsg("Xoá file thành công")}})}));