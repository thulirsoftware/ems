<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use DB;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Batch;
use App\Models\Setting;

class AdminBatchController extends Controller
{
    // Get batch capacity
    private function capacity()
    {
        return (int) Setting::get('batch_capacity') ?? 5;
    }

    // List all batches with user count
    public function batches()
    {
        $batches = Batch::withCount('users')->get();

        return response()->json($batches);
    }

    // Get users in batch
    public function batchUsers($batch_id)
    {
        $users = User::where('batch_id', $batch_id)->get();

        return response()->json($users);
    }

    // Get unassigned users
    public function unassignedUsers()
    {
        $users = User::whereNull('batch_id')->get();

        return response()->json($users);
    }

    // Add users to batches (auto create batch if capacity reached)
    public function addUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array'
        ]);

        $capacity = $this->capacity();

        $batch = Batch::latest()->first();

        if (!$batch) {
            $batch = Batch::create([
                'name' => 'Batch 1'
            ]);
        }

        foreach ($request->user_ids as $userId) {

            $count = User::where('batch_id', $batch->id)->count();

            if ($count >= $capacity) {

                $batchNumber = (Batch::max('id') ?? 0) + 1;

                $batch = Batch::create([
                    'name' => 'Batch ' . $batchNumber
                ]);
            }

            User::where('id', $userId)->update([
                'batch_id' => $batch->id
            ]);
        }

        return response()->json([
            'message' => 'Users assigned to batches successfully'
        ]);
    }

    // Assign all unassigned users automatically
    public function assignUnassigned()
    {
        DB::transaction(function () {

            $capacity = $this->capacity();

            $batch = Batch::latest()->first();

            if (!$batch) {
                $batch = Batch::create([
                    'name' => 'Batch 1'
                ]);
            }

            $users = User::whereNull('batch_id')->get();

            foreach ($users as $user) {

                $count = User::where('batch_id', $batch->id)->count();

                if ($count >= $capacity) {

                    $batchNumber = (Batch::max('id') ?? 0) + 1;

                    $batch = Batch::create([
                        'name' => 'Batch ' . $batchNumber
                    ]);
                }

                $user->update([
                    'batch_id' => $batch->id
                ]);
            }

        });

        return response()->json([
            'message' => 'Unassigned users assigned'
        ]);
    }

    // Remove users from batch
    public function removeUsers(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array'
        ]);

        User::whereIn('id', $request->user_ids)
            ->update(['batch_id' => null]);

        return response()->json([
            'message' => 'Users removed from batch'
        ]);
    }

    // Return system settings for frontend
    public function getBatchCapacity()
    {
        $capacity = Setting::get('batch_capacity');

        return response()->json([
            'batch_capacity' => (int) $capacity
        ]);
    }

    public function updateBatchCapacity(Request $request)
    {
        $request->validate([
            'batch_capacity' => 'required|integer|min:1'
        ]);

        Setting::where('key', 'batch_capacity')->update([
            'value' => $request->batch_capacity
        ]);

        return response()->json([
            'message' => 'Batch capacity updated successfully',
            'batch_capacity' => (int) $request->batch_capacity
        ]);
    }

    public function rebalanceBatches()
    {
        $capacity = (int) Setting::get('batch_capacity');

        DB::transaction(function () use ($capacity) {

            $users = User::orderBy('created_at')->get();

            $batchNumber = 1;
            $count = 0;

            Batch::truncate();

            $batch = Batch::create([
                'name' => 'Batch ' . $batchNumber
            ]);

            foreach ($users as $user) {

                if ($count >= $capacity) {
                    $batchNumber++;
                    $count = 0;

                    $batch = Batch::create([
                        'name' => 'Batch ' . $batchNumber
                    ]);
                }

                $user->update([
                    'batch_id' => $batch->id
                ]);

                $count++;
            }

        });

        return response()->json([
            'message' => 'Batches rebalanced successfully'
        ]);
    }
}