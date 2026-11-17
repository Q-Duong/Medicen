@extends('layouts.default_auth')
@section('title', 'Dữ liệu kế toán - Medicen')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/staff.css') }}" type="text/css" as="style" />
    <style>
        .ktd-tabs { display: flex; gap: 6px; margin: 0 0 16px; border-bottom: 1px solid #e7e3da; }
        .ktd-tabs a { padding: 9px 14px; font-weight: 600; font-size: 14px; color: #6b6966; text-decoration: none; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .ktd-tabs a.is-active { color: #c96442; border-bottom-color: #c96442; }
        .ktd-upload { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .ktd-upload input[type=file] { flex: 1 1 280px; min-width: 0; padding: 9px 10px; border: 1px dashed #cfc9bb; border-radius: 11px; background: #faf9f5; font-size: 13px; }
        .ktd-guide { margin: 10px 0 0; padding-left: 18px; color: #6b6966; font-size: 13px; line-height: 1.6; }
        .ktd-result { margin-top: 14px; border: 1px solid #e7e3da; border-radius: 12px; overflow: hidden; }
        .ktd-result__head { padding: 8px 12px; background: #f5f4ef; font-weight: 600; font-size: 13.5px; }
        .ktd-result__head.is-bad { background: #fdecea; color: #a12622; }
        .ktd-result table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ktd-result td, .ktd-result th { padding: 6px 12px; border-top: 1px solid #eee9df; text-align: left; }
        .ktd-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; }
        .ktd-cov { padding: 12px 14px; border: 1px solid #e7e3da; border-radius: 12px; background: #fff; }
        .ktd-cov b { display: block; font-size: 13px; color: #6b6966; font-weight: 600; margin-bottom: 4px; }
        .ktd-cov span { font-size: 14.5px; color: #1f1e1d; }
        .ktd-cov small { display: block; color: #8a877f; margin-top: 2px; }
        .ktd-sum { color: #6b6966; font-size: 12.5px; max-width: 520px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        @media (max-width: 640px) { .ktd-sum { white-space: normal; } }
    </style>
@endpush
@section('admin_content')
    @php
        $tab = request('tab') === 'tai-lieu' ? 'tai-lieu' : 'so-sach';
        $d = fn ($v) => $v ? \Carbon\Carbon::parse($v)->format('d/m/Y') : '—';
        $bankName = ['TIEN_MAT' => 'Tiền mặt', 'HDB' => 'HDBank', 'VCB' => 'Vietcombank', 'AGRI' => 'Agribank'];
        $results = session('import_results');
    @endphp
    <div class="sf">
        <div class="sf-head">
            <div class="sf-head__title">
                <h1>Dữ liệu kế toán</h1>
                <p>Nguồn số liệu cho Trợ lý Kế toán: sổ sách MISA của cả công ty (XQ + CT) và tài liệu (báo cáo tài chính, hợp đồng).</p>
            </div>
            @if (\App\Services\Claude\AssistantAccess::can(Auth::user(), 'ke_toan'))
                <a href="{{ route('assistant.show', 'ke_toan') }}" class="sf-btn sf-btn--ghost"><i class="fas fa-robot"></i> Hỏi Trợ lý Kế toán</a>
            @endif
        </div>

        <nav class="ktd-tabs">
            <a href="{{ route('accounting.index') }}" class="{{ $tab === 'so-sach' ? 'is-active' : '' }}"><i class="fas fa-book"></i> Sổ sách MISA</a>
            <a href="{{ route('accounting.index', ['tab' => 'tai-lieu']) }}" class="{{ $tab === 'tai-lieu' ? 'is-active' : '' }}"><i class="far fa-file-alt"></i> Tài liệu ({{ $documents->count() }})</a>
        </nav>

        @if ($tab === 'so-sach')
            <section class="sf-card" style="margin-bottom:16px">
                <form method="POST" action="{{ route('accounting.import') }}" enctype="multipart/form-data" class="ktd-upload" id="ktd-import">
                    @csrf
                    <input type="file" name="files[]" accept=".xlsx,.xls" multiple required>
                    <button type="submit" class="sf-btn sf-btn--primary"><i class="fas fa-file-import"></i> Nhập sổ sách</button>
                </form>
                @error('files')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                @error('files.*')<small class="sf-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</small>@enderror
                <ul class="ktd-guide">
                    <li>Xuất từ MISA ra Excel, mỗi file có thể gồm nhiều sheet: <b>Sổ nhật ký chung</b>, <b>Sổ chi tiết tài khoản 111</b>, <b>Sổ tiền gửi ngân hàng</b> (đặt tên sheet theo ngân hàng: HDB, VCB, AGRI), <b>Tổng hợp công nợ phải thu / phải trả</b>. File lương: mỗi sheet là một năm (cột Tháng | CT | XQ).</li>
                    <li>Kỳ của sổ lấy ở dòng tiêu đề ("Năm 2026" hoặc "Từ ngày … đến ngày …"). <b>Nhập lại cùng kỳ sẽ thay dữ liệu cũ</b>, không bị trùng — sửa trên MISA xong cứ xuất và tải lên lại.</li>
                    <li>Giới hạn: file Excel <b>.xlsx / .xls</b>, tối đa <b>10 file</b> mỗi lần, mỗi file tối đa <b>30 MB</b>. Không dùng AI — giữ đúng mẫu xuất của MISA (không đổi tiêu đề, không xóa dòng tiêu đề cột) để hệ thống nhận ra.</li>
                    <li>Trợ lý Kế toán dùng số mới ngay sau khi nhập.</li>
                </ul>
                @if ($results)
                    @foreach ($results as $r)
                        <div class="ktd-result">
                            <div class="ktd-result__head {{ $r['ok'] ? '' : 'is-bad' }}">
                                {{ $r['ok'] ? '✓' : '✗' }} {{ $r['file'] }}{{ $r['ok'] ? '' : ': ' . $r['error'] }}
                            </div>
                            @if ($r['ok'])
                                <table>
                                    <tr><th>Sheet</th><th>Sổ</th><th>Số dòng</th><th>Kỳ</th><th>Ghi chú</th></tr>
                                    @foreach ($r['rows'] as $row)
                                        <tr><td>{{ $row['sheet'] }}</td><td>{{ $row['book'] }}</td><td>{{ number_format($row['rows'], 0, ',', '.') }}</td>
                                            <td>{{ $d($row['from']) }} – {{ $d($row['to']) }}</td><td>{{ $row['note'] }}</td></tr>
                                    @endforeach
                                </table>
                            @endif
                        </div>
                    @endforeach
                @endif
            </section>

            <h2 class="sf-section">Đang có</h2>
            <div class="ktd-grid" style="margin-bottom:18px">
                <div class="ktd-cov"><b>Nhật ký chung</b><span>{{ $d($coverage['nkc']->tu) }} – {{ $d($coverage['nkc']->den) }}</span>
                    <small>{{ number_format($coverage['nkc']->n, 0, ',', '.') }} bút toán</small></div>
                @foreach ($coverage['cash'] as $c)
                    <div class="ktd-cov"><b>{{ $bankName[$c->book] ?? $c->book }}</b><span>{{ $d($c->tu) }} – {{ $d($c->den) }}</span>
                        <small>{{ number_format($c->n, 0, ',', '.') }} dòng thu / chi</small></div>
                @endforeach
                <div class="ktd-cov"><b>Lương thực trả (CT / XQ)</b><span>{{ $coverage['salary']->tu ?? '—' }} → {{ $coverage['salary']->den ?? '—' }}</span><small>tổng theo tháng</small></div>
                <div class="ktd-cov"><b>Công nợ phải thu / phải trả</b>
                    <span>{{ $coverage['parties']->unique(fn ($p) => $p->period_from . $p->period_to)->count() }} kỳ</span>
                    <small>mới nhất đến {{ $d(optional($coverage['parties']->first())->period_to) }}</small></div>
            </div>

            <h2 class="sf-section">Lịch sử nhập</h2>
            <div class="sf-table">
                <table>
                    <thead><tr><th>Lúc</th><th>File</th><th>Sheet</th><th>Sổ</th><th>Kỳ</th><th>Số dòng</th><th>Người nhập</th></tr></thead>
                    <tbody>
                        @forelse ($imports as $i)
                            <tr>
                                <td data-label="Lúc" class="sf-nowrap">{{ \Carbon\Carbon::parse($i->created_at)->format('d/m/Y H:i') }}</td>
                                <td data-label="File">{{ $i->file_name }}</td>
                                <td data-label="Sheet">{{ $i->sheet }}</td>
                                <td data-label="Sổ">{{ $books[$i->book] ?? $i->book }}</td>
                                <td data-label="Kỳ" class="sf-nowrap">{{ $d($i->period_from) }} – {{ $d($i->period_to) }}</td>
                                <td data-label="Số dòng">{{ number_format($i->rows, 0, ',', '.') }}</td>
                                <td data-label="Người nhập">{{ $i->email ?? 'lệnh hệ thống' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="sf-empty">Chưa nhập sổ sách nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <form method="GET" action="{{ route('accounting.index') }}" class="sf-filters">
                <input type="hidden" name="tab" value="tai-lieu">
                <label class="sf-field sf-field--grow"><span>Tìm kiếm</span>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tên tài liệu, đối tác, nội dung…" autocomplete="off"></label>
                <label class="sf-field"><span>Loại</span>
                    <select name="type" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        @foreach ($docTypes as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
                    </select></label>
                <div class="sf-filters__actions">
                    <button type="submit" class="sf-btn sf-btn--dark">Lọc</button>
                    <a href="{{ route('accounting.documents.create') }}" class="sf-btn sf-btn--primary"><i class="fas fa-plus"></i> Thêm tài liệu</a>
                </div>
            </form>
            <div class="sf-table">
                <table>
                    <thead><tr><th>Tài liệu</th><th>Loại</th><th>Đối tác / kỳ</th><th>Ngày ký</th><th>Hiệu lực</th><th>Giá trị</th><th class="sf-actions-h">Thao tác</th></tr></thead>
                    <tbody>
                        @forelse ($documents as $doc)
                            <tr>
                                <td data-label="Tài liệu"><b>{{ $doc->title }}</b><div class="ktd-sum" title="{{ \Illuminate\Support\Str::limit($doc->summary, 400) }}">{{ \Illuminate\Support\Str::limit($doc->summary, 160) }}</div></td>
                                <td data-label="Loại"><span class="sf-tag {{ $doc->type === 'bctc' ? 'sf-tag--acc' : ($doc->type === 'hop_dong' ? 'sf-tag--staff' : '') }}">{{ $docTypes[$doc->type] ?? $doc->type }}</span></td>
                                <td data-label="Đối tác / kỳ">{{ $doc->parties ?: $doc->period ?: '—' }}</td>
                                <td data-label="Ngày ký" class="sf-nowrap">{{ $d($doc->signed_on) }}</td>
                                <td data-label="Hiệu lực" class="sf-nowrap">{{ $doc->valid_from || $doc->valid_to ? $d($doc->valid_from) . ' – ' . $d($doc->valid_to) : '—' }}</td>
                                <td data-label="Giá trị" class="sf-nowrap">{{ $doc->value ? number_format($doc->value, 0, ',', '.') . ' đ' : '—' }}</td>
                                <td data-label="Thao tác" class="sf-actions">
                                    @if ($doc->file_path)
                                        <a href="{{ route('accounting.documents.download', $doc->id) }}" class="sf-icon-btn" title="Tải file gốc"><i class="fas fa-download"></i></a>
                                    @endif
                                    <a href="{{ route('accounting.documents.edit', $doc->id) }}" class="sf-icon-btn" title="Sửa"><i class="fas fa-pen"></i></a>
                                    <form action="{{ route('accounting.documents.destroy', $doc->id) }}" method="POST"
                                        data-confirm="Xóa tài liệu &quot;{{ $doc->title }}&quot; và file gốc? Trợ lý sẽ không tra được tài liệu này nữa."
                                        data-confirm-title="Xóa tài liệu" data-confirm-ok="Xóa" data-confirm-danger>
                                        @method('DELETE') @csrf
                                        <button type="submit" class="sf-icon-btn sf-icon-btn--danger" title="Xóa"><i class="far fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="sf-empty">Chưa có tài liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @include('pages.admin.partials.dialog')
@endsection
@push('js')
    <script>
        // Nhập file lớn mất vài chục giây: khóa nút để không bấm 2 lần
        document.getElementById('ktd-import')?.addEventListener('submit', function () {
            const b = this.querySelector('button[type=submit]');
            b.disabled = true;
            b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang nhập…';
        });
    </script>
@endpush
