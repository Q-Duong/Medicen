document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('appointmentForm');
    const submitBtn = document.getElementById('submitBtn');
    const ajaxMessage = document.getElementById('ajaxMessage');
    const btnText = submitBtn.innerHTML;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.ajax-error-text').forEach(el => el.remove());
        ajaxMessage.classList.add('d-none');

        submitBtn.disabled = true;
        submitBtn.innerHTML =
            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <span>Đang xử lý...</span>';
        let formData = new FormData(form);
        fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json().then(data => ({
                status: response.status,
                body: data
            })))
            .then(res => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnText;

                if (res.status === 422) {
                    let errors = res.body.errors;
                    for (let field in errors) {
                        let input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            input.insertAdjacentHTML('afterend',
                                `<span class="text-danger small ajax-error-text position-absolute mt-1" style="bottom: -22px; left: 15px;">${errors[field][0]}</span>`
                                );
                        }
                    }
                } else if (res.status === 200 && res.body.status === 'success') {
                    ajaxMessage.className = 'alert alert-success mb-4 fade show';
                    ajaxMessage.innerHTML =
                        `<i class="bi bi-check-circle-fill me-2"></i> ${res.body.message}`;
                    form.reset();
                } else {
                    ajaxMessage.className = 'alert alert-danger mb-4 fade show';
                    ajaxMessage.innerHTML =
                        `<i class="bi bi-exclamation-triangle-fill me-2"></i> ${res.body.message || 'Có lỗi xảy ra!'}`;
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnText;
                ajaxMessage.className = 'alert alert-danger mb-4 fade show';
                ajaxMessage.innerHTML =
                    `<i class="bi bi-wifi-off me-2"></i> Không thể kết nối tới máy chủ. Vui lòng kiểm tra lại mạng.`;
            });
    });
});

flatpickr("#appointmentDate", {
    locale: "vn",
    dateFormat: "Y-m-d",
    altInput: true,
    altFormat: "d/m/Y",
    minDate: "today",
    disableMobile: "true"
});