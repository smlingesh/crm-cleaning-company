<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_staff', function (Blueprint $table) {
            $table->date('work_date')->nullable()->after('notes');
            $table->uuid('assignment_batch_id')->nullable()->after('work_date');
            $table->boolean('is_pending_approval')->default(false)->after('assignment_batch_id');

            $table->index(['job_id', 'work_date']);
            $table->index(['job_id', 'assignment_batch_id']);
        });

        $rows = DB::table('job_staff')->select('id', 'job_id', 'created_at')->orderBy('id')->get();

        $groups = $rows->groupBy(function ($row) {
            $date = $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : now()->toDateString();

            return $row->job_id.'|'.$date;
        });

        foreach ($groups as $group) {
            $first = $group->first();
            $workDate = $first->created_at
                ? date('Y-m-d', strtotime($first->created_at))
                : now()->toDateString();
            $batchId = (string) Str::uuid();

            DB::table('job_staff')
                ->whereIn('id', $group->pluck('id'))
                ->update([
                    'work_date'           => $workDate,
                    'assignment_batch_id' => $batchId,
                    'is_pending_approval' => false,
                ]);
        }

        $pendingJobIds = DB::table('jobs')
            ->where('status', 'staff_pending_approval')
            ->pluck('id');

        foreach ($pendingJobIds as $jobId) {
            $latestBatch = DB::table('job_staff')
                ->where('job_id', $jobId)
                ->orderByDesc('created_at')
                ->value('assignment_batch_id');

            if ($latestBatch) {
                DB::table('job_staff')
                    ->where('job_id', $jobId)
                    ->where('assignment_batch_id', $latestBatch)
                    ->update(['is_pending_approval' => true]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('job_staff', function (Blueprint $table) {
            $table->dropIndex(['job_id', 'work_date']);
            $table->dropIndex(['job_id', 'assignment_batch_id']);
            $table->dropColumn(['work_date', 'assignment_batch_id', 'is_pending_approval']);
        });
    }
};
