<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\MaintenanceTicketController;
use App\Http\Controllers\MoveOutRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\ProviderRatingController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\TenantController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/login', [AuthController::class, 'login']);

Route::post('forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('reset-password', [AuthController::class, 'resetPassword']);


Route::resource('admins', AdminController::class);
Route::resource('landlords', LandlordController::class);
Route::resource('tenants', TenantController::class);
Route::resource('serviceProvider', ServiceProviderController::class);





Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);


   Route::get('user', [AuthController::class, 'fetchUser']);

   Route::get('landlord_tenants', [TenantController::class,'fetchLandlordTenants']);

   Route::get('landlord_properties', [PropertiesController::class, 'fetchLandlordProperties']);

   Route::apiResource('properties', PropertiesController::class);

   Route::get('tenant_tickets', [MaintenanceTicketController::class, 'fetchTenantTickets']);

   Route::get('landlord_tickets', [MaintenanceTicketController::class, 'fetchLandlordTickets']);

   Route::apiResource('maintenance-tickets', MaintenanceTicketController::class);

   Route::post('maintenance-tickets/{ticket_id}/reviews', [ProviderRatingController::class, 'submitRating']);

   Route::get('maintenance-tickets/{ticket_id}/reviews', [ProviderRatingController::class, 'fetchReviews']);

   Route::apiResource('apartments', ApartmentController::class);

   Route::get('properties/{propertyId}/room_types', [PropertiesController::class, 'fetchRoomTypes']);

   Route::apiResource('move-out-requests', MoveOutRequestController::class);


   Route::get('getNotifications', [NotificationController::class,'getNotifications']);





});