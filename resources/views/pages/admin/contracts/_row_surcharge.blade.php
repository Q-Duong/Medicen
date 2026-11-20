@php $m = fn ($n) => !empty($n) ? number_format((int) $n, 0, ',', '.') : ''; @endphp
<div class="hd-row hd-cols--sur">
    <input type="text" name="surcharge_rows[{{ $i }}][from_km]" value="{{ $s['from_km'] ?? '' }}" inputmode="numeric" aria-label="Từ km" placeholder="Từ km">
    <input type="text" name="surcharge_rows[{{ $i }}][to_km]" value="{{ $s['to_km'] ?? '' }}" inputmode="numeric" aria-label="Đến km" placeholder="Đến km">
    <input type="text" name="surcharge_rows[{{ $i }}][min_amount]" value="{{ $m($s['min_amount'] ?? null) }}" inputmode="numeric" data-hd-money aria-label="Từ đồng mỗi ngày" placeholder="Từ đ/ngày">
    <input type="text" name="surcharge_rows[{{ $i }}][max_amount]" value="{{ $m($s['max_amount'] ?? null) }}" inputmode="numeric" data-hd-money aria-label="Đến đồng mỗi ngày" placeholder="Đến đ/ngày">
    <label class="hd-row__vat"><input type="checkbox" name="surcharge_rows[{{ $i }}][vat_included]" value="1" @checked(!empty($s['vat_included']))><span>VAT</span></label>
    <button type="button" class="hd-del" data-hd-del title="Xóa dòng" aria-label="Xóa dòng"><i class="fas fa-times"></i></button>
</div>
