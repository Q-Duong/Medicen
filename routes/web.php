<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryPostController;
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
    Route::post('/save-infomation', [AdminController::class, 'save_information'])->name('save-information');

    //Customer
    Route::prefix('customer')->group(function () {
        Route::get('/list', [CustomerController::class, 'list_customer'])->name('list-customer');
    });
    Route::prefix('contact')->group(function () {
        Route::get('/edit', [ContactController::class, 'edit'])->name('edit-contact');
        Route::post('/save-info', [ContactController::class, 'save_info']);
        Route::post('/update', [ContactController::class, 'update'])->name('update-contact');
    });
    //403
    Route::get('/403', function () {
        return view('403');
    })->name('403');

    //Category Post
    Route::prefix('category-post')->group(function () {
        Route::get('/add', [CategoryPostController::class, 'add'])->name('add-category-post');
        Route::get('/list', [CategoryPostController::class, 'list'])->name('list-category-post');
        Route::get('/edit/{category_post_id}', [CategoryPostController::class, 'edit'])->name('edit-category-post');
        Route::get('/delete/{category_post_id}', [CategoryPostController::class, 'delete'])->name('delete-category-post');
        Route::post('/save', [CategoryPostController::class, 'save'])->name('save-category-post');
        Route::post('/update/{category_post_id}', [CategoryPostController::class, 'update'])->name('update-category-post');
    });

    //Unit
    Route::prefix('unit')->group(function () {
        Route::get('/add', [UnitController::class, 'add'])->name('add-unit');
        Route::get('/list', [UnitController::class, 'list'])->name('list-unit');
        Route::get('/edit/{unit_id}', [UnitController::class, 'edit'])->name('edit-unit');
        Route::get('/delete/{unit_id}', [UnitController::class, 'delete'])->name('delete-unit');
        Route::post('/save', [UnitController::class, 'save'])->name('save-unit');
        Route::post('/update/{unit_id}', [UnitController::class, 'update'])->name('update-unit');
    });

    //Export Excel
    Route::post('/export-excel', [OrderController::class, 'export_excel'])->name('export-excel');

    //Sales
    Route::group(['middleware' => 'isSale'], function () {
        //Service
        Route::prefix('service')->group(function () {
            Route::get('/add', [ServiceController::class, 'add_service'])->name('add-service');
            Route::get('/list', [ServiceController::class, 'list_service'])->name('list-service');
            Route::get('/edit/{service}', [ServiceController::class, 'edit_service'])->name('edit-service');
            Route::get('/delete/{service}', [ServiceController::class, 'delete_service'])->name('delete-service');
            Route::post('/save', [ServiceController::class, 'save_service'])->name('save-service');
            Route::post('/update/{service}', [ServiceController::class, 'update_service'])->name('update-service');
        });
        //Post
        Route::prefix('post')->group(function () {
            Route::get('/add', [PostController::class, 'add_post'])->name('add-post');
            Route::get('/list', [PostController::class, 'list_post'])->name('list-post');
            Route::get('/edit/{post_id}', [PostController::class, 'edit_post'])->name('edit-post');
            Route::get('/delete/{post_id}', [PostController::class, 'delete_post'])->name('delete-post');
            Route::post('/save', [PostController::class, 'save_post'])->name('save-post');
            Route::post('/update/{post_id}', [PostController::class, 'update_post'])->name('update-post');
        });
        //Order
        Route::prefix('order')->group(function () {
            Route::get('/view/{order_code}', [OrderController::class, 'view_order'])->name('view-order');
            Route::get('/print/{order_id}', [OrderController::class, 'print_order'])->name('print-order');
            Route::get('/add', [OrderController::class, 'add_order'])->name('add-order');
            Route::get('/list', [OrderController::class, 'list_order'])->name('list-order');
            Route::get('/edit/{order_id}', [OrderController::class, 'edit_order'])->name('edit-order');
            Route::get('/delete/{order_id}', [OrderController::class, 'delete_order'])->name('delete-order');
            Route::post('/save', [OrderController::class, 'save_order'])->name('save-order');
            Route::post('/update/{order_id}', [OrderController::class, 'update_order'])->name('update-order');
            Route::get('/coppy/{order_id}', [OrderController::class, 'coppy_order'])->name('coppy-order');
            Route::post('/save-coppy', [OrderController::class, 'save_coppy_order'])->name('save-order-coppy');
            Route::post('/upload', [OrderController::class, 'upload'])->name('upload');
        });
        //Schuedule
        Route::prefix('schedule')->group(function () {
            Route::get('/add/{order_id}', [OrderController::class, 'add_schedule'])->name('add-schedule');
            Route::post('/save', [OrderController::class, 'save_schedule'])->name('save-schedule');
            Route::get('/edit/{order_id}', [OrderController::class, 'edit_schedule'])->name('edit-schedule');
            Route::post('/update/{order_id}', [OrderController::class, 'update_schedule'])->name('update-schedule');
            Route::post('/cancle', [OrderController::class, 'cancle_schedule'])->name('cancle-schedule');
        });
        //Slider
        Route::prefix('slider')->group(function () {
            Route::get('/list', [SliderController::class, 'list_slider'])->name('list-slider');
            Route::get('/add', [SliderController::class, 'add_slider'])->name('add-slider');
            Route::get('/delete/{slider_id}', [SliderController::class, 'delete_slider'])->name('delete-slider');
            Route::get('/edit/{slider_id}', [SliderController::class, 'edit_slider'])->name('edit-slider');
            Route::post('/insert', [SliderController::class, 'insert_slider'])->name('insert-slider');
            Route::post('/update/{slider_id}', [SliderController::class, 'update_slider'])->name('update-slider');
        });
    });

    //Admin
    Route::group(['middleware' => 'isAdmin'], function () {
        //Staff
        Route::prefix('staff')->group(function () {
            Route::get('/add', [StaffController::class, 'add_staff'])->name('add-staff');
            Route::get('/list', [StaffController::class, 'list_staff'])->name('list-staff');
            Route::get('/edit/{staff_id}', [StaffController::class, 'edit_staff'])->name('edit-staff');
            Route::get('/delete/{staff_id}', [StaffController::class, 'delete_staff'])->name('delete-staff');
            Route::post('/save', [StaffController::class, 'save_staff'])->name('save-staff');
            Route::post('/update/{staff_id}', [StaffController::class, 'update_staff'])->name('update-staff');
        });
        //History
        Route::prefix('history')->group(function () {
            Route::get('/list', [OrderController::class, 'list_history_order'])->name('list-history');
        });
    });
    
    //Accountant
    Route::prefix('accountant')->group(function () {
        Route::get('/update-order/{order_id}', [AccountantController::class, 'update_order_accountant'])->name('update-order-accountant');
        Route::post('/save-order/{order_id}', [AccountantController::class, 'save_order_accountant'])->name('save-order-accountant');
    });
    Route::group(['middleware' => 'isAccountant'], function () {
        //Accountant
        Route::prefix('accountant')->group(function () {
            Route::post('/get-list-order', [AccountantController::class, 'call_list_order_accountant'])->name('url-get-list-accountant');
            Route::get('/list-order', [AccountantController::class, 'list_order_accountant'])->name('list-order-accountant');
            Route::post('/update/{order_id}', [AccountantController::class, 'update_accountant'])->name('url-update-accountant');
            Route::post('/complete/{order_id}', [AccountantController::class, 'complete_accountant'])->name('url-complete-accountant');
            Route::post('/filter-accountant', [AccountantController::class, 'filter_accountant'])->name('url-filter-accountant');
        });
    });
});

