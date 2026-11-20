@php $m = fn ($n) => !empty($n) ? number_format((int) $n, 0, ',', '.') : ''; @endphp
<div class="hd-row hd-cols--tier">
    <input type="text" name="tiers[{{ $i }}][min_cases]" value="{{ $t['min_cases'] ?? '' }}" inputmode="numeric" aria-label="Từ ca" placeholder="Từ ca (0)">
    <input type="text" name="tiers[{{ $i }}][max_cases]" value="{{ $t['max_cases'] ?? '' }}" inputmode="numeric" aria-label="Đến ca" placeholder="Đến ca (trở lên)">
    <select name="tiers[{{ $i }}][unit]" aria-label="Tính theo">
        <option value="ca" @selected(($t['unit'] ?? 'ca') === 'ca')>đ / ca</option>
        <option value="ngay" @selected(($t['unit'] ?? '') === 'ngay')>trọn gói / ngày</option>
    </select>
    <input type="text" name="tiers[{{ $i }}][price]" value="{{ $m($t['price'] ?? null) }}" inputmode="numeric" data-hd-money aria-label="Giá" placeholder="Giá (đ)">
    <button type="button" class="hd-del" data-hd-del title="Xóa dòng" aria-label="Xóa dòng"><i class="fas fa-times"></i></button>
</div>
