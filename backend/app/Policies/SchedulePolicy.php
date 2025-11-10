<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchedulePolicy
{
    public function __construct()
    {
        //
    }

    /**
     * 自分の予定のみ操作可
     */
    public function manage(User $user, Schedule $schedule): bool
    {
        return $user->id === $schedule->user_id
             && $schedule->actCategory?->user_id === $user->id;
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return $user->id === $schedule->user_id;
    }
}
