const inputElementImage=document.querySelector("input.filepond-image"),inputElementCard=document.querySelector("input.filepond-card"),inputElementFile=document.querySelector("input.filepond-file");"undefined"!=typeof image&&FilePond.create(inputElementImage,{labelIdle:image,credits:!1,onaddfilestart:()=>{$(".button-submit").attr("disabled",!0)},onprocessfile:()=>{$(".button-submit").removeAttr("disabled")}}),"undefined"!=typeof card&&FilePond.create(inputElementCard,{labelIdle:card,credits:!1,onaddfilestart:()=>{$(".button-submit").attr("disabled",!0)},onprocessfile:()=>{$(".button-submit").removeAttr("disabled")}}),"undefined"!=typeof file&&FilePond.create(inputElementFile,{labelIdle:file,credits:!1,onaddfilestart:()=>{$(".button-submit").attr("disabled",!0)},onprocessfile:()=>{$(".button-submit").removeAttr("disabled")}}),FilePond.setOptions({server:{headers:{"X-CSRF-TOKEN":$('meta[name="csrf-token"]').attr("content")},process:url_file_upload,revert:url_file_revert}});