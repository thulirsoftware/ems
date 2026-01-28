<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AssessmentController;
use App\Http\Controllers\Api\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Api\Admin\AssessmentQuestionController;
use App\Http\Controllers\Api\Admin\AssessmentChoiceController;

// Auth
Route::prefix('admin')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    Route::middleware('admin')->group(function () {
        Route::get('me', fn() => auth('admins')->user());
        Route::post('logout', [AdminAuthController::class, 'logout']);
    });
});

// Users
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('users', [AdminUserController::class, 'index']);
});

// Assessments
Route::prefix('admin')->middleware('admin')->group(function () {

    // assessments
    Route::get('assessments', [AssessmentController::class, 'index']);
    Route::get('assessments/library', [AssessmentController::class, 'library']);
    Route::get('assessments/upcoming', [AssessmentController::class, 'upcoming']);
    Route::get('assessments/running', [AssessmentController::class, 'running']);
    Route::post('assessments', [AssessmentController::class, 'store']);
    Route::get('assessments/{id}', [AssessmentController::class, 'show']);
    Route::put('assessments/{id}', [AssessmentController::class, 'update']);
    Route::delete('assessments/{id}', [AssessmentController::class, 'destroy']);

    // assignments
    Route::get('assignments/users-with-assignment-status', [AssessmentAssignmentController::class, 'usersWithAssignmentStatus']);
    Route::post('assignments', [AssessmentAssignmentController::class, 'store']);
    Route::delete('assignments', [AssessmentAssignmentController::class, 'destroy']);

    // questions
    Route::get('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'index']);
    Route::post('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'store']);
    Route::put('questions/{id}', [AssessmentQuestionController::class, 'update']);
    Route::delete('questions/{id}', [AssessmentQuestionController::class, 'destroy']);

    // choices
    Route::get('questions/{question_id}/choices', [AssessmentChoiceController::class, 'index']);
    Route::post('questions/{question_id}/choices', [AssessmentChoiceController::class, 'store']);
    Route::put('choices/{id}', [AssessmentChoiceController::class, 'update']);
    Route::delete('choices/{id}', [AssessmentChoiceController::class, 'destroy']);
});
