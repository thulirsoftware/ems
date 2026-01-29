<?php

use App\Http\Controllers\Api\User\AssessmentAttemptController;
use App\Http\Controllers\Api\User\UserAssessmentController;
use App\Http\Controllers\Api\User\UserAuthController;
use App\Http\Controllers\Api\User\UserQuestionController;
use App\Http\Controllers\Api\User\UserAnswerController; // <-- missing import

// Auth
Route::prefix('user')->group(function () {
    Route::post('register', [UserAuthController::class, 'register']);
    Route::post('login', [UserAuthController::class, 'login']);
    Route::post('send-verification-code', [UserAuthController::class, 'sendVerificationCode']);
    Route::post('verify-email', [UserAuthController::class, 'verifyEmail']);

    // profile
    Route::middleware('user')->group(function () {
        Route::get('me', fn() => auth('users')->user());
        Route::post('logout', [UserAuthController::class, 'logout']);
    });
});

// Assessments
Route::prefix('user')->middleware('user')->group(function () {

    // assessments
    Route::get('assessments', [UserAssessmentController::class, 'index']);
    Route::get('assessments/upcoming', [UserAssessmentController::class, 'upcoming']);
    Route::get('assessments/running', [UserAssessmentController::class, 'running']);
    Route::get('assessments/{id}', [UserAssessmentController::class, 'show']);

    // attempt control
    Route::post('assessments/{id}/start', [AssessmentAttemptController::class, 'start']);
    Route::post('assessments/{id}/submit', [AssessmentAttemptController::class, 'submit']);
    Route::post('assessments/{id}/result', [AssessmentAttemptController::class, 'result']);

    // questions & answers
    Route::get('assessments/{id}/questions', [UserQuestionController::class, 'index']);
    Route::post('assessments/{id}/answer', [UserAnswerController::class, 'store']);
});
