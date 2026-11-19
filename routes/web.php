<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\UserDepartmentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExcelUploadController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\ScheduleResultsController;
use App\Http\Controllers\ScheduleSalesController;
use App\Http\Controllers\ScheduleTechniciansController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ZaloController;
use App\Http\Controllers\QrController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//-------------------------------------------- Frontend --------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/search', [HomeController::class, 'search']);
Route::post('/autocomplete-ajax', [HomeController::class, 'autocomplete_ajax']);

Route::get('/sitemap.xml', function () {
    return response()->view('sitemap')->header('Content-Type', 'text/xml');
});

//Blog
Route::prefix('blogs')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blogs.index');
    Route::get('{blog_slug}', [BlogController::class, 'details'])->name('blogs.details');
});

//Service
Route::get('services', [ServiceController::class, 'index'])->name('services.index');

//About
Route::prefix('about')->group(function () {
    Route::get('/', [AboutController::class, 'index'])->name('about.index');
});

//Contact
Route::prefix('contact')->group(function () {
    Route::get('/', [ContactController::class, 'index'])->name('contact.index');
    Route::post('/submit', [ContactController::class, 'submit'])->name('contact.submit');
});


//Schedule
Route::prefix('schedules')->group(function () {
    Route::get('login', [ScheduleController::class, 'showLogin'])->name('schedules.login');
    Route::post('login', [ScheduleController::class, 'login'])->name('schedules.login.post');
    Route::post('logout', [ScheduleController::class, 'logout'])->name('schedules.logout');

    //Drivers
    Route::prefix('drivers')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('schedules.drivers.index');
        Route::post('select', [ScheduleController::class, 'select'])->name('schedules.drivers.select');
        Route::post('select-driver', [ScheduleController::class, 'selectDriver'])->name('schedules.drivers.select_driver');
    });

    Route::group(['middleware' => 'schedules.login'], function () {
        //Sales
        Route::group(['middleware' => 'schedules.role.sales'], function () {
            Route::prefix('sales')->group(function () {
                Route::get('/', [ScheduleSalesController::class, 'index'])->name('schedules.sales.index');
                Route::post('select', [ScheduleSalesController::class, 'select'])->name('schedules.sales.select');
                Route::post('select-driver', [ScheduleController::class, 'selectDriver'])->name('schedules.sales.select_driver');
                Route::post('select-technician', [ScheduleTechniciansController::class, 'selectTechnician'])->name('schedules.sales.select_technician');
            });
        });

        //Technicians
        // Route::group(['middleware' => 'schedules.role.technicians'], function () {
        Route::prefix('technicians')->group(function () {
            Route::get('/', [ScheduleTechniciansController::class, 'index'])->name('schedules.technicians.index');
            Route::post('select', [ScheduleTechniciansController::class, 'select'])->name('schedules.technicians.select');
            Route::post('select-technician', [ScheduleTechniciansController::class, 'selectTechnician'])->name('schedules.technicians.select_technician');
            Route::post('update', [ScheduleTechniciansController::class, 'update'])->name('schedules.technicians.update');
        });
        // });

        //Results
        Route::group(['middleware' => 'schedules.role.results'], function () {
            Route::prefix('results')->group(function () {
                Route::get('/', [ScheduleResultsController::class, 'index'])->name('schedules.results.index');
                Route::post('get', [ScheduleResultsController::class, 'get'])->name('schedules.results.get');
                Route::post('suggest', [ScheduleResultsController::class, 'suggest'])->name('schedules.results.suggest');
                Route::post('search', [ScheduleResultsController::class, 'search'])->name('schedules.results.search');
                Route::post('select', [ScheduleResultsController::class, 'select'])->name('schedules.results.select');
                Route::post('update', [ScheduleResultsController::class, 'update'])->name('schedules.results.update');
            });
        });
    });
});

//Task
Route::group(['middleware' => 'schedules.login'], function () {
    Route::prefix('task-manager')->group(function () {
        Route::get('/', [TaskController::class, 'index'])->name('task.index');
        Route::get('load', [TaskController::class, 'load'])->name('task.load');
        Route::post('create-or-update', [TaskController::class, 'createOrUpdate'])->name('task.create_or_update');
        Route::patch('update-status', [TaskController::class, 'updateStatus'])->name('task.update_status');
        Route::delete('delete', [TaskController::class, 'destroy'])->name('task.destroy');
    });
});

Route::get('/storage-drive', [OrderController::class, 'Storage']);

