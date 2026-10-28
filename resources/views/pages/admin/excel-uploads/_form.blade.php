
@php $editing = $upload->exists; @endphp

@if ($errors->any())
    <div class="flash flash--err" role="alert">Kiểm tra lại các ô được đánh dấu bên dưới.</div>
@endif

<div class="form-grid">
    <div class="field field--full">
        <label for="unit_id">Đơn vị <span class="req" aria-hidden="true">*</span></label>
        <select id="unit_id" name="unit_id" required class="select-2">
            <option value="">Chọn đơn vị</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('unit_id', $upload->unit_id) == $unit->id)>{{ $unit->unit_abbreviation }}</option>
            @endforeach
        </select>
        @error('unit_id') <p class="field__err">{{ $message }}</p> @enderror
    </div>

    <div class="field field--full">
        <span class="label">File Excel @unless($editing)<span class="req" aria-hidden="true">*</span>@endunless</span>
        <label @class(['dropzone', 'is-invalid' => $errors->has('file')]) data-dropzone>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" @unless($editing) required @endunless>
            @include('pages.admin.excel-uploads._icon', ['name' => 'upload'])
            <span class="dropzone__text">
                <strong data-filename>
                    {{ $editing ? 'Chọn file mới nếu muốn thay' : 'Kéo file vào đây hoặc bấm để chọn' }}
                </strong>
                <span class="muted">.xlsx, .xls, .csv – tối đa 10 MB</span>
            </span>
        </label>
        <p class="hint">Dùng <a href="{{ route('excel-uploads.template') }}">file mẫu đơn hàng</a>. Ngày chụp lấy tự động từ file.</p>
        @if ($editing)
            <p class="hint">File hiện tại: <strong>{{ $upload->ten_file }}</strong> ({{ $upload->kich_thuoc_dep }})</p>
        @endif
        @error('file') <p class="field__err">{{ $message }}</p> @enderror
    </div>

    <div class="field field--full">
        <label for="ghi_chu">Ghi chú</label>
        <textarea id="ghi_chu" name="ghi_chu" rows="3" placeholder="Không bắt buộc"
                  @class(['is-invalid' => $errors->has('ghi_chu')])>{{ old('ghi_chu', $upload->ghi_chu) }}</textarea>
        @error('ghi_chu') <p class="field__err">{{ $message }}</p> @enderror
    </div>
</div>