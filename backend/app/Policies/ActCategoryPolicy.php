<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ActCategory;

class ActCategoryPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * 自分のカテゴリのみ操作可
     */
    public function manage(User $user, ActCategory $category): bool
    {
        return $user->id === $category->user_id;
    }

    public function delete(User $user, ActCategory $actCategory): bool
    {
        return $user->id === $actCategory->user_id;
    }
}
