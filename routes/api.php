<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingController;
use App\Events\MyEvent;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\TourAvailabilityController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\FeedbackController;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

Route::post('/upload-id', function (Request $request) {
    Log::info('Upload ID API called');

    try {
        // Validate input
        $validated = $request->validate([
            'valid_id' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'id_type' => 'required|string',
        ]);

        // Store the uploaded ID
        $path = $request->file('valid_id')->store('uploads', 'public');
        $imagePath = storage_path("app/public/{$path}");
        $idType = strtolower($validated['id_type']);

        Log::info("Stored ID at: $imagePath");

        // Set Python Path
        $pythonPath = "C:\\Users\\shana\\Documents\\dorm-vision\\backend\\.venv\\Scripts\\python.exe";
        Log::info("Using Python path: $pythonPath");

        // Construct the command
        $command = escapeshellcmd("$pythonPath " . base_path("ocr_script.py") . " $imagePath $idType");

        // ✅ Run Python script with shell_exec()
        $output = shell_exec($command);
        
        // Log output for debugging
        Log::info("OCR Output: " . $output);

        // Handle errors
        if (!$output) {
            return response()->json([
                'message' => 'OCR processing failed.',
                'error' => 'No output from OCR script.'
            ], 500);
        }

        // Parse the output JSON
        $ocrResult = json_decode($output, true);

        if (!$ocrResult) {
            return response()->json([
                'message' => 'Invalid OCR response.',
                'error' => 'Failed to parse OCR result.'
            ], 500);
        }

        Log::info("OCR Result: " . json_encode($ocrResult));

        return response()->json([
            'message' => $ocrResult['id_type_matched'] ? 'ID verified successfully' : 'ID mismatch',
            'ocr_text' => $ocrResult['text'],
            'file_path' => $path,
            'id_verified' => $ocrResult['id_type_matched'],
        ]);

    } catch (\Exception $e) {
        Log::error('Upload ID Error: ' . $e->getMessage());
        return response()->json([
            'message' => 'An error occurred during the upload process.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/gallery', [GalleryController::class, 'index']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/gallery/upload', [GalleryController::class, 'store']); // Upload new image
    Route::put('/gallery/{id}', [GalleryController::class, 'update']); // Update image details
    Route::delete('/gallery/{id}', [GalleryController::class, 'destroy']); // Delete image
});


Route::post('/bookings/confirm/{id}', [TourAvailabilityController::class, 'confirmBooking']);
Route::post('/bookings/cancel/{id}', [TourAvailabilityController::class, 'cancelBooking']);

Route::options('{any}', function () {
    return response()->json(['status' => 'ok'], 200);
})->where('any', '.*');

Route::get('/test-firebase', function () {
    $file = storage_path('app/firebase-service-account.json');
    if (!is_readable($file)) {
        return response()->json(['error' => 'Firebase service account file not readable']);
    }
    return response()->json(['success' => 'Firebase service account file is accessible']);
});


// Admin/Tenant Authenticated Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/validate-token', [AuthController::class, 'validateToken']);
    Route::post('/auth/refresh-token', [AuthController::class, 'refreshToken']);
});

Route::post('/tour-availability/toggle', [TourAvailabilityController::class, 'toggleAvailability']);
// Get all bookings
Route::get('/tour-bookings', [TourAvailabilityController::class, 'getBookings']);

// Get all availability
Route::get('/tour-availability', [TourAvailabilityController::class, 'getAvailability']);

// Get slots for a specific date
Route::get('/tour-slots', [TourAvailabilityController::class, 'getSlots']);

Route::post('/tour-slots/unavailable', [TourAvailabilityController::class, 'makeAllUnavailable']);
Route::get('/tour-dates', [TourAvailabilityController::class, 'getAvailableDates']);
Route::get('/tour-calendar', [TourAvailabilityController::class, 'getCalendar']);
Route::middleware('auth:sanctum')->post('/book-tour', [TourAvailabilityController::class, 'bookTour']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
});


// Guest Authentication Routes
Route::post('/google-login', [AuthController::class, 'googleLogin'])->middleware('guest');
Route::post('/register-guest', [AuthController::class, 'registerGuest']);

