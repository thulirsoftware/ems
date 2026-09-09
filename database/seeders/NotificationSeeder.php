<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\AssessmentAssignment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Mirror the notification AssessmentAssignmentController::store() sends
        // for every assignment that was seeded, alternating read/unread.
        $assignments = AssessmentAssignment::with('assessment')->get();

        foreach ($assignments as $index => $assignment) {
            Notification::create([
                'user_id' => $assignment->user_id,
                'type' => 'assessment_assigned',
                'title' => 'New assessment assigned',
                'message' => "You have been assigned: {$assignment->assessment->title}",
                'data' => [
                    'assessment_id' => $assignment->assessment_id,
                    'admin_id' => $assignment->assessment->admin_id,
                ],
                'is_read' => $index % 2 === 0,
            ]);
        }

        // A re-exam notification, matching ReExamController's flow.
        $user1 = User::where('email', 'user1@gmail.com')->firstOrFail();
        Notification::create([
            'user_id' => $user1->id,
            'type' => 're_exam_assigned',
            'title' => 'Re-exam scheduled',
            'message' => 'You have a re-exam for Basic Math Test',
            'data' => [],
            'is_read' => false,
        ]);

        // Admin-facing notifications.
        $admin1 = Admin::where('email', 'admin1@gmail.com')->firstOrFail();
        Notification::create([
            'admin_id' => $admin1->id,
            'type' => 'assessment_submitted',
            'title' => 'Assessment submitted',
            'message' => 'Student 1 submitted General Knowledge Descriptive and is awaiting evaluation.',
            'data' => [],
            'is_read' => false,
        ]);
    }
}
