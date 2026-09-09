<?php

use App\Http\Controllers\Api\User\AssessmentAttemptController;
use App\Http\Controllers\Api\User\UserAssessmentController;
use App\Http\Controllers\Api\User\UserAuthController;
use App\Http\Controllers\Api\User\UserDashboardController;
use App\Http\Controllers\Api\User\UserNotificationController;
use App\Http\Controllers\Api\User\UserReportController;
use App\Http\Controllers\Api\User\UserQuestionController;
use App\Http\Controllers\Api\User\UserAnswerController;

// User auth
Route::prefix('user')->group(function () {
    // A shared classroom/office IP can register many students in quick
    // succession, so this stays generous — it only needs to stop automated
    // mass account creation, not a normal lab full of students.
    Route::post('register', [UserAuthController::class, 'register'])->middleware('throttle:30,1');
    Route::post('login', [UserAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('send-verification-code', [UserAuthController::class, 'sendVerificationCode'])->middleware('throttle:otp');
    Route::post('verify-email', [UserAuthController::class, 'verifyEmail'])->middleware('throttle:otp');

    // User profile
    Route::middleware('user')->group(function () {
        Route::get('me', fn() => auth('users')->user());
        Route::post('logout', [UserAuthController::class, 'logout']);
    });
});

// User assessments
Route::prefix('user')->middleware('user')->group(function () {

    // Dashboard
    Route::get('dashboard', [UserDashboardController::class, 'index']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('summary', [UserReportController::class, 'summary']);
        Route::get('assessments', [UserReportController::class, 'assessments']);
        Route::get('attempts', [UserReportController::class, 'attempts']);
        Route::get('questions', [UserReportController::class, 'questions']);
        Route::get('progress', [UserReportController::class, 'progress']);
    });

    // Assessments
    Route::get('assessments/upcoming', [UserAssessmentController::class, 'upcoming']);
    Route::get('assessments/today', [UserAssessmentController::class, 'today']);
    Route::get('assessments/running', [UserAssessmentController::class, 'running']);
    Route::get('assessments/completed', [UserAssessmentController::class, 'completed']);
    Route::get('assessments/missed', [UserAssessmentController::class, 'missed']);
    Route::get('assessments/{id}', [UserAssessmentController::class, 'show']);

    // Attempt control
    Route::post('assessments/{assessment_id}/start', [AssessmentAttemptController::class, 'start']);
    Route::post('assessments/{assessment_id}/submit', [AssessmentAttemptController::class, 'submit']);
    Route::post('assessments/{assessment_id}/result', [AssessmentAttemptController::class, 'result']);

    // Questions & answers
    Route::get('assessments/{assessment_id}/questions', [UserQuestionController::class, 'index']);
    Route::post('assessments/{assessment_id}/answer', [UserAnswerController::class, 'store']);

    // Notifications
    Route::get('notifications', [UserNotificationController::class, 'index']);
    Route::post('notifications/read-all', [UserNotificationController::class, 'markAllRead']);

});
