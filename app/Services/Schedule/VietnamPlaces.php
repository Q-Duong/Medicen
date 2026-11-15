<?php

namespace App\Services\Schedule;

/**
 * Nhận diện tỉnh/thành (và quận/huyện của TP.HCM, một số thành phố lớn) từ địa chỉ chữ,
 * trả về toạ độ gần đúng của trung tâm để ước tính quãng đường. Miễn phí, không gọi dịch vụ ngoài.
 *
 * Toạ độ là trung tâm tỉnh lỵ / quận, sai số vài km; đủ để biết "hôm trước ở Cà Mau, sáng hôm sau
 * chụp ở TP.HCM có kịp không", không dùng để tính chính xác trong nội thành.
 * Sau sáp nhập tỉnh 2025 tên tỉnh mới vẫn dùng lại tên cũ nên vẫn nhận diện được.
 */
class VietnamPlaces
{
    /**
     * Địa danh cụ thể (quận/huyện, thành phố): ưu tiên hơn tên tỉnh. key => [tên, vĩ độ, kinh độ, [bí danh]]
     * Bí danh viết không dấu, chữ thường.
     */
    private const PLACES = [
        // TP.HCM - quận / huyện
        'hcm_q1' => ['Quận 1', 10.776, 106.700, ['quan 1']],
        'hcm_q3' => ['Quận 3', 10.784, 106.684, ['quan 3']],
        'hcm_q4' => ['Quận 4', 10.759, 106.705, ['quan 4']],
        'hcm_q5' => ['Quận 5', 10.756, 106.667, ['quan 5']],
        'hcm_q6' => ['Quận 6', 10.748, 106.635, ['quan 6']],
        'hcm_q7' => ['Quận 7', 10.734, 106.722, ['quan 7', 'phu my hung', 'tan thuan']],
        'hcm_q8' => ['Quận 8', 10.724, 106.628, ['quan 8']],
        'hcm_q10' => ['Quận 10', 10.773, 106.668, ['quan 10']],
        'hcm_q11' => ['Quận 11', 10.763, 106.643, ['quan 11']],
        'hcm_q12' => ['Quận 12', 10.867, 106.641, ['quan 12']],
        'hcm_thu_duc' => ['Thủ Đức', 10.851, 106.772, ['thu duc', 'quan 2', 'quan 9', 'thao dien', 'thu thiem', 'linh trung', 'cat lai']],
        'hcm_binh_thanh' => ['Bình Thạnh', 10.810, 106.709, ['binh thanh']],
        'hcm_go_vap' => ['Gò Vấp', 10.838, 106.665, ['go vap']],
        'hcm_phu_nhuan' => ['Phú Nhuận', 10.800, 106.680, ['phu nhuan']],
        'hcm_tan_binh' => ['Tân Bình', 10.801, 106.652, ['tan binh', 'tan son nhat']],
        'hcm_tan_phu' => ['Tân Phú', 10.790, 106.628, ['tan phu']],
        'hcm_binh_tan' => ['Bình Tân', 10.765, 106.603, ['binh tan', 'tan tao', 'an lac', 'binh hung hoa', 'bhh']],
        'hcm_binh_chanh' => ['Bình Chánh', 10.688, 106.594, ['binh chanh', 'binh hung', 'vinh loc', 'tan kien', 'le minh xuan', 'binh dien']],
        'hcm_hoc_mon' => ['Hóc Môn', 10.889, 106.595, ['hoc mon']],
        'hcm_cu_chi' => ['Củ Chi', 10.973, 106.493, ['cu chi']],
        'hcm_nha_be' => ['Nhà Bè', 10.695, 106.738, ['nha be']],
        'hcm_can_gio' => ['Cần Giờ', 10.411, 106.954, ['can gio']],
        // Thành phố / thị xã hay gặp
        'thu_dau_mot' => ['Thủ Dầu Một', 10.980, 106.651, ['thu dau mot']],
        'di_an' => ['Dĩ An', 10.906, 106.769, ['di an']],
        'thuan_an' => ['Thuận An', 10.925, 106.712, ['thuan an', 'vsip']],
        'ben_cat' => ['Bến Cát', 11.141, 106.587, ['ben cat']],
        'tan_uyen' => ['Tân Uyên', 11.050, 106.789, ['tan uyen']],
        'bien_hoa' => ['Biên Hòa', 10.945, 106.824, ['bien hoa', 'amata']],
        'long_thanh' => ['Long Thành', 10.787, 106.950, ['long thanh']],
        'nhon_trach' => ['Nhơn Trạch', 10.709, 106.887, ['nhon trach']],
        'trang_bom' => ['Trảng Bom', 10.952, 107.007, ['trang bom']],
        'vung_tau' => ['Vũng Tàu', 10.346, 107.084, ['vung tau']],
        'phu_my' => ['Phú Mỹ', 10.587, 107.052, ['phu my']],
        'tan_an' => ['Tân An', 10.535, 106.413, ['tan an']],
        'duc_hoa' => ['Đức Hòa', 10.886, 106.410, ['duc hoa']],
        'ben_luc' => ['Bến Lức', 10.639, 106.488, ['ben luc']],
        'my_tho' => ['Mỹ Tho', 10.360, 106.360, ['my tho', 'cai be', 'cai lay']],
        'long_xuyen' => ['Long Xuyên', 10.386, 105.435, ['long xuyen']],
        'chau_doc' => ['Châu Đốc', 10.707, 105.118, ['chau doc']],
        'rach_gia' => ['Rạch Giá', 10.012, 105.081, ['rach gia']],
        'cao_lanh' => ['Cao Lãnh', 10.460, 105.632, ['cao lanh']],
        'sa_dec' => ['Sa Đéc', 10.293, 105.757, ['sa dec']],
        'nha_trang' => ['Nha Trang', 12.239, 109.197, ['nha trang', 'cam ranh', 'cam lam']],
        'da_lat' => ['Đà Lạt', 11.940, 108.458, ['da lat']],
        'bao_loc' => ['Bảo Lộc', 11.548, 107.807, ['bao loc']],
        'phan_thiet' => ['Phan Thiết', 10.928, 108.102, ['phan thiet', 'mui ne']],
        'phan_rang' => ['Phan Rang', 11.565, 108.988, ['phan rang']],
        'quy_nhon' => ['Quy Nhơn', 13.782, 109.219, ['quy nhon']],
        'tuy_hoa' => ['Tuy Hòa', 13.088, 109.293, ['tuy hoa']],
        'buon_ma_thuot' => ['Buôn Ma Thuột', 12.667, 108.038, ['buon ma thuot', 'buon me thuot', 'bmt']],
        'pleiku' => ['Pleiku', 13.983, 108.000, ['pleiku', 'play cu']],
        'dong_xoai' => ['Đồng Xoài', 11.535, 106.883, ['dong xoai']],
    ];

