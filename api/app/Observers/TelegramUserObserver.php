<?php

namespace App\Observers;

use App\Models\Level;
use App\Models\TelegramUser;

class TelegramUserObserver
{
    public function updated(TelegramUser $user): void
    {
        if (!$user->isDirty('balance')) return;

        $user->load('level');

        $newLevels = Level::query()
            ->where('from_balance', '<=', $user->balance)
            ->where('level', '>', $user->level->level)
            ->orderByDesc('level')
            ->get();

        $nextLevel = $newLevels->first();

        if (!$nextLevel) return;

        $levelUp = config('clicker.level_up');

        $user->level_id = $nextLevel->id;
        $user->max_energy += $newLevels->count() * $levelUp['max_energy'];
        $user->earn_per_tap += $newLevels->count() * $levelUp['earn_per_tap'];
        $user->save();

        if (!$user->referred_by) return;

        $referredBy = TelegramUser::where('telegram_id', $user->referred_by)->first();

        if (!$referredBy) return;

        $referralLevelUp = config('clicker.referral.base.levelUp');

        if ($referralLevelUp && isset($referralLevelUp[$nextLevel->level]) && $referralLevelUp[$nextLevel->level] > 0) {
            $referredBy->increment('balance', $referralLevelUp[$nextLevel->level]);
        }
    }
}
