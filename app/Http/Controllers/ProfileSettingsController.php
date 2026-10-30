<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Resource;
class ProfileSettingsController
{
    static string $page_title = 'MOE | Staff Management';

    public function index(Request $request){
        $resources = Resource::all();

        return view('dashboard/profile-settings',
            [
                'page_title' => self::$page_title . ' - Dashboard',
                'app_version' => config('app.app_version'),
                'resources' => $resources,
            ]);
    }

    public function changePassword(Request $request){
        return view('dashboard/change-password',
        [
            'page_title' => self::$page_title . ' - Change Password',
            'app_version' => config('app.app_version'),
        ]); 
    }

    /**
     * Change the signed-in account's own password. The current one has to be
     * given: a session left open on an unattended desk should not be enough to
     * lock its owner out.
     */
    public function storePassword(Request $request){
        $user = $request->user();

        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Give your current password.',
            'password.required' => 'Choose a new password.',
            'password.confirmed' => 'The two new passwords do not match.',
        ]);

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors([
                'current_password' => 'That is not your current password.',
            ]);
        }

        // Hashed by the cast on the model.
        $user->update(['password' => $request->input('password')]);

        // The password is a credential the session was established under, so
        // the session id changes with it.
        $request->session()->regenerate();

        return redirect()->route('change-password')
            ->with('status', 'Your password has been changed.');
    }
}
