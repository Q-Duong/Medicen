<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\ContractController;
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
//News
// Route::prefix('tin-tuc')->group(function () {
//     Route::get('/', [PostController::class, 'showPostCategories'])->name('blog.category');
//     Route::get('/{post_category_slug}', [PostController::class, 'showPostCategoriesSlug'])->name('blog.category_slug');
//     Route::get('/{post_category_slug}/{post_slug}', [PostController::class, 'showPostInCategories'])->name('blog.post_in_category');
// });
//Blog
Route::prefix('blog')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('{blog_category_slug}', [BlogCategoryController::class, 'index'])->name('blog_category.index');
    Route::get('{blog_category_slug}/{blog_slug}', [BlogController::class, 'detail'])->name('blog.detail');
});
//Service
Route::get('/dich-vu/{service_slug}', [ServiceController::class, 'show'])->name('service.details');
//About
Route::prefix('gioi-thieu')->group(function () {
    Route::get('/', [AboutController::class, 'index'])->name('about.index');
    Route::get('/{about_slug}', [AboutController::class, 'showBySlug'])->name('about.show_by_slug');
});
//Contact
Route::get('lien-he', [ContactController::class, 'show'])->name('contact.index');
//Order
Route::get('dang-ky', [OrderController::class, 'createOrderClient'])->name('order.clients.create');
Route::post('save-order-client', [OrderController::class, 'storeOrderClient'])->name('order.clients.store');
Route::get('dang-ky/chi-tiet', [OrderController::class, 'createOrderDetailsClient'])->name('order.clients.create_details');
Route::post('save-order-details-client', [OrderController::class, 'storeOrderDetailsClient'])->name('order.clients.store_details');
Route::get('successful-medical-registration', [OrderController::class, 'successfulRegistration'])->name('order.clients.alert');

//QR Code
Route::get('create-qr-code', [QrController::class, 'index'])->name('qr.index');

Route::get('hash-password', function () {
    dd(Hash::make('Medicen@2025'));
});

