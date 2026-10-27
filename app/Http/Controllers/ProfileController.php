<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        $profile = Profile::firstOrNew();
        return view('profile.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'location' => 'required|string|max:255',
            'summary' => 'nullable|string',
            'links' => 'nullable|array',
            'links.*' => 'nullable|string|max:255',
        ]);

        if (isset($validated['links'])) {
            $validated['links'] = array_values(array_filter($validated['links']));
        } else {
            $validated['links'] = [];
        }

        $profile = Profile::firstOrNew();
        $profile->fill($validated);
        $profile->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully.');
    }
}
