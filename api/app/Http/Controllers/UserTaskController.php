<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::query()
            ->leftJoin('telegram_user_tasks', function ($join) use ($user) {
                $join->on('tasks.id', '=', 'telegram_user_tasks.task_id')
                    ->where('telegram_user_tasks.telegram_user_id', $user->id);
            })
            ->select(['tasks.*', 'telegram_user_tasks.is_submitted', 'telegram_user_tasks.is_rewarded', 'telegram_user_tasks.submitted_at'])
            ->get()
            ->map(function ($task) {
                return array_merge($task->toArray(), [
                    // submitted_at with iso format
                    'submitted_at' => $task->submitted_at ? Carbon::parse($task->submitted_at)->toIso8601String() : null,
                ]);
            });

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Task $task)
    {
        $user = $request->user();

        $userTask = $user->tasks()->where('task_id', $task->id)->first();

        if ($userTask) {
            return response()->json(['success' => false, 'message' => 'Task already submitted.'], 400);
        }

        $user->tasks()->attach($task->id, ['is_submitted' => true, 'submitted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Task submitted successfully. Waiting for approval.',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function claim(Request $request, Task $task)
    {
        $user = $request->user();

        $userTask = $user->tasks()->where('task_id', $task->id)->first();

        if (!$userTask) {
            return response()->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        if ($userTask->pivot->is_rewarded) {
            return response()->json(['success' => false, 'message' => 'Task already rewarded.'], 400);
        }

        $claimed = false;
        DB::transaction(function () use ($task, &$claimed, $user, $userTask) {
            $userTask->pivot->is_rewarded = true;
            $userTask->pivot->save();

            $user->increment('balance', $task->reward_coins);

            $claimed = true;
        });

        if (!$claimed) {
            return response()->json(['success' => false, 'message' => 'Unable to claim reward.'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "You have successfully claimed $task->reward_coins from $task->name."
        ]);
    }
}
