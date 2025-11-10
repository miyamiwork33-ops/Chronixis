<?php

namespace App\Policies;

use App\Models\HabitLog;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class HabitLogPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * 自分の習慣ログのみ操作可
     */
    public function manage(User $user, HabitLog $habitLog): bool
    {
        return $user->id === $habitLog->user_id;
    }
    public function delete(User $user, HabitLog $habitLog): bool
    {
        return $user->id === $habitLog->user_id;
    }
}
