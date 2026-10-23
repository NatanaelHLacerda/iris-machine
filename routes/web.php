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
        Route::get('dashboard/{tab}', Crm\DashboardController::class)->name('dashboard.tab');
        Route::put('meta-mensal', [Crm\GoalSettingController::class, 'update'])->name('settings.goal');
        Route::post('logout', [Crm\LoginController::class, 'destroy'])->name('logout');

        Route::get('pipeline', Crm\PipelineController::class)->name('pipeline');
        Route::get('leads/exportar', Crm\LeadExportController::class)->name('leads.export');
        Route::resource('leads', Crm\LeadController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
        Route::patch('leads/{lead}/etapa', [Crm\LeadStatusController::class, 'update'])->name('leads.status');
        Route::post('leads/{lead}/notas', [Crm\LeadNoteController::class, 'store'])->name('leads.notes');
        Route::patch('leads/{lead}/proxima-acao', [Crm\LeadNextActionController::class, 'update'])->name('leads.next-action');
        Route::post('leads/{lead}/vendas', [Crm\LeadSaleController::class, 'store'])->name('leads.sales.store');

        Route::get('vendas', [Crm\SaleController::class, 'index'])->name('sales.index');

        Route::get('meta-ads', Crm\AdsController::class)->name('ads');
        Route::post('meta-ads/importar', [Crm\AdSpendImportController::class, 'store'])->name('ads.import');
        Route::post('meta-ads/gastos', [Crm\AdSpendController::class, 'store'])->name('ads.store');

        Route::get('caixa', Crm\CashController::class)->name('cash');
        Route::post('caixa/lancamentos', [Crm\CashEntryController::class, 'store'])->name('cash.entries.store');
        Route::put('caixa/lancamentos/{cashEntry}', [Crm\CashEntryController::class, 'update'])->name('cash.entries.update');
        Route::delete('caixa/lancamentos/{cashEntry}', [Crm\CashEntryController::class, 'destroy'])->name('cash.entries.destroy');
        Route::put('caixa/saldo-inicial', [Crm\OpeningBalanceController::class, 'update'])->name('cash.opening');
        Route::patch('cobrancas/{payment}/pagar', [Crm\PaymentController::class, 'pay'])->name('payments.pay');
        Route::patch('cobrancas/{payment}/estornar', [Crm\PaymentController::class, 'refund'])->name('payments.refund');
        Route::patch('assinaturas/{subscription}/cancelar', [Crm\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    });
});
