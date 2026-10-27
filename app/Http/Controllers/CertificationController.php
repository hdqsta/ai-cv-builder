<?php

namespace App\Http\Controllers;

use App\Models\Certification;
use Illuminate\Http\Request;

class CertificationController extends Controller
{
    public function index()
    {
        $certifications = Certification::orderBy('year', 'desc')->get();
        return view('certifications.index', compact('certifications'));
    }

    public function create()
    {
        return view('certifications.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'issuer' => 'required|string|max:255',
            'year' => 'required|string|max:4', // format YYYY
        ]);

        Certification::create($validated);

        return redirect()->route('certifications.index')->with('success', 'Certification created successfully.');
    }

    public function edit(Certification $certification)
    {
        return view('certifications.edit', compact('certification'));
    }

    public function update(Request $request, Certification $certification)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'issuer' => 'required|string|max:255',
            'year' => 'required|string|max:4',
        ]);

        $certification->update($validated);

        return redirect()->route('certifications.index')->with('success', 'Certification updated successfully.');
    }

    public function destroy(Certification $certification)
    {
        $certification->delete();
        return redirect()->route('certifications.index')->with('success', 'Certification deleted successfully.');
    }
}
