{{-- Giá theo hợp đồng khách hàng: org-order.js gọi order.contract_hint khi đổi đơn vị / ngày chụp / số lượng / tổng tiền. Chỉ nhắc, không chặn lưu. --}}
<div class="oc-hint" id="order-contract-hint" data-url="{{ route('order.contract_hint') }}" data-order-id="{{ $orderId ?? 0 }}" hidden aria-live="polite"></div>
<style>
    .oc-hint { margin: -4px 0 18px; padding: 11px 14px; border: 1px solid #e7e3da; border-radius: 12px; background: #faf9f5; color: #3a3936; font-size: 13.5px; line-height: 1.55; }
    .oc-hint.is-bad { border-color: #f3c6bf; background: #fdf1ef; }
    .oc-hint.is-ok { border-color: #b8e2cd; background: #f1faf5; }
    .oc-hint__head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 10px; font-weight: 700; }
    .oc-hint__head i { color: #c96442; }
    .oc-hint__head a { margin-left: auto; font-weight: 600; font-size: 12.5px; }
    .oc-hint__price { margin-top: 2px; color: #73716c; font-size: 12.5px; }
    .oc-hint__line { margin-top: 6px; }
    .oc-hint__line b.bad { color: #b42318; }
    .oc-hint__line b.ok { color: #18794e; }
    .oc-hint__line b.info { color: #0b5cad; }
    .oc-hint__sub { color: #73716c; font-size: 12.5px; }
    .oc-hint ul { margin: 4px 0 0; padding-left: 18px; }
    .oc-hint li + li { margin-top: 3px; }
    .oc-apply { margin-left: 6px; padding: 3px 10px; border: 1px solid #e7c3b4; border-radius: 8px; background: #fff; color: #8a3a1f; font-size: 12.5px; font-weight: 600; cursor: pointer; }
    .oc-hint__line > .oc-apply { margin-left: 0; }
    .oc-apply:hover { background: #fbefe9; }
    .oc-apply i { font-size: 11px; }
    .oc-filled { box-shadow: 0 0 0 3px rgba(201, 100, 66, .25) !important; transition: box-shadow .4s; }
</style>
