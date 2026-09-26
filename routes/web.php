<?php

use App\Http\Controllers\System\Auth\{AuthenticatedSessionController};
use App\Http\Controllers\System\Essentials\{ReportController};
use App\Http\Controllers\System\Notifications\{NotificationController};
use Illuminate\Support\Facades\{Route};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

$systemRoute = __DIR__."/System";

$guestRoute = __DIR__."/Guest";

// Guest Routes (Public)
Route::prefix("{company_slug}/book_complaints")->middleware("company.exists")->group($guestRoute."/BookComplaint.php");
Route::prefix("{company_slug}/home")->middleware("company.exists")->group($guestRoute."/Home.php");
Route::prefix("{company_slug}/tracking_attendances")->middleware("company.exists")->group($guestRoute."/TrackingAttendance.php");
Route::prefix("{company_slug}/biometric_devices")->middleware("company.exists")->group($guestRoute."/BiometricDevice.php");

Route::get("/shared/reports/sale/{sale}/{type}", [ReportController::class, "sharedSale"])
    ->middleware(["signed", "throttle:guest-status"])
    ->name("reports.sale.shared");

Route::middleware("guest")->group(function() {

    Route::get("/", [AuthenticatedSessionController::class, "create"]);
    Route::get("login", [AuthenticatedSessionController::class, "create"])->name("login");
    Route::post("login", [AuthenticatedSessionController::class, "store"]);

});

Route::middleware(["auth", "verified", "module.permission", "resource.scope", "navigation.track"])->group(function() use ($systemRoute) {

    Route::post("/send-subscription-emails", [NotificationController::class, "sendSubscriptionEmails"])
        ->middleware("throttle:6,1")
        ->name("send-subscription-emails");

    // Assets
    Route::prefix("/assets")->group($systemRoute."/Assets/Asset.php");
    Route::prefix("/assets_management")->group($systemRoute."/Assets/AssetManagement.php");

    // Catalogs
    Route::prefix("/brands")->group($systemRoute."/Catalogs/Brand.php");
    Route::prefix("/categories")->group($systemRoute."/Catalogs/Category.php");
    Route::prefix("/products")->group($systemRoute."/Catalogs/Product.php");
    Route::prefix("/recipes")->group($systemRoute."/Catalogs/Recipe.php");
    Route::prefix("/services")->group($systemRoute."/Catalogs/Service.php");
    Route::prefix("/subscriptions")->group($systemRoute."/Catalogs/Subscription.php");

    // Customers
    Route::prefix("/customers")->group($systemRoute."/Customers/Customer.php");
    Route::prefix("/tracking_attendances")->group($systemRoute."/Customers/TrackingAttendance.php");
    Route::prefix("/tracking_customers")->group($systemRoute."/Customers/TrackingCustomer.php");
    Route::prefix("/tracking_subscriptions")->group($systemRoute."/Customers/TrackingSubscription.php");

    // Devices
    Route::prefix("/biometric_devices")->group($systemRoute."/Devices/BiometricDevice.php");

    // Essentials
    Route::prefix("/account")->group($systemRoute."/Essentials/Account.php");
    Route::prefix("/workspace")->group($systemRoute."/Essentials/Workspace.php");
    Route::prefix("/dashboard")->group($systemRoute."/Essentials/Dashboard.php");
    Route::prefix("/helpers")->group($systemRoute."/Essentials/Helper.php");
    Route::prefix("/master-data")->group($systemRoute."/General/MasterData.php");
    Route::prefix("/home")->group($systemRoute."/Essentials/Home.php");
    Route::prefix("/reports")->group($systemRoute."/Essentials/Report.php");

    // Customers (Tracking Notifications)
    Route::prefix("/tracking_notifications")->group($systemRoute."/Customers/TrackingNotification.php");

    // Organizations
    Route::prefix("/book_complaints")->group($systemRoute."/Organizations/BookComplaint.php");
    Route::prefix("/branches")->group($systemRoute."/Organizations/Branch.php");
    Route::prefix("/companies")->group($systemRoute."/Organizations/Company.php");
    Route::prefix("/roles")->group($systemRoute."/Organizations/Role.php");
    Route::prefix("/business_profile")->group($systemRoute."/Organizations/BusinessProfile.php");
    Route::prefix("/users")->group($systemRoute."/Organizations/User.php");
    Route::prefix("/user_attendances")->group($systemRoute."/Organizations/UserAttendance.php");

    // Operations
    Route::prefix("/service_operations")->group($systemRoute."/Operations/ServiceOperation.php");

    // Sales
    Route::prefix("/sales")->group($systemRoute."/Sales/Sale.php");
    Route::prefix("/quotations")->group($systemRoute."/Sales/Quotation.php");

    // Finance
    Route::prefix("/cash_registers")->group($systemRoute."/Finance/CashRegister.php");
    Route::prefix("/cash-sessions")->group($systemRoute."/Finance/CashSession.php");
    Route::prefix("/cash-movements")->group($systemRoute."/Finance/CashMovement.php");
    Route::prefix("/cash-summary")->group($systemRoute."/Finance/CashSummary.php");
    Route::prefix("/accounts_receivable")->group($systemRoute."/Finance/AccountsReceivable.php");
    Route::prefix("/accounts_payable")->group($systemRoute."/Finance/AccountsPayable.php");
    Route::prefix("/misc_expenses")->group($systemRoute."/Finance/MiscExpense.php");

    // Purchases
    Route::prefix("/purchases")->group($systemRoute."/Purchases/Purchase.php");
    Route::prefix("/suppliers")->group($systemRoute."/Purchases/Supplier.php");

    // Warehouses
    Route::prefix("/stocks_management")->group($systemRoute."/Warehouses/StockManagement.php");

    // Sessions
    Route::post("/logout", [AuthenticatedSessionController::class, "destroy"])->name("logout");

});
