@extends('layouts.default')
@push('css')
    <link rel="stylesheet" href="{{ versionResource('assets/css/built/task.built.css') }}" type="text/css" as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/popupForm/overview.built.css') }}" rel='stylesheet'
        type='text/css' as="style" />
    <link rel="stylesheet" href="{{ versionResource('assets/css/support/org-form.built.css') }}" type="text/css"
        as="style" />
@endpush
@section('content')
@section('title', 'Task Manager - ')
<section class="breadcrumb-option">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__text">
                    <h4>Task Manager</h4>
                    <div class="breadcrumb__links">
                        <a href="{{ URL::to('/') }}">Trang chủ</a>
                        <span>Task Manager</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="task-render">
    <header class="cd-intro">
        <div class="container">
            <div class="task-support">
                <div class="row justify-content-center">
                    <div class="col-lg-3 col-6">
                        <div class="form-dropdown">
                            <select class="form-dropdown-select select-year" disabled>
                                @for ($i = 0; $i <= 10; $i++)
                                    <option {{ $currentYear == $i + 2023 ? 'selected' : '' }}
                                        value="{{ $i + 2023 }}">
                                        {{ $i + 2023 }}</option>
                                @endfor
                            </select>
                            <span class="form-dropdown-chevron" aria-hidden="true"><i
                                    class="fa-solid fa-angle-down"></i></span>
                            <span class="form-dropdown-label" aria-hidden="true">Năm</span>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="form-dropdown">
                            <select class="form-dropdown-select select-month" disabled>
                                <option disabled class="define-month">Chọn tháng</option>
                                @foreach ($months as $key => $month)
                                    <option {{ $months[$key] == $currentMonth ? 'selected' : '' }}
                                        value="{{ $months[$key] }}">
                                        {{ $key + 1 }}</option>
                                @endforeach
                            </select>
                            <span class="form-dropdown-chevron" aria-hidden="true"><i
                                    class="fa-solid fa-angle-down"></i></span>
                            <span class="form-dropdown-label" aria-hidden="true">Tháng</span>
                        </div>
                    </div>
                    <div class="search-box">
                        <div class="btn-search">
                            <button class="btn-schedule-search"><i class="fas fa-search"></i></button>
                        </div>
                        <input type="text" class="schedule-search" name="search-keywords" placeholder="Nhập tên Cty">
                        <div class="search-results"></div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="cd-task">
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
                                                @if (Auth::user()->id == $task->user_id || Auth::user()->name == 'Admin')
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
                                                    <span class="visuallyhidden">Xem nhiệm vụ</span>
                                                </span>
                                            </label>
                                            <div class="tile-overlay-content" role="group"
                                                aria-label="Xem nhiệm vụ" aria-hidden="true">
                                                <div class="tile-overlay-body">
                                                    <div class="tile-overlay-copy typography-overlay-copy">
                                                        {{ $task->task_description }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <input type="checkbox" class="tile-overlay-toggle"
                                            id="{{ Str::slug($task->task_name) . '-progress-' . $task->id }}">
                                        <div class="tile-overlay">
                                            <label tabindex="0" class="tile-button-wrapper"
                                                for="{{ Str::slug($task->task_name) . '-progress-' . $task->id }}"
                                                title="Xem tiến độ">
                                                <span class="tile-button-text" role="button" aria-expanded="false"
                                                    aria-controls="content-toggle-siri">
                                                    <span class="">Xem tiến độ</span>
                                                </span>
                                            </label>
                                            <div class="tile-overlay-content" role="group"
                                                aria-label="Xem tiến độ" aria-hidden="true">
                                                <div class="tile-overlay-body">
                                                    <div class="tile-overlay-copy typography-overlay-copy">
                                                        {{ $task->task_progress }}
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

                <li class="events-group">
                    <div class="top-info">
                        <span>IT</span>
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
                    </div>
                    <ul>
                        @foreach ($getAllTasks as $key => $task)
                            @if ($task->department == 4)
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
                                                @if (Auth::user()->name == 'Admin')
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
    </div>
    <div id="portal">
        <div data-core-fade-transition-wrapper
            class="rc-overlay rc-overlay-popup rc-overlay-fixed-width r-fade-transition-enter-done" data-core-overlay
            data-core-overlay-cover>
            <div data-core-overlay-content tabindex="-1" role="dialog" aria-labelledby="edit-address-header"
                aria-describedby="edit-address-desc" aria-modal="true">
                <div class="rc-overlay-popup-outer">
                    <div class="rc-overlay-popup-content">
                        <div data-core-fade-transition-wrapper class="r-fade-transition-enter-done">
                            <div class="row">
                                <div class="column large-9 small-12 large-centered">
                                    <h2 id="edit-header"
                                        class="rs-account-addressoverlay-subheader typography-headline-reduced">
                                        Tạo nhiệm vụ
                                    </h2>
                                </div>
                                <div class="column small-12 large-10 large-centered">
                                    <form id="task">
                                        @csrf
                                        <input type="hidden" name="department">
                                        <input type="hidden" name="id">
                                        <input type="hidden" name="type" value="create">
                                        <div class="form-textbox">
                                            <input type="text" class="form-textbox-input" name="task_name"
                                                autocapitalize="off" autocomplete="off">
                                            <div class="form-message-wrapper task_name">
                                                <i class="fa fa-exclamation-circle"></i>
                                                <span class="task_name-form-message"></span>
                                            </div>
                                            <span class="form-textbox-label">Tên nhiệm vụ</span>
                                        </div>
                                        <legend class="rs-form-label">
                                            <h3 class="rs-form-label-header typography-body">Mô tả nhiệm vụ
                                            </h3>
                                        </legend>
                                        <div class="form-textbox">
                                            <textarea name="task_description" rows=8 class="form-textarea"></textarea>
                                            <div class="form-message-wrapper task_description">
                                                <i class="fa fa-exclamation-circle"></i>
                                                <span class="task_description-form-message"></span>
                                            </div>
                                        </div>
                                        <div class="rs-overlay-change">
                                            <button type="button" class="form-button rs-lookup-submit">Tạo</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="rc-overlay-close" aria-label="close" data-autom="overlay-close">
                        <span class="rc-overlay-closesvg">
                            <svg width="21" height="21"
                                class="as-svgicon as-svgicon-close as-svgicon-tiny as-svgicon-closetiny"
                                role="img" aria-hidden="true">
                                <path fill="none" d="M0 0h21v21H0z"></path>
                                <path
                                    d="m12.12 10 4.07-4.06a1.5 1.5 0 1 0-2.11-2.12L10 7.88 5.94 3.81a1.5 1.5 0 1 0-2.12 2.12L7.88 10l-4.07 4.06a1.5 1.5 0 0 0 0 2.12 1.51 1.51 0 0 0 2.13 0L10 12.12l4.06 4.07a1.45 1.45 0 0 0 1.06.44 1.5 1.5 0 0 0 1.06-2.56Z">
                                </path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('js')
<script type="text/javascript">
    var url_create_or_update = "{{ route('task.create_or_update') }}";
    var url_load_task = "{{ route('task.load') }}";
    var url_update_status = "{{ route('task.update_status') }}";
    var url_delete = "{{ route('task.destroy') }}";
</script>
<script src="{{ asset('assets/js/support/essential.js') }}"></script>
<script src="{{ asset('assets/js/tool/task/task.js') }}"></script>
@endpush
