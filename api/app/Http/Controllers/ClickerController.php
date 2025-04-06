<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use App\Models\DailyTask;
use App\Models\Level;
use App\Models\MissionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use DateTime;

class ClickerController extends Controller
{
    public function sync(Request $request)
    {
        $user = $request->user();

        // Check if booster pack has expired
        $this->checkBoosterPackExpiration($user);

        // Calculate passive earnings for the user and increase balance
        $passiveEarnings = $user->calcPassiveEarning();

        // Update login streak
        $user->updateLoginStreak();

        // Check and reset daily booster if it's a new day
        $user->checkAndResetDailyBooster();

        // Restore energy
        $restoredEnergy = $user->restoreEnergy();

        // Load the level relationship
        $user->load('level');

        $canUseDailyBooster = $user->canUseDailyBooster();

        $totalDailyRewards = DailyTask::sum('reward_coins');

        $levels = Level::all();

        $missionTypes = MissionType::all();

        $totalReferals = TelegramUser::where('referred_by', $user->telegram_id)->count();

        $user->update(['last_login_date' => now()]);

        return response()->json([
            'user' => $user,
            'restored_energy' => $restoredEnergy,
            'boosters' => [
                'multi_tap' => [
                    'level' => $user->multi_tap_level,
                    'cost' => $this->getBoosterCost($user, 'multi_tap'),
                    'increase_by' => 1,
                ],
                'energy_limit' => [
                    'level' => $user->energy_limit_level,
                    'cost' => $this->getBoosterCost($user, 'energy_limit'),
                    'increase_by' => 500,
                ],
            ],
            'daily_booster' => [
                'can_use' => $canUseDailyBooster,
                'uses_today' => $user->daily_booster_uses,
                'next_available_at' => $canUseDailyBooster ? now() : ($user->last_daily_booster_use ? $user->last_daily_booster_use->addHour() : null),
            ],
            'booster_packs' => [
                'booster_pack_2x' => [
                    'cost' => 2,
                    'duration_days' => 30, 
                    'multiplier' => 2
                ],
                'booster_pack_3x' => [
                    'cost' => 3,
                    'duration_days' => 30, 
                    'multiplier' => 3
                ],
                'booster_pack_7x' => [
                    'cost' => 5,
                    'duration_days' => 30, 
                    'multiplier' => 7
                ]
            ],
            'booster_pack_x2' => $user->booster_pack_x2,
            'booster_pack_x2' => $user->booster_pack_x3,
            'booster_pack_x2' => $user->booster_pack_x7,
            'booster_pack_active_until' => $user->booster_pack_active_until,
            'total_daily_rewards' => $totalDailyRewards,
            'levels' => $levels,
            'max_level' => $levels->max('level'),
            'level_up' => config('clicker.level_up'),
            'referral' => config('clicker.referral'),
            'mission_types' => $missionTypes,
            'passive_earnings' => $passiveEarnings,
            'total_referals' => $totalReferals,
        ]);
    }

    public function tap(Request $request)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        $earned = $user->tap($validated['count']);

