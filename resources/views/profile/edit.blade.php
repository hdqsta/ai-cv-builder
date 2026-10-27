@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Profile Details</h2>

    <form action="{{ route('profile.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
            <input type="text" name="name" id="name" value="{{ old('name', $profile->name) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" name="email" id="email" value="{{ old('email', $profile->email) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Phone -->
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone', $profile->phone) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Location -->
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700">Location (City, Country)</label>
            <input type="text" name="location" id="location" value="{{ old('location', $profile->location) }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        <!-- Summary -->
        <div>
            <label for="summary" class="block text-sm font-medium text-gray-700">Professional Summary</label>
            <textarea name="summary" id="summary" rows="4"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('summary', $profile->summary) }}</textarea>
        </div>

        <!-- Links -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Social & Portfolio Links</label>
            <div id="links-container" class="space-y-2">
                @if (!empty($profile->links))
                    @foreach ($profile->links as $link)
                        <div class="flex gap-2 items-center">
                            <input type="text" name="links[]" value="{{ $link }}" placeholder="https://linkedin.com/in/username"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <button type="button" onclick="this.parentNode.remove()"
                                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
                        </div>
                    @endforeach
                @else
                    <div class="flex gap-2 items-center">
                        <input type="text" name="links[]" placeholder="https://linkedin.com/in/username"
                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <button type="button" onclick="this.parentNode.remove()"
                                class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
                    </div>
                @endif
            </div>
            <button type="button" id="add-link-btn"
                    class="mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
                + Add Link
            </button>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-gray-100 flex justify-end">
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                Save Profile
            </button>
        </div>
    </form>
</div>

<script>
    document.getElementById('add-link-btn').addEventListener('click', function() {
        const container = document.getElementById('links-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-center';
        div.innerHTML = `
            <input type="text" name="links[]" placeholder="https://linkedin.com/in/username"
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
    });
</script>
@endsection