Route::get('/places', [PlaceController::class, 'index']);


Route::middleware(['auth:sanctum'])->group(function () {
    // Fetch all notifications for the logged-in user
    Route::get('/notifications', [NotificationController::class, 'index']);

    // Mark all notifications as read
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAsRead']);

    // Delete a specific notification
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});

Route::get('/trigger-event', function () {
    event(new MyEvent('Hello, this is a real-time test message!'));
    return 'Event triggered successfully!';
});

// Landlord Contact Routes
Route::get('/settings/landlord-contact', [SettingController::class, 'getLandlordContact']);
Route::post('/settings/landlord-contact', [SettingController::class, 'updateLandlordContact']);

// Payments
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', function (Request $request) {
        return response()->json($request->user());
    });

    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/tenant-payments/{id}', [PaymentController::class, 'getTenantPayments']);
});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tenants', [AdminController::class, 'getTenants']);
    Route::get('/payments', [PaymentController::class, 'index']);
});
Route::get('/tenants', [TenantController::class, 'getTenants']);
Route::get('/payments/summary', [PaymentController::class, 'paymentSummary']);

Route::post('/payments/confirm/{user_id}', [PaymentController::class, 'confirmLatestPayment']);
Route::post('/payments/reject/{user_id}', [PaymentController::class, 'rejectLatestPayment']);

Route::post('/payments/{id}/confirm', [PaymentController::class, 'updateSpecificPayment']);



// Unpaid Tenants
Route::get('/unpaid-tenants', [TenantController::class, 'unpaidTenants']);
Route::post('/tenants/{id}/send-reminder', [TenantController::class, 'sendReminder']);

// Other Routes
Route::get('/users', [UserController::class, 'index']);
Route::post('/assign-unit', [UnitController::class, 'assignUnit']);

// Maintenance Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::middleware('role:tenant')->group(function () {
        Route::post('/maintenance-requests', [MaintenanceRequestController::class, 'store']);
        Route::get('/tenant/maintenance-requests', [MaintenanceRequestController::class, 'tenantRequests']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index']);
        Route::post('/maintenance-requests/{id}/update', [MaintenanceRequestController::class, 'updateStatus']);
        Route::post('/maintenance-requests/{id}/schedule', [MaintenanceRequestController::class, 'schedule']);
        Route::delete('/maintenance-requests/{id}', [MaintenanceRequestController::class, 'destroy']);
    });

    Route::post('/maintenance-requests/{id}/cancel', [MaintenanceRequestController::class, 'cancelRequest']);
});

// Application Routes
Route::post('/applications', [ApplicationController::class, 'store']);
Route::get('/applications', [ApplicationController::class, 'index']);
Route::post('/applications/{id}/accept', [ApplicationController::class, 'accept']);
Route::delete('/applications/{id}/decline', [ApplicationController::class, 'decline']);
Route::put('/units/{id}/status', [UnitController::class, 'updateStatus']);

// Unit Routes
Route::apiResource('units', UnitController::class);

// Authentication Routes
Route::post('/login-guest', [AuthController::class, 'loginGuest'])->middleware('guest');
Route::post('/login-admin-tenant', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->get('/auth/user', function (Request $request) {
    return response()->json($request->user());
});
// Tenant Routes
Route::middleware(['auth:sanctum', 'role:tenant'])->group(function () {
    Route::get('/tenant/dashboard', [TenantController::class, 'index']); // Tenant Dashboard
});
// Tenant Route to get assigned unit
Route::middleware(['auth:sanctum', 'role:tenant'])->group(function () {
    Route::get('/tenant/unit', [TenantController::class, 'getAssignedUnit']);
});
// Authenticated User Route
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Fetch the authenticated user's details
Route::middleware('auth:sanctum')->get('/auth/user', function (Request $request) {
    return response()->json($request->user());
});


Route::post('/feedback', [FeedbackController::class, 'store']); // For submitting feedback
Route::get('/feedback', [FeedbackController::class, 'index']); // For retrieving all feedback
