<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KplcController;
use App\Http\Controllers\LandlordController;
use App\Http\Controllers\MaintenanceTicketController;
use App\Http\Controllers\MoveOutRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\ProviderRatingController;
use App\Http\Controllers\RentPaymentController;
use App\Http\Controllers\ServiceProviderController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\withdrawFunds;
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

   Route::post('landlord_image', [LandlordController::class, 'update']);


   Route::post('tenant_image', [TenantController::class, 'update']);


   Route::get('user', [AuthController::class, 'fetchUser']);

   Route::get('landlord_tenants', [TenantController::class, 'fetchLandlordTenants']);

   Route::get('landlord_properties', [PropertiesController::class, 'fetchLandlordProperties']);

   Route::apiResource('properties', PropertiesController::class);

   Route::get('tenant_tickets', [MaintenanceTicketController::class, 'fetchTenantTickets']);

   Route::get('landlord_tickets', [MaintenanceTicketController::class, 'fetchLandlordTickets']);

   Route::apiResource('maintenance-tickets', MaintenanceTicketController::class);

   Route::post('maintenance-tickets/{ticket_id}/reviews', [ProviderRatingController::class, 'submitRating']);

   Route::get('maintenance-tickets/{ticket_id}/reviews', [ProviderRatingController::class, 'fetchReviews']);



   Route::get('properties/{propertyId}/room_types', [PropertiesController::class, 'fetchRoomTypes']);

   Route::get('properties/{propertyId}/tenants', [PropertiesController::class, 'fetchPropertyTenants']);

   Route::apiResource('move-out-requests', MoveOutRequestController::class);

   Route::get('move-out-requests-property', [MoveOutRequestController::class, 'indexByProperty']);

   Route::get('getNotifications', [NotificationController::class, 'getNotifications']);


   Route::get('getTenantTransactions', [PaymentController::class, 'getTenantTransactions']);

   Route::get('getTransactions', [PaymentController::class, 'getAllTransactions']);


   Route::get('getLandlordTransactions', [PaymentController::class, 'getLandlordTransactions']);


   // Route to add funds
   Route::post('/wallet/add-funds', [WalletController::class, 'addFunds']);
   Route::post('/wallet/remove-funds', [WalletController::class, 'removeFunds']);
   Route::get('/wallet/get-balance', [WalletController::class, 'getBalance']);


   //Route to pay Rent

   Route::post('/mpesa/payRent', [WalletController::class, 'payRent']);

   //routes to withdraw funds 

   Route::post('/wallet/withdraw-funds', [withdrawFunds::class, 'withdrawFunds']);

   //fetch Tenants paid rent data 
   Route::get('landlord/{landlordId}/tenants-paid-rent', [RentPaymentController::class, 'fetchLandlordTenantsWhoPaidRent']);
   // Fetch rent paid by a specific tenant
   Route::get('tenant/{tenantId}/rent-payments', [RentPaymentController::class, 'fetchTenantRentPayments']);

   // Fetch all tenants who have paid rent
   Route::get('tenants/paid-rent', [RentPaymentController::class, 'fetchTenantsWhoPaidRent']);

   Route::get('rent-paid-tenants', [RentPaymentController::class, 'fetchAllTenants']);


   //kplc and utilities

   Route::post('kplc_tokens', [KplcController::class, 'buyTokens']);
});

Route::apiResource('apartments', ApartmentController::class);
