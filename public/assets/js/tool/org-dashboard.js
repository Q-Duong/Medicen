// Trang Thống kê doanh thu (pages/admin/index): thẻ Doanh thu / Lợi nhuận / Số đơn / Số ca + biểu đồ Doanh thu & Lợi nhuận.
// Dùng các API sẵn có của StatisticsController (tháng này, tùy chọn, theo ngày, theo đơn vị).
// File nguồn (dễ đọc). Bản nạp lên trang là dashboard.js (rút gọn từ file này).
(function ($) {
    const root = document.getElementById('dashboard');
    if (!root) return;
    const CFG = JSON.parse(root.dataset.config || '{}');
    const token = () => $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    const money = (n) => new Intl.NumberFormat('vi-VN').format(Math.round(n || 0)) + ' đ';
    const short = (n) => {
        const a = Math.abs(n);
        if (a >= 1e9) return (n / 1e9).toFixed(1).replace('.', ',') + ' tỷ';
        if (a >= 1e6) return Math.round(n / 1e6) + ' tr';
        if (a >= 1e3) return Math.round(n / 1e3) + 'k';
        return String(n);
    };

    let chart = null;
    function draw(rows) {
        const el = document.getElementById('chart');
        const empty = root.querySelector('.ds-empty');
        if (!rows || !rows.length) {
            el.hidden = true;
            empty.hidden = false;
            return;
        }
        el.hidden = false;
        empty.hidden = true;
        const data = rows.map((r) => ({ period: r.period, sales: +r.sales || 0, profit: +r.profit || 0 }));
        if (!chart) {
            chart = new Morris.Bar({
                element: 'chart',
                data,
                xkey: 'period',
                ykeys: ['sales', 'profit'],
                labels: ['Doanh thu', 'Lợi nhuận'],
                barColors: ['#c96442', '#18794e'],
                barGap: 2,
                barSizeRatio: 0.7,
                gridTextColor: '#73716c',
                gridLineColor: '#efece5',
                gridTextSize: 11,
                hideHover: 'auto',
                resize: true,
                yLabelFormat: short,
                hoverCallback: (i, opts, content, row) =>
                    '<div class="ds-hover"><b>' + row.period + '</b><div>Doanh thu: ' + money(row.sales) + '</div><div>Lợi nhuận: ' + money(row.profit) + '</div></div>',
            });
        } else {
            chart.setData(data);
        }
    }

    function show(res, label) {
        if (!res || !res.success) {
            $('.ds-total, .ds-profit, .ds-orders, .ds-quantity').text('0');
            $('.ds-margin').text('');
            $('.filter-text, .chart-text').text(label || '');
            draw([]);
            return;
        }
        $('.ds-total').text(money(res.total));
        $('.ds-profit').text(money(res.total_profit));
        $('.ds-margin').text(res.total ? 'Tỷ suất ' + ((res.total_profit / res.total) * 100).toFixed(1).replace('.', ',') + '%' : '');
        $('.ds-orders').text(new Intl.NumberFormat('vi-VN').format(res.total_orders || 0) + ' đơn');
        $('.ds-quantity').text(new Intl.NumberFormat('vi-VN').format(res.total_quantity || 0) + ' ca');
        $('.filter-text, .chart-text').text(label);
        draw(res.chart);
    }

    function load(url, data, label) {
        root.classList.add('is-loading');
        $.ajax({ url, method: 'POST', dataType: 'JSON', data: Object.assign({ _token: token() }, data || {}) })
            .done((res) => show(res, label || res.option || ''))
            .fail(() => show(null, label))
            .always(() => root.classList.remove('is-loading'));
    }

    function setChip(opt) {
        root.querySelectorAll('.ds-chip').forEach((c) => c.classList.toggle('is-active', c.dataset.opt === opt));
    }

    $(root).on('click', '.ds-chip', function () {
        const opt = this.dataset.opt;
        setChip(opt);
        if (opt === 'month') load(CFG.month, {}, 'tháng này');
        else load(CFG.optional, { optional_revenue: opt }, this.textContent.trim().toLowerCase());
    });
    $(root).on('click', '.btn-revenue-by-date', function () {
        const from = $('input[name="from_date"]').val(), to = $('input[name="to_date"]').val();
        if (!from || !to) {
            if (window.uiAlert) window.uiAlert('Chọn đủ Từ ngày và Đến ngày.', { type: 'info' });
            return;
        }
        setChip(null);
        const fmt = (d) => d.split('-').reverse().join('/');
        load(CFG.date, { from_date: from, to_date: to }, 'từ ' + fmt(from) + ' đến ' + fmt(to));
    });
    $(root).on('click', '.btn-unit-revenue', function () {
        setChip(null);
        load(CFG.unit, { unit_id: $('.unit_id').val() }, 'đơn vị ' + $('.unit_id option:selected').text().trim());
    });

    $(function () {
        load(CFG.month, {}, 'tháng này');
    });
})(jQuery);