Route::get('/create-order', [OrderController::class, 'create_order']);
Route::post('/save-order-f', [OrderController::class, 'save_order_f']);
Route::get('/successful-medical-registration', [OrderController::class, 'successful_medical_registration']);
Route::get('/create-order/customer-autocomplete', [OrderController::class, 'createCustomerAuto']);
Route::post('/save-order-customer', [OrderController::class, 'save_order_customer']);

//Document
Route::delete('delete-file-order/{path}', [OrderController::class, 'delete_file_order'])->name('url-delete-file-order');
Route::get('upload_file', 'App\Http\Controllers\DocumentController@upload_file');
Route::get('upload_image', 'App\Http\Controllers\DocumentController@upload_image');
Route::get('create_folder', 'App\Http\Controllers\DocumentController@create_folder');
Route::get('rename_folder', 'App\Http\Controllers\DocumentController@rename_folder');
Route::get('list_document', 'App\Http\Controllers\DocumentController@list_document');
Route::get('read_data', 'App\Http\Controllers\DocumentController@read_data');

//Zalo
//Route::get('get-access-token','App\Http\Controllers\OrderController@getAccessToken');
Route::get('test-zalo', [OrderController::class, 'test_zalo']);
Route::get('test-zalo-cancle', [OrderController::class, 'test_zalo_cancle']);
Route::get('get-access-token', [OrderController::class, 'getAccessTokenFromRefreshToken']);

Route::get('/config-cache', function() {
    Artisan::call('config:cache');
    echo('Config cache is available for configuration ');
});

Route::get('/view-clear', function() {
    // Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    echo('View clear succcess');
});

Route::get('/route-clear', function() {
    Artisan::call('route:clear');
    echo('route clear is available for configuration ');
});

Route::post('/upload-image-ck', [PostController::class, 'upload_image_ck'])->name('upload-image-ck');