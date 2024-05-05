<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PostCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AboutController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

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
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/blog-list', [HomeController::class, 'blog_list']);
Route::post('/search', [HomeController::class, 'search']);
Route::post('/autocomplete-ajax', [HomeController::class, 'autocomplete_ajax']);

//Danh muc bai viet
Route::get('/blogs/{post_slug}', [PostController::class, 'show_category_post_home']);
Route::get('/blog/{post_slug}', [PostController::class, 'show_post_home']);

//Dịch vụ
Route::get('/dich-vu/{service_slug}', [ServiceController::class, 'show_service']);

//About
Route::get('/gioi-thieu', [AboutController::class, 'about']);
Route::get('/gioi-thieu/tai-sao-chon-chung-toi', [AboutController::class, 'aboutWhy']);
Route::get('/gioi-thieu/co-so-vat-chat', [AboutController::class, 'aboutInfrastructure']);

Route::get('/blogs/{category_post_slug}', 'App\Http\Controllers\CategoryPostController@danh_muc_bai_viet');

//Service
Route::get('/dich-vu/thue-xe-x-quang', [AboutController::class, 'serviceX']);

//Contact
Route::get('/lien-he', [ContactController::class, 'contact']);

//Schedule
Route::get('/lichxe', [ScheduleController::class, 'show_schedule']);
Route::post('/select-month', [ScheduleController::class, 'select_month'])->name('select_month');
Route::get('/lichchitiet', [ScheduleController::class, 'login_schedule_details'])->name('schedule-details');
Route::post('/login-schedule', [ScheduleController::class, 'login_schedule']);
Route::group(['middleware' => 'checkSchedule'], function () {
    Route::get('/show-schedule-details', [ScheduleController::class, 'show_schedule_details'])->middleware('checkRoleSchedule')->name('schedule-office');
    Route::post('/call-schedule-details', [ScheduleController::class, 'call_schedule_details'])->middleware('checkRoleSchedule')->name('call-schedule-office');
    Route::post('/suggest-schedule-search', [ScheduleController::class, 'suggest_schedule_search'])->middleware('checkRoleSchedule')->name('suggest-schedule-search');
    Route::post('/schedule-search', [ScheduleController::class, 'schedule_search'])->middleware('checkRoleSchedule')->name('schedule-search');
    Route::get('/lichxechitiet', [ScheduleController::class, 'show_schedule_details_clone'])->name('schedule-sale');
});

//Order
Route::get('/create-order', [OrderController::class, 'create_order']);
Route::post('/save-order-f', [OrderController::class, 'store_order_f']);
Route::get('/successful-medical-registration', [OrderController::class, 'successful_medical_registration']);
Route::get('/create-order/customer-autocomplete', [OrderController::class, 'createCustomerAuto']);
Route::post('/save-order-customer', [OrderController::class, 'store_order_customer']);

Route::post('/select-month-details', [ScheduleController::class, 'select_month_details'])->name('select_month_details');
Route::post('/select-month-details-clone', [ScheduleController::class, 'select_month_details_clone'])->name('select_month_details_clone');
Route::post('/update-order-quantity-draft/{id}', [ScheduleController::class, 'update_order_quantity_draft'])->name('update_order_quantity_draft');
Route::post('/update-order-quantity-details/{id}', [ScheduleController::class, 'update_order_quantity_details'])->name('update_order_quantity_details');
// Route::post('/update-order-warning/{id}', [ScheduleController::class, 'update_order_warning']);
Route::post('/update-order-schedule/{id}', [OrderController::class, 'update_order_schedule'])->name('update_order_schedule');

Route::get('/storage-drive', [OrderController::class, 'Storage']);

