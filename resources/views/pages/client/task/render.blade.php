<div class="events">
    <ul class="wrap">
        <li class="events-group">
            <div class="top-info">
                <span>Sales</span>
                @if (Auth::user()->name != 'Sale')
                    <button class="create-task-btn" data-department="1" title="Tạo nhiệm vụ">
                        <span class="card-cta-modal-button-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                class="card-cta-modal-button-small-icon card-modal-button-small-icon">
                                <path
                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                </path>
                            </svg>
                        </span>
                    </button>
                @endif
            </div>
            <ul>
                @foreach ($getAllTasks as $key => $task)
                    @if ($task->department == 1)
                        <li class="single-event">
                            @if ($task->task_status == 2)
                                <div class="task-status">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            @endif
                            <div class="tile-inner" data-component-list="TileOverlay">
                                <div class="tile-content tile-gradient">
                                    <div class="tile-gradient-card-container">
                                        <div class="tile-card-copy">
                                            <div class="tile-card-label typography-card-label">
                                                {{ $task->profile_lastname }}</div>
                                            <div class="tile-card-headline typography-card-headline">
                                                {{ $task->task_name }}</div>
                                        </div>
                                        <div class="tile-timestamp" data-ts="2024-12-11T12:59:37.641Z"><i
                                                class="fa-regular fa-clock"></i>
                                            {{ $task->created_at->locale('vi')->isoFormat('D MMMM, YYYY') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="task-management-button-group">
                                    @if ($task->task_status == 1)
                                        @if (Auth::user()->id == $task->user_id  || Auth::user()->name == 'Admin')
                                            <button class="edit-task-btn" id="{{ $task->id }}"
                                                title="Chỉnh sửa">
                                                <span>
                                                    Edit
                                                </span>
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if (Auth::user()->name == 'Sale' || Auth::user()->name == 'Admin')
                                            <button class="done-task-btn" id="{{ $task->id }}"
                                                title="Hoàn thành">
                                                <span>
                                                    Done
                                                </span>
                                                <i class="fa-regular fa-square-check"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                                <input type="checkbox" class="tile-overlay-toggle"
                                    id="{{ Str::slug($task->task_name) . '-' . $task->id }}">
                                <div class="tile-overlay">
                                    <label tabindex="0" class="tile-button-wrapper"
                                        for="{{ Str::slug($task->task_name) . '-' . $task->id }}"
                                        title="Xem nhiệm vụ">
                                        <span class="tile-button">
                                            <svg class="tile-icon-alt" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span class="tile-button-text" role="button" aria-expanded="false"
                                            aria-controls="content-toggle-siri">
                                            <span class="visuallyhidden">Read more about Siri</span>
                                        </span>
                                    </label>
                                    <div class="tile-overlay-content" role="group"
                                        aria-label="More content about Siri" aria-hidden="true">
                                        <div class="tile-overlay-body">
                                            <div class="tile-overlay-copy typography-overlay-copy">
                                                {{ $task->task_description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </li>

        <li class="events-group">
            <div class="top-info">
                <span>Kết quả</span>
                @if (Auth::user()->name != 'Office')
                    <button class="create-task-btn" data-department="2" title="Tạo nhiệm vụ">
                        <span class="card-cta-modal-button-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                class="card-cta-modal-button-small-icon card-modal-button-small-icon">
                                <path
                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                </path>
                            </svg>
                        </span>
                    </button>
                @endif
            </div>
            <ul>
                @foreach ($getAllTasks as $key => $task)
                    @if ($task->department == 2)
                        <li class="single-event">
                            @if ($task->task_status == 2)
                                <div class="task-status">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            @endif
                            <div class="tile-inner" data-component-list="TileOverlay">
                                <div class="tile-content tile-gradient">
                                    <div class="tile-gradient-card-container">
                                        <div class="tile-card-copy">
                                            <div class="tile-card-label typography-card-label">
                                                {{ $task->profile_lastname }}</div>
                                            <div class="tile-card-headline typography-card-headline">
                                                {{ $task->task_name }}</div>
                                        </div>
                                        <div class="tile-timestamp" data-ts="2024-12-11T12:59:37.641Z"><i
                                                class="fa-regular fa-clock"></i>
                                            {{ $task->created_at->locale('vi')->isoFormat('D MMMM, YYYY') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="task-management-button-group">
                                    @if ($task->task_status == 1)
                                        @if (Auth::user()->id == $task->user_id || Auth::user()->name == 'Admin')
                                            <button class="edit-task-btn" id="{{ $task->id }}"
                                                title="Chỉnh sửa">
                                                <span>
                                                    Edit
                                                </span>
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if (Auth::user()->name == 'Office' || Auth::user()->name == 'Admin')
                                            <button class="done-task-btn" id="{{ $task->id }}"
                                                title="Hoàn thành">
                                                <span>
                                                    Done
                                                </span>
                                                <i class="fa-regular fa-square-check"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                                <input type="checkbox" class="tile-overlay-toggle"
                                    id="{{ Str::slug($task->task_name) . '-' . $task->id }}">
                                <div class="tile-overlay">
                                    <label tabindex="0" class="tile-button-wrapper"
                                        for="{{ Str::slug($task->task_name) . '-' . $task->id }}"
                                        title="Xem nhiệm vụ">
                                        <span class="tile-button">
                                            <svg class="tile-icon-alt" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span class="tile-button-text" role="button" aria-expanded="false"
                                            aria-controls="content-toggle-siri">
                                            <span class="visuallyhidden">Read more about Siri</span>
                                        </span>
                                    </label>
                                    <div class="tile-overlay-content" role="group"
                                        aria-label="More content about Siri" aria-hidden="true">
                                        <div class="tile-overlay-body">
                                            <div class="tile-overlay-copy typography-overlay-copy">
                                                {{ $task->task_description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </li>

        <li class="events-group">
            <div class="top-info">
                <span>Kế toán</span>
                @if (Auth::user()->name != 'Accountant')
                    <button class="create-task-btn" data-department="3" title="Tạo nhiệm vụ">
                        <span class="card-cta-modal-button-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                class="card-cta-modal-button-small-icon card-modal-button-small-icon">
                                <path
                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                </path>
                            </svg>
                        </span>
                    </button>
                @endif
            </div>
            <ul>
                @foreach ($getAllTasks as $key => $task)
                    @if ($task->department == 3)
                        <li class="single-event">
                            @if ($task->task_status == 2)
                                <div class="task-status">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            @endif
                            <div class="tile-inner" data-component-list="TileOverlay">
                                <div class="tile-content tile-gradient">
                                    <div class="tile-gradient-card-container">
                                        <div class="tile-card-copy">
                                            <div class="tile-card-label typography-card-label">
                                                {{ $task->profile_lastname }}</div>
                                            <div class="tile-card-headline typography-card-headline">
                                                {{ $task->task_name }}</div>
                                        </div>
                                        <div class="tile-timestamp" data-ts="2024-12-11T12:59:37.641Z"><i
                                                class="fa-regular fa-clock"></i>
                                            {{ $task->created_at->locale('vi')->isoFormat('D MMMM, YYYY') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="task-management-button-group">
                                    @if ($task->task_status == 1)
                                        @if (Auth::user()->id == $task->user_id || Auth::user()->name == 'Admin')
                                            <button class="edit-task-btn" id="{{ $task->id }}"
                                                title="Chỉnh sửa">
                                                <span>
                                                    Edit
                                                </span>
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </button>
                                        @endif
                                        @if (Auth::user()->name == 'Accountant' || Auth::user()->name == 'Admin')
                                            <button class="done-task-btn" id="{{ $task->id }}"
                                                title="Hoàn thành">
                                                <span>
                                                    Done
                                                </span>
                                                <i class="fa-regular fa-square-check"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                                <input type="checkbox" class="tile-overlay-toggle"
                                    id="{{ Str::slug($task->task_name) . '-' . $task->id }}">
                                <div class="tile-overlay">
                                    <label tabindex="0" class="tile-button-wrapper"
                                        for="{{ Str::slug($task->task_name) . '-' . $task->id }}"
                                        title="Xem nhiệm vụ">
                                        <span class="tile-button">
                                            <svg class="tile-icon-alt" xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20">
                                                <path
                                                    d="M17.25,8.51H11.5V2.75A1.5,1.5,0,0,0,10,1.25h0a1.5,1.5,0,0,0-1.5,1.5V8.5H2.75a1.5,1.5,0,0,0,0,3H8.5v5.75a1.5,1.5,0,0,0,1.5,1.5h0a1.5,1.5,0,0,0,1.5-1.5V11.5h5.75a1.5,1.5,0,0,0,0-3Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span class="tile-button-text" role="button" aria-expanded="false"
                                            aria-controls="content-toggle-siri">
                                            <span class="visuallyhidden">Read more about Siri</span>
                                        </span>
                                    </label>
                                    <div class="tile-overlay-content" role="group"
                                        aria-label="More content about Siri" aria-hidden="true">
                                        <div class="tile-overlay-body">
                                            <div class="tile-overlay-copy typography-overlay-copy">
                                                {{ $task->task_description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endif
                @endforeach
            </ul>
        </li>
    </ul>
</div>