<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
    public function uploadIcon(Request $request)
    {
        $request->validate([
            'icon' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        
        // Delete old icon if exists
        if ($user->icon_path && Storage::exists($user->icon_path)) {
            Storage::delete($user->icon_path);
        }

        $filename = $user->id . '.' . $request->file('icon')->extension();
        $path = $request->file('icon')->storeAs('private/user_icons', $filename);

        $user->icon_path = $path;
        // $user->save();

        return response()->json(['message' => 'Icon uploaded', 'path' => $path]);
    }

    public function getIcon()
    {
        $user = Auth::user();

        if (!$user->icon_path || !Storage::exists($user->icon_path)) {
            return response()->json(['error' => 'Icon not found'], 404);
        }

        return response()->file(Storage::path($user->icon_path));
    }
}
