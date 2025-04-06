<?php

namespace App\Policies;

use App\Models\Mission;
use App\Models\TelegramUser;
use Illuminate\Auth\Access\Response;

class MissionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(TelegramUser $telegramUser): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(TelegramUser $telegramUser, Mission $mission): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(TelegramUser $telegramUser): bool
    {
        //
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(TelegramUser $telegramUser, Mission $mission): bool
    {
        //
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(TelegramUser $telegramUser, Mission $mission): bool
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(TelegramUser $telegramUser, Mission $mission): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(TelegramUser $telegramUser, Mission $mission): bool
    {
        //
    }
}