        return response()->json([
            'success' => true,
            'earned' => $earned,
            'balance' => $user->balance,
            'available_energy' => $user->available_energy,
        ]);
    }

    public function buyBoosterPack(Request $request)
    {
        $request->validate([
            'booster_pack' => 'required|in:booster_pack_2x,booster_pack_3x,booster_pack_7x',
        ]);

        $user = $request->user();
        $boosterPack = $request->input('booster_pack');

        try {
            DB::transaction(function () use ($user, $boosterPack) {
                $currentTime = new DateTime();
                $boosterActiveUntil = new DateTime($user->booster_pack_active_until);

                if ($boosterActiveUntil > $currentTime) {
                    if ($this->isValidUpgrade($user, $boosterPack)) {
                        $this->deactivateCurrentBooster($user);
                    } else {
                        throw new \InvalidArgumentException("Cannot downgrade or repurchase the same booster pack while one is active.");
                    }
                }

                $this->activateBoosterPack($user, $boosterPack);
                $user->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Booster pack purchased successfully',
                'booster_pack_active_until' => $user->booster_pack_active_until,
                'balance' => $user->balance
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while purchasing the booster pack.',
            ], 500);
        }
    }

    private function isValidUpgrade($user, $newPack)
    {
        $packValues = [
            'booster_pack_2x' => 2,
            'booster_pack_3x' => 3,
            'booster_pack_7x' => 7
        ];

        $currentPack = $this->getCurrentBoosterPack($user);
        if($currentPack) {
            return $packValues[$newPack] > $packValues[$currentPack];
        } else {
            return true;
        }
    }

    private function getCurrentBoosterPack($user)
    {
        if ($user->booster_pack_7x) return 'booster_pack_7x';
        if ($user->booster_pack_3x) return 'booster_pack_3x';
        if ($user->booster_pack_2x) return 'booster_pack_2x';
        return null;
    }

    private function deactivateCurrentBooster($user)
    {
        $user->booster_pack_2x = 0;
        $user->booster_pack_3x = 0;
        $user->booster_pack_7x = 0;
    }

    private function activateBoosterPack($user, $boosterPack)
    {
        switch($boosterPack) {
            case 'booster_pack_2x':
                $user->booster_pack_2x = 1;
                break;
            case 'booster_pack_3x':
                $user->booster_pack_3x = 1;
                break;
            case 'booster_pack_7x':
                $user->booster_pack_7x = 1;
                break;
            default:
                throw new \InvalidArgumentException("Invalid booster pack type: {$boosterPack}");
        }
        $user->booster_pack_active_until = (new DateTime())->modify('+30 days')->format('Y-m-d H:i:s');
    }

    private function checkBoosterPackExpiration($user)
    {
        if ($user->booster_pack_active_until) {
            $expirationDate = new DateTime($user->booster_pack_active_until);
            $currentDate = new DateTime();

            if ($currentDate > $expirationDate) {
                // Booster pack has expired, deactivate it
                $user->booster_pack_2x = 0;
                $user->booster_pack_3x = 0;
                $user->booster_pack_7x = 0;
                $user->booster_pack_active_until = null;
                $user->save();
            }
        }
    }

    public function buyBooster(Request $request)
    {
        $request->validate([
            'booster_type' => 'required|in:multi_tap,energy_limit',
        ]);

        $user = $request->user();
        $boosterType = $request->input('booster_type');
        $cost = $this->getBoosterCost($user, $boosterType);

        // if ($user->balance < $cost) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Not enough coins to buy this booster.',
        //         'required_coins' => $cost,
        //         'current_balance' => $user->balance,
        //     ], 400);
        // }

        try {
            DB::transaction(function () use ($user, $boosterType, $cost) {
                //$user->balance -= $cost;

                switch ($boosterType) {
                    case 'multi_tap':
                        $user->multi_tap_level++;
                        $user->earn_per_tap++;
                        break;
                    case 'energy_limit':
                        $user->energy_limit_level++;
                        $user->max_energy += 500;
                        break;
                    default:
                        throw new \InvalidArgumentException("Invalid booster type: {$boosterType}");
                }

                $user->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Booster purchased successfully',
                'balance' => $user->balance,
                'earn_per_tap' => $user->earn_per_tap,
                'max_energy' => $user->max_energy,
                'multi_tap_level' => $user->multi_tap_level,
                'energy_limit_level' => $user->energy_limit_level,
                'next_multi_tap_cost' => $this->getBoosterCost($user, 'multi_tap'),
                'next_energy_limit_cost' => $this->getBoosterCost($user, 'energy_limit'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while purchasing the booster.',
            ], 500);
        }
    }

    private function getBoosterCost($user, $boosterType)
    {
        $boostLevel = $boosterType === 'multi_tap' ? $user->multi_tap_level : $user->energy_limit_level;
        return 1 + ($boostLevel - 1);  // Cost of TON increases by one each level
    }

    public function listDailyTasks(Request $request)
    {
        $user = $request->user();

        // fetch all daily tasks and check if they are available for the user
        $dailyTasks = DailyTask::query()
            ->leftJoin('telegram_user_daily_tasks', function ($join) use ($user) {
                $join->on('daily_tasks.id', '=', 'telegram_user_daily_tasks.daily_task_id')
                    ->where('telegram_user_daily_tasks.telegram_user_id', $user->id);
            })
            ->select(['daily_tasks.*', 'telegram_user_daily_tasks.completed',])
            ->selectRaw('daily_tasks.required_login_streak <= ? as available', [$user->login_streak])
            ->get();

        return response()->json($dailyTasks);
    }

    public function listLeaderboard(Request $request)
    {
        $request->validate([
            'level_id' => 'required|integer|exists:levels,id',
        ]);

        $levelId = $request->input('level_id');

        $topUsers = TelegramUser::where('level_id', $levelId)
            ->orderBy('production_per_hour', 'desc')
            ->take(100)
            ->get();

        return response()->json($topUsers);
    }

    public function useDailyBooster(Request $request)
    {
        $user = $request->user();

        if ($user->useDailyBooster()) {
            return response()->json([
                'success' => true,
                'message' => 'Daily booster used successfully',
                'current_energy' => $user->max_energy,
                'daily_booster_uses' => $user->daily_booster_uses,
                'next_available_at' => $user->last_daily_booster_use->addHour(),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cannot use daily booster at this time',
                'daily_booster_uses' => $user->daily_booster_uses,
                'next_available_at' => $user->last_daily_booster_use ? $user->last_daily_booster_use->addHour() : null,
            ], 400);
        }
    }

    public function claimDailyTaskReward(Request $request)
    {
        $user = $request->user();

        $task = DailyTask::where('required_login_streak', '<=', $user->login_streak)
            ->whereDoesntHave('telegramUsers', function ($query) use ($user) {
                $query->where('telegram_user_id', $user->id);
            })
            ->first();

        if ($task) {
            DB::transaction(function () use ($task, $user) {
                $user->increment('balance', $task->reward_coins);
                $user->dailyTasks()->attach($task->id, [
                    'completed' => true,
                    'updated_at' => now()
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Daily task reward claimed successfully',
                'balance' => $user->balance,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to claim daily task reward. Task may not be available or already completed for today.',
        ], 400);
    }

    public function setTonWallet(Request $request)
    {
        $request->validate([
            'ton_wallet' => 'required|string',
        ]);

        $user = $request->user();
        $user->ton_wallet = $request->input('ton_wallet');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'TON Wallet address updated successfully',
            'ton_wallet' => $user->ton_wallet,
        ]);
    }
}