Auth::routes();
Route::get('/admin', [AdminController::class, 'login']);
Route::post('admin/logout', [AdminController::class, 'admin_logout'])->name('admin-logout');
//-------------------------------------------- Backend --------------------------------------------
Route::prefix('admin')->middleware(['auth'])->group(function () {
    //Dashboard
    Route::get('/dashboard', [AdminController::class, 'show_dashboard'])->name('dashboard');

    Route::post('/revenue-statistics-by-date', [AdminController::class, 'revenue_statistics_by_date'])->name('url-revenue-statistics-by-date');
    Route::post('/optional-revenue-statistics', [AdminController::class, 'optional_revenue_statistics'])->name('url-optional-revenue-statistics');
    Route::post('/revenue-statistics-for-the-month', [AdminController::class, 'revenue_statistics_for_the_month'])->name('url-revenue-statistics-for-the-month');
    Route::post('/revenue-statistics-by-unit', [AdminController::class, 'revenue_statistics_by_unit'])->name('url-revenue-statistics-by-unit');

    //Information Account
    Route::get('/information', [AdminController::class, 'information'])->name('information');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/store-infomation', [AdminController::class, 'store_information'])->name('store-information');

    //Customer
    Route::prefix('customer')->group(function () {
        Route::get('/list', [CustomerController::class, 'list_customer'])->name('list-customer');
    });
    Route::prefix('contact')->group(function () {
        Route::get('/edit', [ContactController::class, 'edit'])->name('edit-contact');
        Route::post('/store-info', [ContactController::class, 'store_info']);
        Route::post('/update', [ContactController::class, 'update'])->name('update-contact');
    });
    //403
    Route::get('/403', function () {
        return view('403');
    })->name('403');

    //Category Post
    Route::prefix('post-category')->group(function () {
        Route::get('/', [PostCategoryController::class, 'index'])->name('post_category.index');
        Route::get('create', [PostCategoryController::class, 'create'])->name('post_category.create');
        Route::post('save', [PostCategoryController::class, 'store'])->name('post_category.store');
        Route::get('edit/{id}', [PostCategoryController::class, 'edit'])->name('post_category.edit');
        Route::patch('update/{id}', [PostCategoryController::class, 'update'])->name('post_category.update');
        Route::delete('delete/{id}', [PostCategoryController::class, 'destroy'])->name('post_category.destroy');
    });

    //Unit
    Route::prefix('unit')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('unit.index');
        Route::get('create', [UnitController::class, 'create'])->name('unit.create');
        Route::post('save', [UnitController::class, 'store'])->name('unit.store');
        Route::get('edit/{id}', [UnitController::class, 'edit'])->name('unit.edit');
        Route::patch('update/{id}', [UnitController::class, 'update'])->name('unit.update');
        Route::delete('delete/{id}', [UnitController::class, 'destroy'])->name('unit.destroy');
    });

    //Export Excel
    Route::post('/export-excel', [OrderController::class, 'export_excel'])->name('export_excel');

    //Sales
    Route::group(['middleware' => 'isSale'], function () {
        //Service
        Route::prefix('service')->group(function () {
            Route::get('/', [ServiceController::class, 'index'])->name('service.index');
            Route::get('create', [ServiceController::class, 'create'])->name('service.create');
            Route::post('save', [ServiceController::class, 'store'])->name('service.store');
            Route::get('edit/{service}', [ServiceController::class, 'edit'])->name('service.edit');
            Route::patch('update/{service}', [ServiceController::class, 'update'])->name('service.update');
            Route::delete('delete/{service}', [ServiceController::class, 'destroy'])->name('service.destroy');
        });
        //Post
        Route::prefix('post')->group(function () {
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
            Route::get('coppy/{id}', [OrderController::class, 'coppy'])->name('order.coppy');
            Route::post('save-coppy', [OrderController::class, 'store_coppy'])->name('order.store_coppy');
            Route::get('view/{code}', [OrderController::class, 'view'])->name('order.view');
            Route::get('print/{id}', [OrderController::class, 'print'])->name('order.print');
            Route::post('upload', [OrderController::class, 'upload'])->name('upload');
        });
        //Schuedule
        Route::prefix('schedule')->group(function () {
            Route::get('create/{id}', [ScheduleController::class, 'create'])->name('schedule.create');
            Route::post('save', [ScheduleController::class, 'store'])->name('schedule.store');
            Route::get('edit/{id}', [ScheduleController::class, 'edit'])->name('schedule.edit');
            Route::patch('update/{id}', [ScheduleController::class, 'update'])->name('schedule.update');
            Route::post('cancle', [ScheduleController::class, 'cancle'])->name('schedule.cancle');
        });
        //Slider
        Route::prefix('slider')->group(function () {
            Route::get('/', [SliderController::class, 'index'])->name('slider.index');
            Route::get('create', [SliderController::class, 'create'])->name('slider.create');
            Route::post('save', [SliderController::class, 'store'])->name('slider.store');
            Route::get('edit/{id}', [SliderController::class, 'edit'])->name('slider.edit');
            Route::patch('update/{id}', [SliderController::class, 'update'])->name('slider.update');
            Route::delete('delete/{id}', [SliderController::class, 'destroy'])->name('slider.destroy');
        });
    });

    //Admin
    Route::group(['middleware' => 'isAdmin'], function () {
        //Staff
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
            Route::get('/', [OrderController::class, 'index'])->name('history.index');
        });
    });

    //Accountant
    Route::prefix('accountant')->group(function () {
        Route::get('update-order/{id}', [AccountantController::class, 'update_order'])->name('accountant.update_order');
        Route::post('save-order/{id}', [AccountantController::class, 'store_order'])->name('accountant.store_order');
    });
    Route::group(['middleware' => 'isAccountant'], function () {
        //Accountant
        Route::prefix('accountant')->group(function () {
            Route::get('/', [AccountantController::class, 'index'])->name('accountant.index');
            Route::post('get-list', [AccountantController::class, 'get_list'])->name('accountant.get_list');
            Route::patch('update/{id}', [AccountantController::class, 'update'])->name('url-update-accountant');
            Route::post('complete/{id}', [AccountantController::class, 'complete'])->name('accountant.complete');
            Route::post('filter', [AccountantController::class, 'filter'])->name('accountant.filter');
        });
    });
});

//Zalo
//Route::get('get-access-token','App\Http\Controllers\OrderController@getAccessToken');
Route::get('test-zalo', [OrderController::class, 'test_zalo']);
Route::get('test-zalo-cancle', [OrderController::class, 'test_zalo_cancle']);
Route::get('get-access-token', [OrderController::class, 'getAccessTokenFromRefreshToken']);



Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    echo ('View clear succcess');
    Artisan::call('route:clear');
    echo ('route clear is available for configuration ');
});

Route::get('/config-cache', function () {
    Artisan::call('config:cache');
    echo ('Config cache is available for configuration ');
});

Route::post('/upload-image-ck', [PostController::class, 'upload_image_ck'])->name('upload-image-ck');
