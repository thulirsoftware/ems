<?php

use App\Http\Controllers\Api\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\AdminBatchController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Admin\AdminResultController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AssessmentController;
use App\Http\Controllers\Api\Admin\AssessmentAssignmentController;
use App\Http\Controllers\Api\Admin\AssessmentQuestionController;
use App\Http\Controllers\Api\Admin\AssessmentChoiceController;
use App\Http\Controllers\Api\Admin\AssessmentTypeController;
use App\Http\Controllers\Api\Admin\ReExamController;

// Admin auth
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login');

    // Admin profile & management — registering a new admin requires an
    // authenticated admin, so anonymous callers can never create one.
    Route::middleware('admin')->group(function () {
        Route::get('me', fn() => auth('admins')->user());
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('register', [AdminAuthController::class, 'register']);
    });
});

// Admin users
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('users', [AdminUserController::class, 'index']);
    Route::post('users', [AdminUserController::class, 'store']);
    Route::post('users/bulk-assign', [AdminUserController::class, 'bulkStore']);
});

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('batches')->group(function () {
        Route::get('/', [AdminBatchController::class, 'index']);
        Route::post('/', [AdminBatchController::class, 'store']);
        Route::get('{id}', [AdminBatchController::class, 'show']);
        Route::put('{id}', [AdminBatchController::class, 'update']);
        Route::delete('{id}', [AdminBatchController::class, 'destroy']);
        Route::get('assessment/{assessment_id}', [AdminBatchController::class, 'getBatchesByAssessment']);
        Route::get('{id}/users', [AdminBatchController::class, 'getUsersByBatchId']);
        Route::post('{id}/users', [AdminBatchController::class, 'addUsers']);
        Route::delete('{id}/users', [AdminBatchController::class, 'removeUsers']);
    });
});

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::post('re-exam', [ReExamController::class, 'createReExam']);
    Route::post('re-exam/filtered', [ReExamController::class, 'createFilteredReExam']);
});

// Admin dashboard
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index']);
});

// Admin reports
Route::prefix('admin')->middleware('admin')->group(function () {
    Route::prefix('reports')->group(function () {
        Route::get('summary', [AdminReportController::class, 'summary']);
        Route::get('assessments', [AdminReportController::class, 'assessments']);
        Route::get('batches', [AdminReportController::class, 'batches']);
        Route::get('users', [AdminReportController::class, 'users']);
        Route::get('questions', [AdminReportController::class, 'questions']);
        Route::get('attempts', [AdminReportController::class, 'attempts']);
    });
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

    // Questions
    Route::get('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'index']);
    Route::post('assessments/{assessment_id}/questions', [AssessmentQuestionController::class, 'store']);
    Route::put('questions/{id}', [AssessmentQuestionController::class, 'update']);
    Route::delete('questions/{id}', [AssessmentQuestionController::class, 'destroy']);
    Route::get('assessments/{assessment_id}/questions/with-choices', [AssessmentQuestionController::class, 'indexWithChoices']);
    Route::post('assessments/{assessment_id}/questions/with-choices', [AssessmentQuestionController::class, 'storeWithChoices']);
    Route::get('questions/{question_id}/with-choices', [AssessmentQuestionController::class, 'showWithChoices']);
    Route::put('questions/{question_id}/with-choices', [AssessmentQuestionController::class, 'updateWithChoices']);
    Route::post('questions/bulk-store/{assessment_id}', [AssessmentQuestionController::class, 'bulkStoreQuestions']);

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
    Route::get('results/{assessment_id}/user/{user_id}/answers', [AdminResultController::class, 'userAnswersForGrading']);
    Route::post('results/{assessment_id}/user/{user_id}/question/{question_id}/grade', [AdminResultController::class, 'gradeAnswer']);
    Route::get('results/{assessment_id}/rank-list', [AdminResultController::class, 'rankList']);
});