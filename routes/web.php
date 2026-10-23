<?php

use App\Http\Controllers\Crm;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StoreLeadController;
use App\Http\Controllers\StoreTrackingEventsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::post('leads', StoreLeadController::class)->middleware('throttle:leads')->name('leads.store');
Route::post('e', StoreTrackingEventsController::class)->middleware('throttle:tracking')->name('tracking.store');

Route::prefix('gestao-crm')->name('crm.')->middleware('noindex')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Crm\LoginController::class, 'create'])->name('login');
        Route::post('login', [Crm\LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', Crm\DashboardController::class)->name('dashboard');
        Route::post('logout', [Crm\LoginController::class, 'destroy'])->name('logout');

        Route::get('pipeline', Crm\PipelineController::class)->name('pipeline');
        Route::get('leads/exportar', Crm\LeadExportController::class)->name('leads.export');
        Route::resource('leads', Crm\LeadController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::patch('leads/{lead}/etapa', [Crm\LeadStatusController::class, 'update'])->name('leads.status');
        Route::post('leads/{lead}/notas', [Crm\LeadNoteController::class, 'store'])->name('leads.notes');
        Route::patch('leads/{lead}/proxima-acao', [Crm\LeadNextActionController::class, 'update'])->name('leads.next-action');
        Route::post('leads/{lead}/vendas', [Crm\LeadSaleController::class, 'store'])->name('leads.sales.store');

        Route::get('vendas', [Crm\SaleController::class, 'index'])->name('sales.index');
        Route::patch('cobrancas/{payment}/pagar', [Crm\PaymentController::class, 'pay'])->name('payments.pay');
        Route::patch('cobrancas/{payment}/estornar', [Crm\PaymentController::class, 'refund'])->name('payments.refund');
        Route::patch('assinaturas/{subscription}/cancelar', [Crm\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });
});