Auth::routes();
Route::get('/admin', [AdminController::class, 'login']);
Route::post('admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
//-------------------------------------------- Backend --------------------------------------------
// Mã bảo mật (CSRF) mới của phiên hiện tại: trang mở lâu bị hết phiên, đăng nhập lại ở tab khác rồi lấy mã mới
// để lưu tiếp mà không phải tải lại trang (không mất số đang nhập). Chỉ trả mã của chính phiên đang gọi.
Route::get('csrf-token', fn () => response()->json(['token' => csrf_token(), 'auth' => auth()->check()])
    ->header('Cache-Control', 'no-store'))->name('csrf.token');

Route::prefix('admin')->middleware(['auth'])->group(function () {
    //Dashboard
    Route::get('/trang-chu', [AdminController::class, 'home'])->name('admin.home');
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard.index')->middleware('access:dashboard');
    Route::get('chat', [AdminController::class, 'chat'])->name('dashboard.chat');
    //Chuông thông báo: việc cần làm của tài khoản
    Route::get('notifications/todo', [\App\Http\Controllers\NotificationController::class, 'todo'])->name('notifications.todo');

    //Profit: Sales, Kế toán (config profit.user_departments) và Admin
    Route::prefix('profit')->middleware('department:' . implode(',', config('profit.user_departments', [])))->group(function () {
        Route::get('calculate', [ProfitController::class, 'index'])->name('profit.index');
        Route::post('save', [ProfitController::class, 'store'])->name('profit.save');
        Route::get('report/{id}', [ProfitController::class, 'show'])->name('profit.show');
        Route::delete('report/{id}', [ProfitController::class, 'destroy'])->name('profit.delete');
        Route::post('report/{id}/approve', [ProfitController::class, 'approve'])->name('profit.approve');
    });

    //Statistics
    Route::prefix('statistics')->group(function () {
        Route::post('revenue-by-date', [StatisticsController::class, 'revenueByDate'])->name('statistics.revenue_by_date')->middleware('access:dashboard,kpi');
        Route::post('optional-revenue', [StatisticsController::class, 'optionalRevenue'])->name('statistics.optional_revenue')->middleware('access:dashboard,kpi');
        Route::post('revenue-for-the-month', [StatisticsController::class, 'revenueForTheMonth'])->name('statistics.revenue_for_the_month')->middleware('access:dashboard,kpi');
        Route::post('revenue-by-unit', [StatisticsController::class, 'revenueByUnit'])->name('statistics.revenue_by_unit')->middleware('access:dashboard,kpi');
        Route::get('/', [StatisticsController::class, 'index'])->name('statistics.index')->middleware('access:kpi');
        Route::post('performance-analysis', [StatisticsController::class, 'performanceAnalysis'])->name('statistics.performance_analysis')->middleware('access:kpi');
    });

    //Statistics
    Route::prefix('employee-timekeeping')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('employee_timekeeping.index')->middleware('isAdmin');
        Route::post('export', [EmployeeController::class, 'export'])->name('employee_timekeeping.export')->middleware('access:timekeeping');
    });

    //Information Account
    Route::get('/information', [AdminController::class, 'information'])->name('information');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    // Trang "Tài khoản của tôi": lưu thông tin cá nhân + đổi mật khẩu
    Route::post('/save-infomation', [AdminController::class, 'store_information'])->name('store-information');

    //Customer
    Route::prefix('customer')->middleware('isAdmin')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
    });
    // Route::prefix('contact')->group(function () {
    //     Route::get('edit', [ContactController::class, 'edit'])->name('contact.edit');
    //     Route::patch('update', [ContactController::class, 'update'])->name('contact.update');
    // });
    //403
    Route::get('/403', function () {
        return view('403');
    })->name('403');

    //Unit
    // Kế toán thêm / sửa đơn vị; xóa chỉ Admin
    Route::prefix('unit')->middleware('access:units')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('unit.index');
        Route::get('create', [UnitController::class, 'create'])->name('unit.create');
        Route::post('save', [UnitController::class, 'store'])->name('unit.store');
        Route::get('edit/{id}', [UnitController::class, 'edit'])->name('unit.edit');
        Route::patch('update/{id}', [UnitController::class, 'update'])->name('unit.update');
        Route::delete('delete/{id}', [UnitController::class, 'destroy'])->name('unit.destroy')->middleware('isAdmin');
    });

    //File
    // Upload file: danh sách khám (trang Đơn hàng) và file kết quả (trang lịch bộ phận Kết quả)
    Route::prefix('file')->middleware('access:orders,debt_result')->group(function () {
        Route::post('process', [FileController::class, 'process'])->name('file.process');
        Route::delete('revert', [FileController::class, 'revert'])->name('file.revert');
        Route::delete('delete-file-order', [FileController::class, 'destroyFileOrder'])->name('file.delete_file_order');
        Route::delete('delete-file-total', [FileController::class, 'destroyFileTotal'])->name('file.delete_file_total');
    });

    //Export Excel
    Route::get('/export-excel', [OrderController::class, 'exportExcel'])->name('export.excel')->middleware('access:debt_export');

    //Export Misa
    Route::get('/export-misa', [OrderController::class, 'exportMisa'])->name('export.misa')->middleware('access:debt_export');


    //Trợ lý AI các phòng ban (Claude). Quyền theo users.department, xem AssistantAccess
    Route::prefix('assistant')->name('assistant.')->middleware('assistant.access')->group(function () {
        Route::get('{department}', [AssistantController::class, 'show'])->name('show');
        Route::post('{department}/ask', [AssistantController::class, 'ask'])->name('ask')->middleware('throttle:20,1');
        // Hỏi lại câu trả lời của lượt đang chờ (trình duyệt gọi mỗi ~1,5 giây)
        Route::post('{department}/conversations/{conversation}/poll', [AssistantController::class, 'poll'])->name('poll')->whereNumber('conversation')->middleware('throttle:120,1');
        Route::get('{department}/conversations', [AssistantController::class, 'conversations'])->name('conversations');
        Route::get('{department}/conversations/{conversation}', [AssistantController::class, 'messages'])->name('messages')->whereNumber('conversation');
        Route::patch('{department}/conversations/{conversation}', [AssistantController::class, 'rename'])->name('rename')->whereNumber('conversation');
        Route::delete('{department}/conversations/{conversation}', [AssistantController::class, 'destroy'])->name('destroy')->whereNumber('conversation');
    });

    //Xếp lịch xe: phòng Điều phối xếp lịch (config schedule.user_departments) và Admin
    Route::prefix('xep-lich')->name('dispatch.')->middleware('department:' . implode(',', config('schedule.user_departments', [])))->group(function () {
        Route::get('/', [DispatchController::class, 'index'])->name('index');
        Route::post('goi-y', [DispatchController::class, 'suggest'])->name('suggest')->middleware('throttle:10,1');
        Route::post('ap-dung', [DispatchController::class, 'apply'])->name('apply');
        Route::get('don/{order}', [DispatchController::class, 'order'])->name('order')->whereNumber('order');
        Route::get('tuan', [DispatchController::class, 'week'])->name('week');
        Route::get('anh-tuan', [DispatchController::class, 'weekSheet'])->name('week_sheet');
        Route::get('cham-cong', [DispatchController::class, 'timekeeping'])->name('timekeeping');
        Route::get('don-cho', [DispatchController::class, 'pending'])->name('pending');
        Route::get('lam-moi', [DispatchController::class, 'refresh'])->name('refresh');
        Route::post('huy/{order}', [DispatchController::class, 'cancelSchedule'])->name('cancel')->whereNumber('order');
        Route::post('cham-cong/{order}', [DispatchController::class, 'saveActual'])->name('actual')->whereNumber('order');
        Route::get('sua', [DispatchController::class, 'editOrders'])->name('edit');
        Route::post('giu-cho', [DispatchController::class, 'storeHold'])->name('holds.store');
        Route::get('giu-cho/{hold}', [DispatchController::class, 'showHold'])->name('holds.show');
        Route::put('giu-cho/{hold}', [DispatchController::class, 'updateHold'])->name('holds.update');
        Route::delete('giu-cho/{hold}', [DispatchController::class, 'destroyHold'])->name('holds.destroy');
        Route::post('lich-nghi', [DispatchController::class, 'storeLeave'])->name('leaves.store');
        Route::delete('lich-nghi/{leave}', [DispatchController::class, 'destroyLeave'])->name('leaves.destroy');
        Route::post('bao-tri-xe', [DispatchController::class, 'storeMaintenance'])->name('maintenances.store');
        Route::delete('bao-tri-xe/{maintenance}', [DispatchController::class, 'destroyMaintenance'])->name('maintenances.destroy');
    });

    //Sales (đơn hàng). Trước đây 'isSale' cho qua mọi tài khoản nhân viên (role 2) nên KTV / Office / HR dán link vẫn vào được
    Route::group(['middleware' => 'access:orders'], function () {
        //Post (bài viết trang chủ): chỉ Admin
        Route::prefix('post')->middleware('isAdmin')->group(function () {
            Route::get('/', [PostController::class, 'index'])->name('post.index');
            Route::get('create', [PostController::class, 'create'])->name('post.create');
            Route::post('save', [PostController::class, 'store'])->name('post.store');
            Route::get('edit/{id}', [PostController::class, 'edit'])->name('post.edit');
            Route::patch('update/{id}', [PostController::class, 'update'])->name('post.update');
            Route::delete('delete/{id}', [PostController::class, 'destroy'])->name('post.destroy');
        });
        //Order
        Route::prefix('order')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('order.index');
            Route::get('create', [OrderController::class, 'create'])->name('order.create');
            Route::post('save', [OrderController::class, 'store'])->name('order.store');
            Route::get('edit/{id}', [OrderController::class, 'edit'])->name('order.edit');
            Route::patch('update/{id}', [OrderController::class, 'update'])->name('order.update');
            Route::delete('delete/{id}', [OrderController::class, 'destroy'])->name('order.destroy');
            Route::get('copy/{id}', [OrderController::class, 'copy'])->name('order.copy');
            Route::get('group-candidates', [OrderController::class, 'groupCandidates'])->name('order.group_candidates');
            Route::get('filter-options', [OrderController::class, 'filterOptions'])->name('order.filter_options');
            Route::get('customer-suggestions', [OrderController::class, 'customerSuggestions'])->name('order.customer_suggestions');
            Route::post('save-copy', [OrderController::class, 'storeCopy'])->name('order.store_copy');
            Route::get('view/{code}', [OrderController::class, 'view'])->name('order.view');
            Route::get('print/{id}', [OrderController::class, 'print'])->name('order.print');
        });


        //schedule
        Route::prefix('schedule')->group(function () {
            Route::get('create/{id}', [ScheduleSalesController::class, 'create'])->name('schedule.create');
            Route::post('save', [ScheduleSalesController::class, 'store'])->name('schedule.store');
            Route::get('edit/{id}', [ScheduleSalesController::class, 'edit'])->name('schedule.edit');
            Route::patch('update/{id}', [ScheduleSalesController::class, 'update'])->name('schedule.update');
            Route::post('cancel', [ScheduleSalesController::class, 'cancel'])->name('schedule.cancel');
        });
    });

    //Admin
    Route::group(['middleware' => 'isAdmin'], function () {
        //Staff
        //Phân quyền phòng ban (dùng cho trợ lý AI)
        Route::get('user-departments', [UserDepartmentController::class, 'index'])->name('user-departments.index');
        Route::patch('user-departments/{user}', [UserDepartmentController::class, 'update'])->name('user-departments.update');
        //Quản lý tài khoản đăng nhập
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AccountController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\AccountController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\AccountController::class, 'store'])->name('store');
            Route::get('{user}/edit', [\App\Http\Controllers\AccountController::class, 'edit'])->name('edit')->whereNumber('user');
            Route::patch('{user}', [\App\Http\Controllers\AccountController::class, 'update'])->name('update')->whereNumber('user');
            Route::delete('{user}', [\App\Http\Controllers\AccountController::class, 'destroy'])->name('destroy')->whereNumber('user');
        });
        Route::prefix('staff')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('staff.index');
            Route::get('create', [StaffController::class, 'create'])->name('staff.create');
            Route::post('save', [StaffController::class, 'store'])->name('staff.store');
            Route::get('edit/{id}', [StaffController::class, 'edit'])->name('staff.edit');
            Route::patch('update/{id}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('delete/{id}', [StaffController::class, 'destroy'])->name('staff.destroy');
        });
        //History
        Route::prefix('history')->group(function () {
            Route::get('/', [HistoryController::class, 'index'])->name('history.index');
        });
    });

    //Accountant: trang "Thông tin báo cáo" cũ của đơn
    Route::prefix('accountant')->middleware('access:order_report')->group(function () {
        Route::get('update-order/{id}', [AccountantController::class, 'updateOrder'])->name('accountant.order.update');
        Route::post('save-order/{id}', [AccountantController::class, 'storeOrder'])->name('accountant.order.store');
    });
    //Dữ liệu kế toán (sổ sách MISA + tài liệu) cho Trợ lý Kế toán
    // Công nợ CT (máy CT tại bệnh viện hợp tác) + Sổ TK VCB chú Lộc: kế toán nhập trên web, nối vào Báo cáo thu chi
    Route::prefix('cong-no-ct')->name('ct.')->middleware('access:debt_accountant')->controller(\App\Http\Controllers\CtReceivableController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('them', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{receivable}', 'edit')->name('edit')->whereNumber('receivable');
        Route::patch('{receivable}', 'update')->name('update')->whereNumber('receivable');
        Route::delete('{receivable}', 'destroy')->name('destroy')->whereNumber('receivable');
        Route::post('{receivable}/thanh-toan', 'storePayment')->name('payments.store')->whereNumber('receivable');
        Route::patch('thanh-toan/{payment}', 'updatePayment')->name('payments.update')->whereNumber('payment');
        Route::delete('thanh-toan/{payment}', 'destroyPayment')->name('payments.destroy')->whereNumber('payment');
        Route::post('don-vi', 'storePartner')->name('partners.store');
        Route::patch('don-vi/{partner}', 'updatePartner')->name('partners.update')->whereNumber('partner');
        Route::delete('don-vi/{partner}', 'destroyPartner')->name('partners.destroy')->whereNumber('partner');
        Route::post('ghi-nho', 'saveMemo')->name('memos.store');
        Route::patch('ghi-nho/{memo}', 'saveMemo')->name('memos.update')->whereNumber('memo');
        Route::delete('ghi-nho/{memo}', 'destroyMemo')->name('memos.destroy')->whereNumber('memo');
    });
    Route::prefix('so-vcb-chu-loc')->name('vcb.')->middleware('access:debt_accountant')->controller(\App\Http\Controllers\VcbLedgerController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::patch('{entry}', 'update')->name('update')->whereNumber('entry');
        Route::delete('{entry}', 'destroy')->name('destroy')->whereNumber('entry');
        Route::post('so-du-dau', 'opening')->name('opening');
        Route::post('khoan-co-dinh', 'saveRecurring')->name('recurrings.store');
        Route::patch('khoan-co-dinh/{recurring}', 'saveRecurring')->name('recurrings.update')->whereNumber('recurring');
    });
    Route::prefix('du-lieu-ke-toan')->name('accounting.')->middleware('access:debt_accountant')->group(function () {
        Route::get('/', [\App\Http\Controllers\AccountingDataController::class, 'index'])->name('index');
        Route::post('so-sach', [\App\Http\Controllers\AccountingDataController::class, 'importBooks'])->name('import');
        Route::get('tai-lieu/them', [\App\Http\Controllers\AccountingDataController::class, 'createDocument'])->name('documents.create');
        Route::post('tai-lieu', [\App\Http\Controllers\AccountingDataController::class, 'storeDocument'])->name('documents.store');
        Route::post('tai-lieu/doc-bang-ai', [\App\Http\Controllers\AccountingDataController::class, 'extractDocument'])->name('documents.extract')->middleware('throttle:10,1');
        Route::get('tai-lieu/{id}/sua', [\App\Http\Controllers\AccountingDataController::class, 'editDocument'])->name('documents.edit')->whereNumber('id');
        Route::patch('tai-lieu/{id}', [\App\Http\Controllers\AccountingDataController::class, 'updateDocument'])->name('documents.update')->whereNumber('id');
        Route::delete('tai-lieu/{id}', [\App\Http\Controllers\AccountingDataController::class, 'destroyDocument'])->name('documents.destroy')->whereNumber('id');
        Route::get('tai-lieu/{id}/tai-ve', [\App\Http\Controllers\AccountingDataController::class, 'download'])->name('documents.download')->whereNumber('id');
    });

    Route::group(['middleware' => 'access:debt_accountant'], function () {
        //Accountant
        Route::prefix('accountant')->group(function () {
            Route::get('/', [AccountantController::class, 'index'])->name('accountant.index');
            Route::post('get-accountant', [AccountantController::class, 'getAccountant'])->name('accountant.get');
            Route::patch('update', [AccountantController::class, 'updateRow'])->name('accountant.update');
            Route::patch('update-rows', [AccountantController::class, 'updateRows'])->name('accountant.update_rows');
            Route::patch('qty-change', [AccountantController::class, 'qtyChange'])->name('accountant.qty_change');
            Route::patch('complete', [AccountantController::class, 'complete'])->name('accountant.complete');
            Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant.filter_options');
        });

        //Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::post('/{id}/seed-default', [ReportController::class, 'seedDefault'])
                ->name('seed_default');
            Route::put('/{id}', [ReportController::class, 'update'])->name('update');
            Route::post('/update-order', [ReportController::class, 'updateOrder'])->name('update_order');
            Route::get('/{id}/export-pdf', [ReportController::class, 'exportPdf'])
                ->name('export_pdf');
        });
    });

    Route::group(['middleware' => 'access:debt_sales'], function () {
        Route::prefix('accountant-sales')->group(function () {
            Route::get('/', [AccountantController::class, 'indexSales'])->name('accountant_sales.index');
            Route::post('get-accountant', [AccountantController::class, 'getAccountantSales'])->name('accountant_sales.get');
            Route::patch('update', [AccountantController::class, 'updateRowSales'])->name('accountant_sales.update');
            Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant_sales.filter_options');
        });
    });

    Route::prefix('accountant-result')->middleware('access:debt_result')->group(function () {
        Route::get('/', [AccountantController::class, 'indexResult'])->name('accountant_result.index');
        Route::post('get-accountant', [AccountantController::class, 'getAccountantResult'])->name('accountant_result.get');
        Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant_result.filter_options');
    });

    Route::prefix('contract')->group(function () {
        Route::middleware('access:contract_edit')->group(function () {
            Route::get('/', [ContractController::class, 'index'])->name('contract.index');
            Route::post('get-contract', [ContractController::class, 'getContract'])->name('contract.get');
            Route::patch('update', [ContractController::class, 'update'])->name('contract.update');
            Route::post('filter', [ContractController::class, 'filter'])->name('contract.filter');
        });
        Route::prefix('view-only')->middleware('access:contract_view,contract_edit')->group(function () {
            Route::get('/', [ContractController::class, 'indexViewOnly'])->name('contract.view_only.index');
            Route::post('get-contract', [ContractController::class, 'getContractViewOnly'])->name('contract.view_only.get');
            Route::post('filter', [ContractController::class, 'filterViewOnly'])->name('contract.view_only.filter');
        });
    });

    Route::prefix('excel-uploads')->name('excel-uploads.')->middleware('access:orders')->group(function () {
        Route::get('template', [ExcelUploadController::class, 'template'])->name('template');
        Route::get('{excel_upload}/to-order', [ExcelUploadController::class, 'toOrder'])->name('to-order');
        Route::get('{excel_upload}/download', [ExcelUploadController::class, 'download'])->name('download');
        Route::patch('{excel_upload}/status', [ExcelUploadController::class, 'updateStatus'])->name('status');
    });

    Route::resource('excel-uploads', ExcelUploadController::class)->middleware('access:orders');
    /*
 * Route::resource tạo sẵn:
 *  GET    /excel-uploads                       index    excel-uploads.index
 *  GET    /excel-uploads/create                create   excel-uploads.create
 *  POST   /excel-uploads                       store    excel-uploads.store
 *  GET    /excel-uploads/{excel_upload}        show     excel-uploads.show
 *  GET    /excel-uploads/{excel_upload}/edit   edit     excel-uploads.edit
 *  PUT    /excel-uploads/{excel_upload}        update   excel-uploads.update
 *  DELETE /excel-uploads/{excel_upload}        destroy  excel-uploads.destroy
 */
});

//Zalo
//Route::get('get-access-token','App\Http\Controllers\OrderController@getAccessToken');
Route::get('test-zalo', [ZaloController::class, 'test_zalo'])->middleware(['auth', 'isAdmin']);
Route::get('test-zalo-cancle', [ZaloController::class, 'test_zalo_cancle'])->middleware(['auth', 'isAdmin']);
Route::get('get-access-token', [ZaloController::class, 'getAccessTokenFromRefreshToken']);



Route::middleware(['auth', 'isAdmin'])->get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    echo ('View clear succcess');
    Artisan::call('route:clear');
    echo ('route clear is available for configuration ');
});


Route::prefix('clear')->middleware(['auth', 'isAdmin'])->group(function () {
    Route::get('route', [ConfigController::class, 'clearRoute']);
    Route::get('cache', [ConfigController::class, 'clearCache']);
});

Route::get('/clear/route', [ConfigController::class, 'clearRoute'])->middleware(['auth', 'isAdmin']);

Route::post('/upload-image-ck', [PostController::class, 'upload_image_ck'])->name('upload-image-ck')->middleware(['auth', 'isAdmin']);
