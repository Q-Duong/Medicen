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
Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::post('/search', [HomeController::class, 'search']);
Route::post('/autocomplete-ajax', [HomeController::class, 'autocomplete_ajax']);
//News
Route::prefix('tin-tuc')->group(function () {
    Route::get('/', [PostController::class, 'showPostCategories'])->name('blog.category');
    Route::get('/{post_category_slug}', [PostController::class, 'showPostCategoriesSlug'])->name('blog.category_slug');
    Route::get('/{post_category_slug}/{post_slug}', [PostController::class, 'showPostInCategories'])->name('blog.post_in_category');
});
//Service
Route::get('/dich-vu/{service_slug}', [ServiceController::class, 'show'])->name('service.show');
//About
Route::prefix('gioi-thieu')->group(function () {
    Route::get('/', [AboutController::class, 'show'])->name('about.show');
    Route::get('/{about_slug}', [AboutController::class, 'showBySlug'])->name('about.show_by_slug');
});
//Contact
Route::get('lien-he', [ContactController::class, 'show'])->name('contact.show');
//Order
Route::get('dang-ky', [OrderController::class, 'createOrderClient'])->name('order.clients.create');
Route::post('save-order-client', [OrderController::class, 'storeOrderClient'])->name('order.clients.store');
Route::get('dang-ky/chi-tiet', [OrderController::class, 'createOrderDetailsClient'])->name('order.clients.create_details');
Route::post('save-order-details-client', [OrderController::class, 'storeOrderDetailsClient'])->name('order.clients.store_details');
Route::get('successful-medical-registration', [OrderController::class, 'successfulRegistration'])->name('order.clients.alert');
//Schedule
Route::get('lichxe', [ScheduleController::class, 'showSchedule'])->name('schedule.show_ktv');
Route::post('schedule-select-month', [ScheduleController::class, 'selectMonth'])->name('schedule.select_month');
Route::post('/update-quantity-ktv/{id}', [ScheduleController::class, 'updateQuantityKTV'])->name('schedule.update_quantity_ktv');
Route::get('/lichchitiet', [ScheduleController::class, 'loginScheduleDetails'])->name('schedule.login_details');
Route::post('/login-schedule', [ScheduleController::class, 'login_schedule']);
Route::group(['middleware' => 'checkSchedule'], function () {
    Route::get('/show-schedule-details', [ScheduleController::class, 'showScheduleDetails'])->middleware('checkRoleSchedule')->name('schedule.show_details');
    Route::post('/call-schedule-details', [ScheduleController::class, 'getScheduleDetails'])->middleware('checkRoleSchedule')->name('schedule.get');
    Route::post('/schedule-search-suggest', [ScheduleController::class, 'scheduleSearchSuggest'])->middleware('checkRoleSchedule')->name('schedule.search_suggest');
    Route::post('/schedule-search', [ScheduleController::class, 'scheduleSearch'])->middleware('checkRoleSchedule')->name('schedule.search');
    Route::get('/lichxechitiet', [ScheduleController::class, 'showScheduleSale'])->name('schedule.show_sale');
});


Route::post('/select-month-details', [ScheduleController::class, 'select_month_details'])->name('select_month_details');
Route::post('/select-month-details-clone', [ScheduleController::class, 'select_month_details_clone'])->name('select_month_details_clone');
Route::post('/update-order-quantity-details/{id}', [ScheduleController::class, 'update_order_quantity_details'])->name('update_order_quantity_details');
// Route::post('/update-order-warning/{id}', [ScheduleController::class, 'update_order_warning']);
Route::post('/update-order-schedule/{id}', [OrderController::class, 'update_order_schedule'])->name('update_order_schedule');

Route::get('/storage-drive', [OrderController::class, 'Storage']);

Auth::routes();
Route::get('/admin', [AdminController::class, 'login']);
Route::post('admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
//-------------------------------------------- Backend --------------------------------------------
Route::prefix('admin')->middleware(['auth'])->group(function () {
    //Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard.index');

    Route::post('/revenue-statistics-by-date', [AdminController::class, 'revenue_statistics_by_date'])->name('url-revenue-statistics-by-date');
    Route::post('/optional-revenue-statistics', [AdminController::class, 'optional_revenue_statistics'])->name('url-optional-revenue-statistics');
    Route::post('/revenue-statistics-for-the-month', [AdminController::class, 'revenue_statistics_for_the_month'])->name('url-revenue-statistics-for-the-month');
    Route::post('/revenue-statistics-by-unit', [AdminController::class, 'revenue_statistics_by_unit'])->name('url-revenue-statistics-by-unit');

    //Information Account
    Route::get('/information', [AdminController::class, 'information'])->name('information');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/save-infomation', [AdminController::class, 'store_information'])->name('store-information');

    //Customer
    Route::prefix('customer')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('customer.index');
    });
    Route::prefix('contact')->group(function () {
        Route::get('edit', [ContactController::class, 'edit'])->name('contact.edit');
        Route::patch('update', [ContactController::class, 'update'])->name('contact.update');
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
            Route::get('edit/{id}', [ServiceController::class, 'edit'])->name('service.edit');
            Route::patch('update/{id}', [ServiceController::class, 'update'])->name('service.update');
            Route::delete('delete/{id}', [ServiceController::class, 'destroy'])->name('service.destroy');
        });
        //About
        Route::prefix('about')->group(function () {
            Route::get('/', [AboutController::class, 'index'])->name('about.index');
            Route::get('create', [AboutController::class, 'create'])->name('about.create');
            Route::post('save', [AboutController::class, 'store'])->name('about.store');
            Route::get('edit/{id}', [AboutController::class, 'edit'])->name('about.edit');
            Route::patch('update/{id}', [AboutController::class, 'update'])->name('about.update');
            Route::delete('delete/{id}', [AboutController::class, 'destroy'])->name('about.destroy');
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
            Route::get('copy/{id}', [OrderController::class, 'copy'])->name('order.copy');
            Route::post('save-copy', [OrderController::class, 'storeCopy'])->name('order.store_copy');
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
            Route::post('cancel', [ScheduleController::class, 'cancel'])->name('schedule.cancel');
        });
        //Slider
        Route::prefix('slider')->group(function () {
            Route::get('/', [SliderController::class, 'index'])->name('slider.index');
            Route::get('create', [SliderController::class, 'create'])->name('slider.create');
            Route::post('save', [SliderController::class, 'insert'])->name('slider.insert');
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
            Route::get('/', [OrderController::class, 'list_history_order'])->name('history.list_history');
        });
    });

    //Accountant
    Route::prefix('accountant')->group(function () {
        Route::get('update-order/{id}', [AccountantController::class, 'updateOrder'])->name('accountant.update_order');
        Route::post('save-order/{id}', [AccountantController::class, 'storeOrder'])->name('accountant.store_order');
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