//Schedule
Route::prefix('schedules')->group(function () {
    Route::get('login', [ScheduleController::class, 'showLogin'])->name('schedules.login');
    Route::post('login', [ScheduleController::class, 'login'])->name('schedules.login.post');

    //Drivers
    Route::prefix('drivers')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('schedules.drivers.index');
        Route::post('select', [ScheduleController::class, 'select'])->name('schedules.drivers.select');
    });

    Route::group(['middleware' => 'schedules.login'], function () {
        //Sales
        Route::group(['middleware' => 'schedules.role.sales'], function () {
            Route::prefix('sales')->group(function () {
                Route::get('/', [ScheduleSalesController::class, 'index'])->name('schedules.sales.index');
                Route::post('select', [ScheduleSalesController::class, 'select'])->name('schedules.sales.select');
            });
        });

        //Technicians
        Route::group(['middleware' => 'schedules.role.technicians'], function () {
            Route::prefix('technicians')->group(function () {
                Route::get('/', [ScheduleTechniciansController::class, 'index'])->name('schedules.technicians.index');
                Route::post('select', [ScheduleTechniciansController::class, 'select'])->name('schedules.technicians.select');
                Route::post('update', [ScheduleTechniciansController::class, 'update'])->name('schedules.technicians.update');
            });
        });

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
Route::prefix('admin')->middleware(['auth'])->group(function () {
    //Dashboard
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard.index');
    Route::get('chat', [AdminController::class, 'chat'])->name('dashboard.chat');

    //Profit
    Route::prefix('profit')->group(function () {
        Route::get('calculate', [ProfitController::class, 'index'])->name('profit.index');
        Route::post('save', [ProfitController::class, 'store'])->name('profit.save');
        Route::get('report/{id}', [ProfitController::class, 'show'])->name('profit.show');
        Route::delete('report/{id}', [ProfitController::class, 'destroy'])->name('profit.delete');
        Route::post('report/{id}/approve', [ProfitController::class, 'approve'])->name('profit.approve');
    });

    //Statistics
    Route::prefix('statistics')->group(function () {
        Route::post('revenue-by-date', [StatisticsController::class, 'revenueByDate'])->name('statistics.revenue_by_date');
        Route::post('optional-revenue', [StatisticsController::class, 'optionalRevenue'])->name('statistics.optional_revenue');
        Route::post('revenue-for-the-month', [StatisticsController::class, 'revenueForTheMonth'])->name('statistics.revenue_for_the_month');
        Route::post('revenue-by-unit', [StatisticsController::class, 'revenueByUnit'])->name('statistics.revenue_by_unit');
        Route::get('/', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::post('performance-analysis', [StatisticsController::class, 'performanceAnalysis'])->name('statistics.performance_analysis');
    });

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

    //File
    Route::prefix('file')->group(function () {
        Route::post('process', [FileController::class, 'process'])->name('file.process');
        Route::delete('revert', [FileController::class, 'revert'])->name('file.revert');
        Route::delete('delete-file-order', [FileController::class, 'destroyFileOrder'])->name('file.delete_file_order');
        Route::delete('delete-file-total', [FileController::class, 'destroyFileTotal'])->name('file.delete_file_total');
    });

    //Export Excel
    Route::post('/export-excel', [OrderController::class, 'exportExcel'])->name('export.excel');


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
        });

        //schedule
        Route::prefix('schedule')->group(function () {
            Route::get('create/{id}', [ScheduleSalesController::class, 'create'])->name('schedule.create');
            Route::post('save', [ScheduleSalesController::class, 'store'])->name('schedule.store');
            Route::get('edit/{id}', [ScheduleSalesController::class, 'edit'])->name('schedule.edit');
            Route::patch('update/{id}', [ScheduleSalesController::class, 'update'])->name('schedule.update');
            Route::post('cancel', [ScheduleSalesController::class, 'cancel'])->name('schedule.cancel');
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
            Route::get('/', [HistoryController::class, 'index'])->name('history.index');
        });
    });

    //Accountant
    Route::prefix('accountant')->group(function () {
        Route::get('update-order/{id}', [AccountantController::class, 'updateOrder'])->name('accountant.order.update');
        Route::post('save-order/{id}', [AccountantController::class, 'storeOrder'])->name('accountant.order.store');
    });
    Route::group(['middleware' => 'isAccountant'], function () {
        //Accountant
        Route::prefix('accountant')->group(function () {
            Route::get('/', [AccountantController::class, 'index'])->name('accountant.index');
            Route::post('get-accountant', [AccountantController::class, 'getAccountant'])->name('accountant.get');
            Route::patch('update', [AccountantController::class, 'updateRow'])->name('accountant.update');
            Route::patch('complete', [AccountantController::class, 'complete'])->name('accountant.complete');
            Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant.filter_options');
        });
    });

    Route::group(['middleware' => 'isSale'], function () {
        Route::prefix('accountant-sales')->group(function () {
            Route::get('/', [AccountantController::class, 'indexSales'])->name('accountant_sales.index');
            Route::post('get-accountant', [AccountantController::class, 'getAccountantSales'])->name('accountant_sales.get');
            Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant_sales.filter_options');
        });
    });

    Route::prefix('accountant-result')->group(function () {
        Route::get('/', [AccountantController::class, 'indexResult'])->name('accountant_result.index');
        Route::post('get-accountant', [AccountantController::class, 'getAccountantResult'])->name('accountant_result.get');
        Route::post('filter-options', [AccountantController::class, 'getFilterOptions'])->name('accountant_result.filter_options');
    });

    Route::prefix('contract')->group(function () {
        Route::get('/', [ContractController::class, 'index'])->name('contract.index');
        Route::post('get-contract', [ContractController::class, 'getContract'])->name('contract.get');
        Route::patch('update', [ContractController::class, 'update'])->name('contract.update');
        Route::post('filter', [ContractController::class, 'filter'])->name('contract.filter');
        Route::prefix('view-only')->group(function () {
            Route::get('/', [ContractController::class, 'indexViewOnly'])->name('contract.view_only.index');
            Route::post('get-contract', [ContractController::class, 'getContractViewOnly'])->name('contract.view_only.get');
            Route::post('filter', [ContractController::class, 'filterViewOnly'])->name('contract.view_only.filter');
        });
    });
});

//Zalo
//Route::get('get-access-token','App\Http\Controllers\OrderController@getAccessToken');
Route::get('test-zalo', [ZaloController::class, 'test_zalo']);
Route::get('test-zalo-cancle', [ZaloController::class, 'test_zalo_cancle']);
Route::get('get-access-token', [ZaloController::class, 'getAccessTokenFromRefreshToken']);



Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    echo ('View clear succcess');
    Artisan::call('route:clear');
    echo ('route clear is available for configuration ');
});


Route::prefix('clear')->group(function () {
    Route::get('route', [ConfigController::class, 'clearRoute']);
    Route::get('cache', [ConfigController::class, 'clearCache']);
});

Route::get('/clear/route', [ConfigController::class, 'clearRoute']);

Route::post('/upload-image-ck', [PostController::class, 'upload_image_ck'])->name('upload-image-ck');
