@extends('layouts.app')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Skills List Column (2 cols wide on md+) -->
    <div class="md:col-span-2 space-y-6">
        <h2 class="text-2xl font-bold text-gray-900">Skills Inventory</h2>

        @if ($skills->isEmpty())
            <div class="bg-white p-6 rounded-lg border border-gray-200 text-center text-gray-500 shadow-sm">
                No skills added yet. Add your first skill on the right.
            </div>
        @else
            <!-- Group skills by category -->
            @php
                $groupedSkills = $skills->groupBy('category');
            @endphp

            <div class="space-y-6">
                @foreach ($groupedSkills as $category => $categorySkills)
                    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">
                            {{ $category }}
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($categorySkills as $skill)
                                <div class="inline-flex items-center bg-gray-50 text-gray-700 px-3 py-1 rounded-md text-sm border border-gray-200 shadow-sm">
                                    <span>{{ $skill->name }}</span>
                                    <form action="{{ route('skills.destroy', $skill) }}" method="POST" class="ml-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 focus:outline-none font-bold text-xs">
                                            &times;
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Quick Add Form Column (1 col wide) -->
    <div>
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm sticky top-24">
            <h3 class="text-lg font-bold text-gray-900 mb-4">Add New Skill</h3>
            <form action="{{ route('skills.store') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Skill Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Skill Name</label>
                    <input type="text" name="name" id="name" required placeholder="e.g. PHP, Vue.js, Docker"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                    <input type="text" name="category" id="category" required placeholder="e.g. Frontend, Backend, Tools"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           list="categories-list">
                    <!-- Dynamic datalist suggestions based on existing categories -->
                    <datalist id="categories-list">
                        @foreach ($skills->pluck('category')->unique() as $cat)
                            <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <button type="submit"
                        class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                    Add Skill
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
