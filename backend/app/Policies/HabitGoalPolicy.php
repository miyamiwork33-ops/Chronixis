<?php

namespace App\Policies;

use App\Models\User;
use App\Models\HabitGoal;

class HabitGoalPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * 自分の習慣化目標のみ操作可
     */
    public function manage(User $user, HabitGoal $habitGoal): bool
    {
        return $user->id === $habitGoal->user_id;
    }

    public function delete(User $user, HabitGoal $habitGoal): bool
    {
        return $user->id === $habitGoal->user_id;
    }
}
