@if (!empty($count) && $count > 0)
    @foreach ($files as $key => $file)
        <div class="main-file">
            <div class="file-content">
                <div class="file-name">
                    <p>{{ $key }}</p>
                </div>
                <div class="file-action">
                    <a href="https://drive.google.com/file/d/{{ $file }}/view" target="_blank"
                        class="dowload-file">
                        <i class="far fa-eye"></i>
                    </a>
                    <button class="delete-file " type="button"
                        onclick="deleteFileOrder('{{ $key }}', '{{ $file }}', '{{ $order_detail_id }}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
@endif
