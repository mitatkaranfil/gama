<?php

namespace App\Http\Controllers;

use App\Models\Mission;
use App\Models\MissionLevel;
use App\Models\TelegramUserMission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserMissionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $missions = Mission::query()
            ->with(['nextLevel' => fn ($q) => $q->whereNotIn(
                'id',
                fn ($q) =>
                $q->select('mission_level_id')
                    ->from('telegram_user_missions')
                    ->where('telegram_user_id', $user->id)
            )])
            ->withSum(['levels as production_per_hour' => fn ($q) => $q->whereIn(
                'id',
                fn ($q) =>
                $q->select('mission_level_id')
                    ->from('telegram_user_missions')
                    ->where('telegram_user_id', $user->id)
            )], 'production_per_hour')

            ->when($request->get('type'), fn ($q) => $q->where('missions.mission_type_id', $request->get('type')))
            ->get();

        return response()->json($missions);
    }

    public function store(Request $request, MissionLevel $missionLevel)
    {
        $user = $request->user();

        if ($user->balance < $missionLevel->cost) {
            return response()->json([
                'message' => 'Insufficient balance',
            ], 400);
        }

        DB::transaction(function () use ($user, $missionLevel) {
            TelegramUserMission::create([
                'telegram_user_id' => $user->id,
                'mission_level_id' => $missionLevel->id,
                'level' => $missionLevel->level,
            ]);

            $user->update([
                'production_per_hour' => $user->production_per_hour + $missionLevel->production_per_hour,
                'balance' => $user->balance - $missionLevel->cost,
            ]);
        });

        $missionLevel->load('mission');

        $nextLevel = MissionLevel::query()
            ->where('mission_id', $missionLevel->mission_id)
            ->where('level', $missionLevel->level + 1)
            ->first();

        return response()->json([
            'message' => "Mission {$missionLevel->mission->name} upgraded {$missionLevel->level} level",
            'next_level' => $nextLevel,
            'user' => $user,
        ]);
    }
}