    /** Tỉnh / thành phố trực thuộc trung ương (tên trước sáp nhập 2025) => [tên, vĩ độ, kinh độ, [bí danh]] */
    private const PROVINCES = [
        'ho_chi_minh' => ['TP Hồ Chí Minh', 10.776, 106.701, ['ho chi minh', 'tp hcm', 'tphcm', 'tp.hcm', 'hcm', 'sai gon', 'saigon']],
        'ha_noi' => ['Hà Nội', 21.028, 105.854, ['ha noi', 'hanoi']],
        'hai_phong' => ['Hải Phòng', 20.845, 106.688, ['hai phong']],
        'da_nang' => ['Đà Nẵng', 16.054, 108.202, ['da nang']],
        'can_tho' => ['Cần Thơ', 10.045, 105.747, ['can tho', 'tra noc', 'ninh kieu']],
        'binh_duong' => ['Bình Dương', 10.980, 106.651, ['binh duong']],
        'dong_nai' => ['Đồng Nai', 10.945, 106.824, ['dong nai']],
        'ba_ria_vung_tau' => ['Bà Rịa - Vũng Tàu', 10.496, 107.168, ['ba ria', 'brvt', 'br-vt']],
        'binh_phuoc' => ['Bình Phước', 11.535, 106.883, ['binh phuoc']],
        'tay_ninh' => ['Tây Ninh', 11.310, 106.098, ['tay ninh']],
        'long_an' => ['Long An', 10.535, 106.413, ['long an']],
        'tien_giang' => ['Tiền Giang', 10.360, 106.360, ['tien giang', 'tinh tg']],
        'ben_tre' => ['Bến Tre', 10.241, 106.376, ['ben tre']],
        'tra_vinh' => ['Trà Vinh', 9.935, 106.345, ['tra vinh']],
        'vinh_long' => ['Vĩnh Long', 10.254, 105.972, ['vinh long']],
        'dong_thap' => ['Đồng Tháp', 10.460, 105.632, ['dong thap']],
        'an_giang' => ['An Giang', 10.386, 105.435, ['an giang']],
        'hau_giang' => ['Hậu Giang', 9.784, 105.470, ['hau giang', 'vi thanh']],
        'soc_trang' => ['Sóc Trăng', 9.603, 105.980, ['soc trang']],
        'kien_giang' => ['Kiên Giang', 10.012, 105.081, ['kien giang']],
        'bac_lieu' => ['Bạc Liêu', 9.294, 105.727, ['bac lieu']],
        'ca_mau' => ['Cà Mau', 9.177, 105.150, ['ca mau']],
        'lam_dong' => ['Lâm Đồng', 11.940, 108.458, ['lam dong']],
        'dak_nong' => ['Đắk Nông', 12.004, 107.690, ['dak nong', 'dac nong', 'gia nghia']],
        'dak_lak' => ['Đắk Lắk', 12.667, 108.038, ['dak lak', 'daklak', 'dac lac']],
        'gia_lai' => ['Gia Lai', 13.983, 108.000, ['gia lai']],
        'kon_tum' => ['Kon Tum', 14.350, 108.000, ['kon tum', 'kontum']],
        'binh_thuan' => ['Bình Thuận', 10.928, 108.102, ['binh thuan', 'duc linh', 'tuy phong', 'ham thuan']],
        'ninh_thuan' => ['Ninh Thuận', 11.565, 108.988, ['ninh thuan']],
        'khanh_hoa' => ['Khánh Hòa', 12.239, 109.197, ['khanh hoa']],
        'phu_yen' => ['Phú Yên', 13.088, 109.293, ['phu yen']],
        'binh_dinh' => ['Bình Định', 13.782, 109.219, ['binh dinh', 'phu cat', 'tuy phuoc', 'tay son', 'an nhon', 'van canh', 'vinh thanh', 'hoai nhon']],
        'quang_ngai' => ['Quảng Ngãi', 15.120, 108.792, ['quang ngai']],
        'quang_nam' => ['Quảng Nam', 15.573, 108.474, ['quang nam', 'tam ky', 'hoi an']],
        'hue' => ['Huế', 16.464, 107.590, ['thua thien hue', 'thua thien', 'hue']],
        'quang_tri' => ['Quảng Trị', 16.816, 107.100, ['quang tri', 'dong ha']],
        'quang_binh' => ['Quảng Bình', 17.468, 106.622, ['quang binh', 'dong hoi']],
        'ha_tinh' => ['Hà Tĩnh', 18.343, 105.906, ['ha tinh']],
        'nghe_an' => ['Nghệ An', 18.673, 105.692, ['nghe an', 'tp vinh', 'thanh pho vinh']],
        'thanh_hoa' => ['Thanh Hóa', 19.807, 105.776, ['thanh hoa']],
        'ninh_binh' => ['Ninh Bình', 20.250, 105.975, ['ninh binh']],
        'nam_dinh' => ['Nam Định', 20.420, 106.168, ['nam dinh']],
        'ha_nam' => ['Hà Nam', 20.541, 105.913, ['ha nam', 'phu ly']],
        'thai_binh' => ['Thái Bình', 20.450, 106.340, ['thai binh']],
        'hung_yen' => ['Hưng Yên', 20.646, 106.051, ['hung yen']],
        'hai_duong' => ['Hải Dương', 20.940, 106.330, ['hai duong']],
        'quang_ninh' => ['Quảng Ninh', 20.951, 107.080, ['quang ninh', 'ha long']],
        'bac_ninh' => ['Bắc Ninh', 21.186, 106.076, ['bac ninh']],
        'bac_giang' => ['Bắc Giang', 21.273, 106.194, ['bac giang']],
        'vinh_phuc' => ['Vĩnh Phúc', 21.310, 105.600, ['vinh phuc', 'vinh yen']],
        'phu_tho' => ['Phú Thọ', 21.322, 105.402, ['phu tho', 'viet tri']],
        'thai_nguyen' => ['Thái Nguyên', 21.594, 105.848, ['thai nguyen']],
        'hoa_binh' => ['Hòa Bình', 20.817, 105.338, ['hoa binh']],
        'son_la' => ['Sơn La', 21.327, 103.914, ['son la']],
        'dien_bien' => ['Điện Biên', 21.386, 103.023, ['dien bien']],
        'lai_chau' => ['Lai Châu', 22.396, 103.458, ['lai chau']],
        'lao_cai' => ['Lào Cai', 22.486, 103.970, ['lao cai', 'sa pa', 'sapa']],
        'yen_bai' => ['Yên Bái', 21.705, 104.875, ['yen bai']],
        'tuyen_quang' => ['Tuyên Quang', 21.823, 105.214, ['tuyen quang']],
        'ha_giang' => ['Hà Giang', 22.823, 104.980, ['ha giang']],
        'cao_bang' => ['Cao Bằng', 22.666, 106.258, ['cao bang']],
        'bac_kan' => ['Bắc Kạn', 22.147, 105.835, ['bac kan', 'bac can']],
        'lang_son' => ['Lạng Sơn', 21.853, 106.761, ['lang son']],
    ];

