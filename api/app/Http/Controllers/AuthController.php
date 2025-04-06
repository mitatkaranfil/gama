<?php

namespace App\Http\Controllers;

use App\Models\TelegramUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function telegramUser(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required',
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
            'referred_by' => 'sometimes|nullable',
        ]);

        $validated['balance'] = 5_000;

        if ($request->get('referred_by') != null && $request->get('referred_by') != $request->get('telegram_id')) {
            $isUserExists = TelegramUser::where('telegram_id', $request->get('telegram_id'))->exists();
            $referredBy = TelegramUser::where('telegram_id', $request->get('referred_by'))->first();

            if ($referredBy && !$isUserExists) {
                $increaseBy = config('clicker.referral.base.welcome');
                if ($increaseBy > 0) {
                    $referredBy->increment('balance', $increaseBy);
                    $validated['balance'] += $increaseBy;
                }
            }
        } else {
            $validated['referred_by'] = null;
        }

        $user = TelegramUser::firstOrCreate(
            ['telegram_id' => $request->get('telegram_id')],
            $validated
        );

        $user->updateLoginStreak();

        $token = $user->createToken($user->telegram_id);

        return response()->json([
            'first_login' => $user->wasRecentlyCreated,
            'token' => $token->plainTextToken,
        ]);
    }
}
