<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AppContentController;

Route::get("clear-cache",function() {
  Cache::flush();
  return true;
});

// All Authenticated User
Route::group(['middleware' => ['auth:api','auth.custom','cors.custom']], function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('check-user', [AuthController::class, 'checkLogin']);

    // Role
    Route::get('role', [RoleController::class, 'index']);
    Route::prefix('role')->group(function() {
        Route::get('by-id/{id}', [RoleController::class, 'show']);
    });

    // User
    Route::get('user-by-id', [UserController::class, 'show']);
    Route::prefix('user')->group(function() {
        Route::post('update-profile', [UserController::class, 'updateProfile']);
        Route::post('upload-avatar', [UserController::class, 'uploadAvatar']);
        Route::post('remove-avatar', [UserController::class, 'removeAvatar']);
    });

    // Admin Only
    Route::group(['middleware' => ['admin.panel']], function () {
        // User
        // Route::get('user', [UserController::class, 'index']);
        // Route::post('user-paginate', [UserController::class, 'paginate']);
        // Route::post('user', [UserController::class, 'store']);
        // Route::prefix('user')->group(function() {
        //     // Route::post('delete', [UserController::class, 'destroy']);
        //     Route::post('update', [UserController::class, 'update']);
        //     Route::post('create', [UserController::class, 'store']);
        //     Route::post('search-name', [UserController::class, 'searchName']);
        //     Route::post('search-email', [UserController::class, 'searchEmail']);
        //     Route::post('change-password', [UserController::class, 'changePassword']);
        //     Route::post('get-by-pickup-name-phone', [UserController::class, 'getByPickupNamePhone']);
        //     Route::post('get-default-by-pickup-name-phone', [UserController::class, 'getDefaultByPickupNamePhone']);
        //     Route::post('get-default-by-name-phone', [UserController::class, 'getDefaultByNamePhone']);
        //     Route::post('search-by-name-phone-email', [UserController::class, 'getByNamePhoneEmail']);
        // });

        // // Role
        // Route::prefix('role')->group(function() {
        //     Route::get('list-feature', [RoleController::class, 'featureList']);
        //     Route::post('paginate', [RoleController::class, 'paginate']);
        //     Route::post('update', [RoleController::class, 'update']);
        //     Route::post('create', [RoleController::class, 'store']);
        //     Route::post('delete', [RoleController::class, 'destroy']);
        // });

        // Menu
        Route::get('menu', [MenuController::class, 'index']);
        Route::get('menu/privilleges', [MenuController::class, 'getAccessibleMenu']);

        // APP CONTENT
        Route::prefix('app-content')->group(function() {
            Route::post('upload-image', [AppContentController::class, 'uploadImage']);

            // Route::prefix('banner')->group(function() {
            //     Route::post('create', [AppContentController::class, 'createBanner']);
            //     Route::post('paginate', [AppContentController::class, 'getDataBannerPaginate']);
            //     Route::post('update', [AppContentController::class, 'editBanner']);
            //     Route::post('delete', [AppContentController::class, 'deleteBanner']);
            // });

            Route::prefix('article')->group(function() {
                Route::post('create', [AppContentController::class, 'createArticle']);
                Route::post('paginate', [AppContentController::class, 'getDataArticlePaginate']);
                Route::post('update', [AppContentController::class, 'editArticle']);
                Route::post('delete', [AppContentController::class, 'deleteArticle']);
            });
        });
    });
});

// Guest / All User
Route::middleware('guest')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login-web', [AuthController::class, 'loginWeb'])->name('loginWeb');
    Route::post('refresh-token', [AuthController::class, 'refreshToken'])->name('refreshToken');
    Route::prefix('user')->group(function() {
        Route::post('forgot-password', [UserController::class, 'forgotPassword']);
    });

    // Test
    Route::post('test', [TestController::class, 'store']);
    Route::get('test', [TestController::class, 'index']);

    Route::prefix('customer')->group(function() {
        Route::prefix('app-content')->group(function() {
            Route::prefix('banner')->group(function() {
                Route::get('all', [AppContentController::class, 'getAllDataBanner']);
            });

            Route::prefix('article')->group(function() {
                Route::post('paginate', [AppContentController::class, 'getDataArticlePaginate']);
            });
        });
    });
});
