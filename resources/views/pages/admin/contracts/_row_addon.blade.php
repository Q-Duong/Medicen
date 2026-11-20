@php $m = fn ($n) => isset($n) && $n !== '' ? number_format((int) $n, 0, ',', '.') : ''; @endphp
<div class="hd-row hd-cols--addon">
    <input type="text" name="addon_rows[{{ $i }}][name]" value="{{ $a['name'] ?? '' }}" maxlength="120" aria-label="Khoản" placeholder="Vd: Phim 20x25">
    <input type="text" name="addon_rows[{{ $i }}][price_per_case]" value="{{ $m($a['price_per_case'] ?? null) }}" inputmode="numeric" data-hd-money aria-label="Đồng mỗi ca" placeholder="đ / ca">
    <button type="button" class="hd-del" data-hd-del title="Xóa dòng" aria-label="Xóa dòng"><i class="fas fa-times"></i></button>
</div>
