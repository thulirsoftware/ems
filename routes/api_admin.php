<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminResultController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AssessmentController;
use App\Http\Controllers\Api\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Api\Admin\AssessmentQuestionController;
use App\Http\Controllers\Api\Admin\AssessmentChoiceController;
use App\Http\Controllers\Api\Admin\AssessmentTypeController;

// Admin auth
Route::prefix('admin')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register']);
    Route::post('login', [AdminAuthController::class, 'login']);

    // Admin profile
    Route::middleware('admin')->group(function () {
        Route::get('me', fn() => auth('admins')->user());
        Route::post('logout', [AdminAuthController::class, 'logout']);
    });
});

// Admin users
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
});

// Admin assessments
Route::prefix('admin')->middleware('admin')->group(function () {

    // Assessments
    Route::get('assessments', [AssessmentController::class, 'index']);
    Route::get('assessments/library', [AssessmentController::class, 'library']);
    Route::get('assessments/upcoming', [AssessmentController::class, 'upcoming']);
    Route::get('assessments/running', [AssessmentController::class, 'running']);
    Route::post('assessments', [AssessmentController::class, 'store']);
    Route::get('assessments/{id}', [AssessmentController::class, 'show']);
    Route::put('assessments/{id}', [AssessmentController::class, 'update']);
    Route::delete('assessments/{id}', [AssessmentController::class, 'destroy']);

    // Assessment types
    Route::get('assessment-types', [AssessmentTypeController::class, 'index']);
    Route::post('assessment-types', [AssessmentTypeController::class, 'store']);
    Route::get('assessment-types/{id}', [AssessmentTypeController::class, 'show']);
    Route::put('assessment-types/{id}', [AssessmentTypeController::class, 'update']);
    Route::delete('assessment-types/{id}', [AssessmentTypeController::class, 'destroy']);

    // Questions
    Route::get('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'index']);
    Route::post('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'store']);
    Route::put('questions/{id}', [AssessmentQuestionController::class, 'update']);
    Route::delete('questions/{id}', [AssessmentQuestionController::class, 'destroy']);
    Route::get('assessments/{assessment_id}/questions/with-choices', [AssessmentQuestionController::class, 'indexWithChoices']);
    Route::post('assessments/{assessment_id}/questions/with-choices', [AssessmentQuestionController::class, 'storeWithChoices']);
    Route::get('questions/{id}/with-choices', [AssessmentQuestionController::class, 'showWithChoices']);
    Route::put('questions/{id}/with-choices', [AssessmentQuestionController::class, 'updateWithChoices']);

    // Choices
    Route::get('questions/{question_id}/choices', [AssessmentChoiceController::class, 'index']);
    Route::post('questions/{question_id}/choices', [AssessmentChoiceController::class, 'store']);
    Route::put('choices/{id}', [AssessmentChoiceController::class, 'update']);
    Route::delete('choices/{id}', [AssessmentChoiceController::class, 'destroy']);

    // Assignments
    Route::get('assignments/users-with-assignment-status', [AssessmentAssignmentController::class, 'usersWithAssignmentStatus']);
    Route::post('assignments', [AssessmentAssignmentController::class, 'store']);
    Route::delete('assignments', [AssessmentAssignmentController::class, 'destroy']);

    // Results
    Route::get('results/finished', [AdminResultController::class, 'finishedAssessments']);
    Route::get('results/{assessment_id}/users', [AdminResultController::class, 'usersByAssessment']);
    Route::get('results/{assessment_id}/user/{user_id}', [AdminResultController::class, 'userResult']);
});