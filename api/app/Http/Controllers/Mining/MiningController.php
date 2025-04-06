<?php

namespace App\Http\Controllers\Mining;

use App\Http\Controllers\Controller;
use App\Models\MiningOperation;
use App\Models\TokenTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MiningController extends Controller
{
    public function startMining(Request $request)
    {
        $user = Auth::user();
        
        // Check for active mining session
        $activeMining = MiningOperation::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if ($activeMining) {
            return response()->json([
                'message' => 'Already mining'
            ], 400);
        }

        // Start new mining session
        $miningOperation = MiningOperation::create([
            'user_id' => $user->id,
            'mining_rate' => $this->getMiningRate($user),
            'boost_multiplier' => $this->getBoostMultiplier($user)
        ]);

        return response()->json([
            'message' => 'Mining started successfully',
            'mining_rate' => $miningOperation->mining_rate,
            'boost_multiplier' => $miningOperation->boost_multiplier
        ]);
    }

    public function stopMining(Request $request)
    {
        $user = Auth::user();
        
        $miningOperation = MiningOperation::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        if (!$miningOperation) {
            return response()->json([
                'message' => 'No active mining session'
            ], 400);
        }

        $duration = now()->diffInSeconds($miningOperation->created_at);
        $tokensEarned = $this->calculateTokensEarned(
            $duration,
            $miningOperation->mining_rate,
            $miningOperation->boost_multiplier
        );

        // Create token transaction
        TokenTransaction::create([
            'user_id' => $user->id,
            'type' => 'mining',
            'amount' => $tokensEarned,
            'description' => 'Mining operation completed'
        ]);

        $miningOperation->update([
            'ended_at' => now(),
            'tokens_earned' => $tokensEarned,
            'duration_seconds' => $duration
        ]);

        return response()->json([
            'message' => 'Mining stopped successfully',
            'tokens_earned' => $tokensEarned
        ]);
    }

    public function getMiningStatus(Request $request)
    {
        $user = Auth::user();
        
        $activeMining = MiningOperation::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->first();

        return response()->json([
            'is_mining' => !!$activeMining,
            'mining_rate' => $activeMining ? $activeMining->mining_rate : 0,
            'boost_multiplier' => $activeMining ? $activeMining->boost_multiplier : 1,
            'tokens_earned' => $activeMining ? $activeMining->tokens_earned : 0
        ]);
    }

    public function getEarnings(Request $request)
    {
        $user = Auth::user();
        
        $earnings = MiningOperation::where('user_id', $user->id)
            ->whereNotNull('ended_at')
            ->orderBy('ended_at', 'desc')
            ->get();

        return response()->json([
            'earnings' => $earnings
        ]);
    }

    public function getTokenBalance(Request $request)
    {
        $user = Auth::user();
        
        $balance = TokenTransaction::where('user_id', $user->id)
            ->sum('amount');

        return response()->json([
            'balance' => $balance
        ]);
    }

    public function getTokenHistory(Request $request)
    {
        $user = Auth::user();
        
        $transactions = TokenTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($transactions);
    }

    private function getMiningRate($user)
    {
        // Base mining rate
        $baseRate = 1;
        
        // Add modifiers based on user level, achievements, etc.
        return $baseRate;
    }

    private function getBoostMultiplier($user)
    {
        // Calculate boost multiplier from active boosts
        $activeBoosts = $user->activeBoosts;
        return $activeBoosts->sum('multiplier') + 1;
    }

    private function calculateTokensEarned($duration, $miningRate, $boostMultiplier)
    {
        return floor(($duration * $miningRate * $boostMultiplier) / 60);
    }
}
