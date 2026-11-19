@extends('layouts.default_auth')
@section('title', $upload->ten_file)
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/excel-uploads.css') }}" type="text/css" as="style" />
@endpush
@php
    $flow = \App\Enums\UploadStatus::flow();
    $idx = array_search($upload->trang_thai, $flow, true);
@endphp

@section('admin_content')
    <div class="table-agile-info">
        <div class="import-section">
            <a class="back" href="{{ route('excel-uploads.index') }}">@include('pages.admin.excel-uploads._icon', ['name' => 'back']) Danh sách file</a>

            <div class="page-head">
                <div class="title-block">
                    <span class="file-ext file-ext--lg file-ext--{{ $upload->extension }}"
                        aria-hidden="true">{{ $upload->extension }}</span>
                    <div>
                        <h1 class="break">{{ $upload->ten_file }}</h1>
                        <p class="muted">{{ $upload->unit?->unit_name }} – ngày chụp {{ $upload->ngay->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="head-actions">
                    <a class="btn btn--ghost" href="{{ route('excel-uploads.download', $upload) }}">
                        @include('pages.admin.excel-uploads._icon', ['name' => 'download']) Tải về
                    </a>
                    @if (Auth::user()->name == 'HR' || Auth::user()->name == 'Admin')
                        @if ($upload->trang_thai->canEdit())
                            <a class="btn btn--ghost" href="{{ route('excel-uploads.edit', $upload) }}">
                                @include('pages.admin.excel-uploads._icon', ['name' => 'edit']) Sửa
                            </a>
                            <form method="POST" action="{{ route('excel-uploads.destroy', $upload) }}" data-confirm="Xoá file này? Không khôi phục được." data-confirm-title="Xác nhận xóa" data-confirm-ok="Xóa" data-confirm-danger>
                                @csrf @method('DELETE')
                                <button class="btn btn--danger-ghost" type="submit">@include('pages.admin.excel-uploads._icon', ['name' => 'trash'])
                                    Xoá</button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>

            <div class="detail">
                <section class="panel">
                    <h2>Tiến trình</h2>

                    <ol class="steps">
                        @foreach ($flow as $i => $s)
                            <li @class([
                                'steps__item',
                                'is-done' => $idx !== false && $i < $idx,
                                'is-current' => $idx === $i,
                            ])>
                                <span class="steps__mark">
                                    @if ($idx !== false && $i < $idx)
                                        @include('pages.admin.excel-uploads._icon', ['name' => 'check'])@else{{ $i + 1 }}
                                    @endif
                                </span>
                                <span class="steps__label">{{ $s->label() }}</span>
                            </li>
                        @endforeach
                    </ol>

                    @if (Auth::user()->name == 'Sale' || Auth::user()->name == 'Admin')
                        @if ($upload->order_id)
                            <div class="notice notice--done">
                                Đã tạo <strong>đơn hàng #{{ $upload->order_id }}</strong> từ file này. Trạng thái file tự
                                cập
                                nhật theo đơn.
                                <a href="{{ route('order.index') }}">Xem danh sách đơn</a>
                            </div>
                        @elseif ($upload->canCreateOrder())
                            <div class="notice">
                                File chưa có đơn hàng.
                                <a class="btn btn--sm btn--primary"
                                    href="{{ route('excel-uploads.to-order', $upload) }}">Tạo
                                    đơn từ file</a>
                            </div>
                        @endif


                        {{-- Chỉ còn nút Tiếp nhận khi file đang chờ; sau đó dùng nút "Tạo đơn từ file" ở trên --}}
                        @foreach ($upload->trang_thai->nextSteps() as $s)
                            <form method="POST" action="{{ route('excel-uploads.status', $upload) }}"
                                class="status-actions">
                                @csrf @method('PATCH')
                                <input type="hidden" name="trang_thai" value="{{ $s->value }}">
                                <button type="submit" class="btn btn--primary">{{ $s->actionLabel() }}</button>
                            </form>
                        @endforeach
                        @unless ($upload->trang_thai->canEdit())
                            <p class="hint">File đã được tiếp nhận nên không sửa hoặc xoá được nữa.</p>
                        @endunless
                        @error('trang_thai')
                            <p class="field__err">{{ $message }}</p>
                        @enderror
                    @endif
                </section>

                <section class="panel">
                    <h2>Thông tin</h2>
                    <dl class="meta">
                        <dt>Trạng thái</dt>
                        <dd>@include('pages.admin.excel-uploads._status', ['status' => $upload->trang_thai])</dd>
                        <dt>Đơn vị</dt>
                        <dd>{{ $upload->unit?->unit_name ?? '—' }}</dd>
                        <dt>Ngày chụp</dt>
                        <dd class="tnum">{{ $upload->ngay->format('d/m/Y') }}</dd>
                        @if ($upload->order_id)
                            <dt>Đơn hàng</dt>
                            <dd>#{{ $upload->order_id }}</dd>
                        @endif
                        <dt>Dung lượng</dt>
                        <dd>{{ $upload->kich_thuoc_dep }}</dd>
                        <dt>Thêm lúc</dt>
                        <dd class="tnum">
                            {{ $upload->created_at->format('d/m/Y H:i') }}{{ $upload->nguoiTao ? ' – ' . $upload->nguoiTao->name : '' }}
                        </dd>
                        @if ($upload->tiep_nhan_luc)
                            <dt>Tiếp nhận lúc</dt>
                            <dd class="tnum">
                                {{ $upload->tiep_nhan_luc->format('d/m/Y H:i') }}{{ $upload->nguoiTiepNhan ? ' – ' . $upload->nguoiTiepNhan->name : '' }}
                            </dd>
                        @endif
                        @if ($upload->hoan_thanh_luc)
                            <dt>Hoàn thành lúc</dt>
                            <dd class="tnum">{{ $upload->hoan_thanh_luc->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($upload->ghi_chu)
                            <dt>Ghi chú</dt>
                            <dd>{{ $upload->ghi_chu }}</dd>
                        @endif
                    </dl>
                </section>
            </div>

            <section class="panel preview">
                <div class="preview__head">
                    <h2>Nội dung file</h2>
                    @if ($truncated)
                        <span class="muted">Hiển thị 200 dòng đầu. Tải file về để xem đủ.</span>
                    @endif
                </div>

                @if ($previewError)
                    <p class="notice">{{ $previewError }}</p>
                @elseif (empty($rows))
                    <p class="muted">Sheet đầu tiên không có dữ liệu.</p>
                @else
                    <div class="sheet-wrap">
                        <table class="sheet">
                            <thead>
                                <tr>
                                    <th class="sheet__corner"></th>
                                    @foreach ($rows[0] as $c => $_)
                                        <th>{{ \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $r => $row)
                                    <tr>
                                        <th class="sheet__row">{{ $r + 1 }}</th>
                                        @foreach ($row as $cell)
                                            <td>{{ $cell }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