    /**
     * Nhận diện địa danh trong địa chỉ.
     * Ưu tiên địa danh cụ thể (quận/huyện, thành phố); nếu không có thì lấy tên tỉnh xuất hiện SAU CÙNG
     * (địa chỉ thường viết từ nhỏ tới lớn).
     *
     * @return array{key: string, name: string, lat: float, lng: float}|null
     */
    public static function locate(?string $address): ?array
    {
        $text = ' ' . self::normalize((string) $address) . ' ';
        // "Q.7", "Q. 7", "Q7" => "quan 7"
        $text = preg_replace('/(?<![a-z0-9])q\.?\s*(\d{1,2})(?![0-9])/', 'quan $1', $text);
        if (trim($text) === '') {
            return null;
        }

        foreach ([self::PLACES, self::PROVINCES] as $group) {
            $best = null;
            foreach ($group as $key => [$name, $lat, $lng, $aliases]) {
                foreach ($aliases as $alias) {
                    $pattern = '/(?<![a-z0-9])' . preg_quote($alias, '/') . '(?![a-z0-9])/';
                    if (preg_match_all($pattern, $text, $m, PREG_OFFSET_CAPTURE)) {
                        $pos = end($m[0])[1];
                        if (!$best || $pos > $best['pos'] || ($pos === $best['pos'] && strlen($alias) > $best['len'])) {
                            $best = ['key' => $key, 'name' => $name, 'lat' => $lat, 'lng' => $lng, 'pos' => $pos, 'len' => strlen($alias)];
                        }
                    }
                }
            }
            if ($best) {
                unset($best['pos'], $best['len']);

                return $best;
            }
        }

        return null;
    }

    /** Khoảng cách đường chim bay (km) */
    public static function straightKm(array $a, array $b): float
    {
        $r = 6371;
        $dLat = deg2rad($b['lat'] - $a['lat']);
        $dLng = deg2rad($b['lng'] - $a['lng']);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($a['lat'])) * cos(deg2rad($b['lat'])) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($h)));
    }

    /** Chữ thường, bỏ dấu tiếng Việt, gộp khoảng trắng */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
            'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
            'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
            'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
            'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
            'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
            'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
            'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'đ' => 'd',
        ]);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
