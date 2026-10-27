@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Review Hasil Parsing CV</h2>
        <p class="text-sm text-gray-500">Berikut adalah data yang berhasil diekstrak oleh AI. Silakan periksa, edit jika ada kesalahan, lalu simpan ke Master Profile Anda.</p>
    </div>

    <!-- Error Alerts -->
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700 font-semibold">Terdapat kesalahan pengisian data:</p>
                    <ul class="mt-1 list-disc pl-5 text-xs text-red-600 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('cv.review.save') }}" method="POST" class="space-y-8">
        @csrf

        <!-- 1. PERSONAL DETAILS -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <h3 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-2">1. Detail Pribadi</h3>
            
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                    <input type="text" name="profile[name]" value="{{ old('profile.name', $parsedCv['profile']['name'] ?? '') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Alamat Email</label>
                    <input type="email" name="profile[email]" value="{{ old('profile.email', $parsedCv['profile']['email'] ?? '') }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nomor Telepon</label>
                    <input type="text" name="profile[phone]" value="{{ old('profile.phone', $parsedCv['profile']['phone'] ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Lokasi (Kota, Negara)</label>
                    <input type="text" name="profile[location]" value="{{ old('profile.location', $parsedCv['profile']['location'] ?? '') }}"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Ringkasan Profesional</label>
                <textarea name="profile[summary]" rows="4"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('profile.summary', $parsedCv['profile']['summary'] ?? '') }}</textarea>
            </div>

            <!-- Social Links -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tautan Sosial & Portfolio</label>
                <div id="profile-links-container" class="space-y-2">
                    @php
                        $links = old('profile.links', $parsedCv['profile']['links'] ?? []);
                    @endphp
                    @foreach ($links as $index => $link)
                        <div class="flex gap-2">
                            <input type="url" name="profile[links][]" value="{{ $link }}" placeholder="https://..."
                                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <button type="button" onclick="this.parentNode.remove()"
                                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" id="add-link-btn"
                        class="mt-2 inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50">
                    + Tambah Tautan
                </button>
            </div>
        </div>

        <!-- 2. WORK EXPERIENCE -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                <h3 class="text-lg font-bold text-gray-900">2. Pengalaman Kerja</h3>
                <button type="button" id="add-exp-btn"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Pengalaman
                </button>
            </div>

            <div id="experiences-container" class="space-y-6 divide-y divide-gray-100">
                @php
                    $experiences = old('experiences', $parsedCv['experiences'] ?? []);
                @endphp
                @foreach ($experiences as $expIndex => $exp)
                    <div class="experience-card pt-6 first:pt-0 space-y-4" data-index="{{ $expIndex }}">
                        <div class="flex justify-between items-start">
                            <h4 class="text-sm font-semibold text-gray-500">Pengalaman #<span class="exp-num">{{ $expIndex + 1 }}</span></h4>
                            <button type="button" onclick="this.closest('.experience-card').remove(); renumberExperiences();"
                                    class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Pengalaman</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Nama Perusahaan</label>
                                <input type="text" name="experiences[{{ $expIndex }}][company]" value="{{ $exp['company'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Jabatan / Posisi</label>
                                <input type="text" name="experiences[{{ $expIndex }}][position]" value="{{ $exp['position'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                                <input type="month" name="experiences[{{ $expIndex }}][start_date]" value="{{ isset($exp['start_date']) ? substr($exp['start_date'], 0, 7) : '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tanggal Selesai (Kosongkan jika aktif saat ini)</label>
                                <input type="month" name="experiences[{{ $expIndex }}][end_date]" value="{{ isset($exp['end_date']) ? substr($exp['end_date'], 0, 7) : '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                        </div>

                        <!-- Bullets -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Poin Pencapaian & Tanggung Jawab</label>
                            <div class="bullets-container space-y-2" id="exp-bullets-{{ $expIndex }}">
                                @php
                                    $bullets = $exp['bullet_points'] ?? [];
                                @endphp
                                @foreach ($bullets as $bIndex => $bullet)
                                    <div class="flex gap-2 items-start">
                                        <textarea name="experiences[{{ $expIndex }}][bullets][{{ $bIndex }}][content]" rows="1"
                                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">{{ $bullet }}</textarea>
                                        <button type="button" onclick="this.parentNode.remove()"
                                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addExpBullet({{ $expIndex }})"
                                    class="mt-2 inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50">
                                + Tambah Poin
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 3. PROJECTS -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                <h3 class="text-lg font-bold text-gray-900">3. Project Portfolio</h3>
                <button type="button" id="add-proj-btn"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Project
                </button>
            </div>

            <div id="projects-container" class="space-y-6 divide-y divide-gray-100">
                @php
                    $projects = old('projects', $parsedCv['projects'] ?? []);
                @endphp
                @foreach ($projects as $projIndex => $proj)
                    <div class="project-card pt-6 first:pt-0 space-y-4" data-index="{{ $projIndex }}">
                        <div class="flex justify-between items-start">
                            <h4 class="text-sm font-semibold text-gray-500">Project #<span class="proj-num">{{ $projIndex + 1 }}</span></h4>
                            <button type="button" onclick="this.closest('.project-card').remove(); renumberProjects();"
                                    class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Project</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700">Nama Project</label>
                                <input type="text" name="projects[{{ $projIndex }}][name]" value="{{ $proj['name'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Link Project (Optional)</label>
                                <input type="url" name="projects[{{ $projIndex }}][link]" value="{{ $proj['link'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Tech Stack (pisahkan dengan koma)</label>
                            <input type="text" name="projects[{{ $projIndex }}][tech_stack]"
                                   value="{{ is_array($proj['tech_stack'] ?? null) ? implode(', ', $proj['tech_stack']) : ($proj['tech_stack'] ?? '') }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"
                                   placeholder="Laravel, React, Tailwind CSS">
                        </div>

                        <!-- Bullets -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi & Pencapaian Project</label>
                            <div class="bullets-container space-y-2" id="proj-bullets-{{ $projIndex }}">
                                @php
                                    $bullets = $proj['bullet_points'] ?? [];
                                @endphp
                                @foreach ($bullets as $bIndex => $bullet)
                                    <div class="flex gap-2 items-start">
                                        <textarea name="projects[{{ $projIndex }}][bullets][{{ $bIndex }}][content]" rows="1"
                                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">{{ $bullet }}</textarea>
                                        <button type="button" onclick="this.parentNode.remove()"
                                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" onclick="addProjBullet({{ $projIndex }})"
                                    class="mt-2 inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50">
                                + Tambah Poin
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 4. EDUCATION -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                <h3 class="text-lg font-bold text-gray-900">4. Pendidikan</h3>
                <button type="button" id="add-edu-btn"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Pendidikan
                </button>
            </div>

            <div id="education-container" class="space-y-6 divide-y divide-gray-100">
                @php
                    $education = old('education', $parsedCv['education'] ?? []);
                @endphp
                @foreach ($education as $eduIndex => $edu)
                    <div class="edu-card pt-6 first:pt-0 space-y-4" data-index="{{ $eduIndex }}">
                        <div class="flex justify-between items-start">
                            <h4 class="text-sm font-semibold text-gray-500">Pendidikan #<span class="edu-num">{{ $eduIndex + 1 }}</span></h4>
                            <button type="button" onclick="this.closest('.edu-card').remove(); renumberEducation();"
                                    class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Pendidikan</button>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Nama Institusi</label>
                                <input type="text" name="education[{{ $eduIndex }}][institution]" value="{{ $edu['institution'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Gelar / Bidang Studi</label>
                                <input type="text" name="education[{{ $eduIndex }}][degree]" value="{{ $edu['degree'] ?? '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                                <input type="month" name="education[{{ $eduIndex }}][start_date]" value="{{ isset($edu['start_date']) ? substr($edu['start_date'], 0, 7) : '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Tanggal Lulus (atau estimasi)</label>
                                <input type="month" name="education[{{ $eduIndex }}][end_date]" value="{{ isset($edu['end_date']) ? substr($edu['end_date'], 0, 7) : '' }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700">Catatan Lainnya (IPK, Penghargaan, dll.)</label>
                            <input type="text" name="education[{{ $eduIndex }}][notes]" value="{{ $edu['notes'] ?? '' }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 5. SKILLS -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                <h3 class="text-lg font-bold text-gray-900">5. Skill / Keahlian</h3>
                <button type="button" id="add-skill-btn"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Skill
                </button>
            </div>

            <div id="skills-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @php
                    $skills = old('skills', $parsedCv['skills'] ?? []);
                @endphp
                @foreach ($skills as $skillIndex => $skill)
                    <div class="skill-card flex gap-2 items-center bg-gray-50 p-3 rounded-md border border-gray-200" data-index="{{ $skillIndex }}">
                        <input type="text" name="skills[{{ $skillIndex }}][name]" value="{{ $skill['name'] ?? '' }}" placeholder="Nama Skill" required
                               class="flex-1 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                        <input type="text" name="skills[{{ $skillIndex }}][category]" value="{{ $skill['category'] ?? '' }}" placeholder="Kategori" required
                               class="w-1/3 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                        <button type="button" onclick="this.closest('.skill-card').remove();"
                                class="text-red-600 hover:text-red-800 font-bold px-2">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- 6. CERTIFICATIONS -->
        <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-6">
            <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                <h3 class="text-lg font-bold text-gray-900">6. Sertifikasi</h3>
                <button type="button" id="add-cert-btn"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent rounded-md text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700">
                    + Tambah Sertifikasi
                </button>
            </div>

            <div id="certifications-container" class="space-y-4">
                @php
                    $certifications = old('certifications', $parsedCv['certifications'] ?? []);
                @endphp
                @foreach ($certifications as $certIndex => $cert)
                    <div class="cert-card flex gap-4 items-center bg-gray-50 p-4 rounded-md border border-gray-200" data-index="{{ $certIndex }}">
                        <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
                            <input type="text" name="certifications[{{ $certIndex }}][name]" value="{{ $cert['name'] ?? '' }}" placeholder="Nama Sertifikat" required
                                   class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                            <input type="text" name="certifications[{{ $certIndex }}][issuer]" value="{{ $cert['issuer'] ?? '' }}" placeholder="Penerbit" required
                                   class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                            <input type="text" name="certifications[{{ $certIndex }}][year]" value="{{ $cert['year'] ?? '' }}" placeholder="Tahun" required maxlength="4" pattern="\d{4}"
                                   class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                        </div>
                        <button type="button" onclick="this.closest('.cert-card').remove();"
                                class="text-red-600 hover:text-red-800 font-bold px-2">&times;</button>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Konfirmasi Master Profile Merge/Overwrite Warning -->
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded shadow-sm my-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 font-semibold">Konfirmasi Penyimpanan Data:</p>
                    <p class="mt-1 text-xs text-yellow-600">
                        Data pengalaman kerja, pendidikan, project, sertifikasi, dan skill baru dari CV ini akan <strong>ditambahkan (merge)</strong> ke dalam Master Profile Anda saat ini. Data detail pribadi (nama, email, telepon, lokasi, summary) akan <strong>diperbarui/ditimpa (overwrite)</strong> dengan data di bawah ini.
                    </p>
                </div>
            </div>
        </div>

        <!-- Buttons -->
        <div class="flex justify-end space-x-3 border-t border-gray-100 pt-6">
            <a href="{{ route('cv.upload.form') }}"
               class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Ulangi Upload
            </a>
            <button type="submit"
                    class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none">
                Simpan ke Master Profile
            </button>
        </div>
    </form>
</div>

<script>
    // 1. Social Links dynamic addition
    document.getElementById('add-link-btn').addEventListener('click', function() {
        const container = document.getElementById('profile-links-container');
        const div = document.createElement('div');
        div.className = 'flex gap-2';
        div.innerHTML = `
            <input type="url" name="profile[links][]" placeholder="https://..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-3 py-2 bg-red-600 text-white rounded-md text-sm font-semibold hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
    });

    // 2. Work Experiences dynamic handlers
    let expCount = {{ count($experiences) }};
    document.getElementById('add-exp-btn').addEventListener('click', function() {
        const container = document.getElementById('experiences-container');
        const div = document.createElement('div');
        div.className = 'experience-card pt-6 space-y-4';
        div.setAttribute('data-index', expCount);
        div.innerHTML = `
            <div class="flex justify-between items-start">
                <h4 class="text-sm font-semibold text-gray-500">Pengalaman #<span class="exp-num">${expCount + 1}</span></h4>
                <button type="button" onclick="this.closest('.experience-card').remove(); renumberExperiences();"
                        class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Pengalaman</button>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Nama Perusahaan</label>
                    <input type="text" name="experiences[${expCount}][company]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Jabatan / Posisi</label>
                    <input type="text" name="experiences[${expCount}][position]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="month" name="experiences[${expCount}][start_date]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Tanggal Selesai (Kosongkan jika aktif saat ini)</label>
                    <input type="month" name="experiences[${expCount}][end_date]"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Poin Pencapaian & Tanggung Jawab</label>
                <div class="bullets-container space-y-2" id="exp-bullets-${expCount}">
                    <div class="flex gap-2 items-start">
                        <textarea name="experiences[${expCount}][bullets][0][content]" rows="1" required
                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
                        <button type="button" onclick="this.parentNode.remove()"
                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addExpBullet(${expCount})"
                        class="mt-2 inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50">
                    + Tambah Poin
                </button>
            </div>
        `;
        container.appendChild(div);
        expCount++;
    });

    function addExpBullet(expIndex) {
        const container = document.getElementById(`exp-bullets-${expIndex}`);
        const count = container.children.length;
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start';
        div.innerHTML = `
            <textarea name="experiences[${expIndex}][bullets][${count}][content]" rows="1" required
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
    }

    function renumberExperiences() {
        const cards = document.querySelectorAll('#experiences-container .experience-card');
        cards.forEach((card, index) => {
            card.querySelector('.exp-num').textContent = index + 1;
        });
    }

    // 3. Projects dynamic handlers
    let projCount = {{ count($projects) }};
    document.getElementById('add-proj-btn').addEventListener('click', function() {
        const container = document.getElementById('projects-container');
        const div = document.createElement('div');
        div.className = 'project-card pt-6 space-y-4';
        div.setAttribute('data-index', projCount);
        div.innerHTML = `
            <div class="flex justify-between items-start">
                <h4 class="text-sm font-semibold text-gray-500">Project #<span class="proj-num">${projCount + 1}</span></h4>
                <button type="button" onclick="this.closest('.project-card').remove(); renumberProjects();"
                        class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Project</button>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700">Nama Project</label>
                    <input type="text" name="projects[${projCount}][name]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Link Project (Optional)</label>
                    <input type="url" name="projects[${projCount}][link]"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Tech Stack (pisahkan dengan koma)</label>
                <input type="text" name="projects[${projCount}][tech_stack]"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"
                       placeholder="Laravel, React, Tailwind CSS">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Deskripsi & Pencapaian Project</label>
                <div class="bullets-container space-y-2" id="proj-bullets-${projCount}">
                    <div class="flex gap-2 items-start">
                        <textarea name="projects[${projCount}][bullets][0][content]" rows="1" required
                                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
                        <button type="button" onclick="this.parentNode.remove()"
                                class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
                    </div>
                </div>
                <button type="button" onclick="addProjBullet(${projCount})"
                        class="mt-2 inline-flex items-center px-2 py-1 border border-gray-300 rounded text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50">
                    + Tambah Poin
                </button>
            </div>
        `;
        container.appendChild(div);
        projCount++;
    });

    function addProjBullet(projIndex) {
        const container = document.getElementById(`proj-bullets-${projIndex}`);
        const count = container.children.length;
        const div = document.createElement('div');
        div.className = 'flex gap-2 items-start';
        div.innerHTML = `
            <textarea name="projects[${projIndex}][bullets][${count}][content]" rows="1" required
                      class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs"></textarea>
            <button type="button" onclick="this.parentNode.remove()"
                    class="px-2 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">Remove</button>
        `;
        container.appendChild(div);
    }

    function renumberProjects() {
        const cards = document.querySelectorAll('#projects-container .project-card');
        cards.forEach((card, index) => {
            card.querySelector('.proj-num').textContent = index + 1;
        });
    }

    // 4. Education dynamic handlers
    let eduCount = {{ count($education) }};
    document.getElementById('add-edu-btn').addEventListener('click', function() {
        const container = document.getElementById('education-container');
        const div = document.createElement('div');
        div.className = 'edu-card pt-6 space-y-4';
        div.innerHTML = `
            <div class="flex justify-between items-start">
                <h4 class="text-sm font-semibold text-gray-500">Pendidikan #<span class="edu-num">${eduCount + 1}</span></h4>
                <button type="button" onclick="this.closest('.edu-card').remove(); renumberEducation();"
                        class="text-xs text-red-600 hover:text-red-800 font-semibold">Hapus Pendidikan</button>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Nama Institusi</label>
                    <input type="text" name="education[${eduCount}][institution]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Gelar / Bidang Studi</label>
                    <input type="text" name="education[${eduCount}][degree]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Tanggal Mulai</label>
                    <input type="month" name="education[${eduCount}][start_date]" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700">Tanggal Lulus (atau estimasi)</label>
                    <input type="month" name="education[${eduCount}][end_date]"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700">Catatan Lainnya (IPK, Penghargaan, dll.)</label>
                <input type="text" name="education[${eduCount}][notes]"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs">
            </div>
        `;
        container.appendChild(div);
        eduCount++;
    });

    function renumberEducation() {
        const cards = document.querySelectorAll('#education-container .edu-card');
        cards.forEach((card, index) => {
            card.querySelector('.edu-num').textContent = index + 1;
        });
    }

    // 5. Skills dynamic handlers
    let skillCount = {{ count($skills) }};
    document.getElementById('add-skill-btn').addEventListener('click', function() {
        const container = document.getElementById('skills-container');
        const div = document.createElement('div');
        div.className = 'skill-card flex gap-2 items-center bg-gray-50 p-3 rounded-md border border-gray-200';
        div.setAttribute('data-index', skillCount);
        div.innerHTML = `
            <input type="text" name="skills[${skillCount}][name]" placeholder="Nama Skill" required
                   class="flex-1 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
            <input type="text" name="skills[${skillCount}][category]" placeholder="Kategori" required
                   class="w-1/3 rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
            <button type="button" onclick="this.closest('.skill-card').remove();"
                    class="text-red-600 hover:text-red-800 font-bold px-2">&times;</button>
        `;
        container.appendChild(div);
        skillCount++;
    });

    // 6. Certifications dynamic handlers
    let certCount = {{ count($certifications) }};
    document.getElementById('add-cert-btn').addEventListener('click', function() {
        const container = document.getElementById('certifications-container');
        const div = document.createElement('div');
        div.className = 'cert-card flex gap-4 items-center bg-gray-50 p-4 rounded-md border border-gray-200';
        div.setAttribute('data-index', certCount);
        div.innerHTML = `
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
                <input type="text" name="certifications[${certCount}][name]" placeholder="Nama Sertifikat" required
                       class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                <input type="text" name="certifications[${certCount}][issuer]" placeholder="Penerbit" required
                       class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
                <input type="text" name="certifications[${certCount}][year]" placeholder="Tahun" required maxlength="4" pattern="\\d{4}"
                       class="rounded border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-xs py-1">
            </div>
            <button type="button" onclick="this.closest('.cert-card').remove();"
                    class="text-red-600 hover:text-red-800 font-bold px-2">&times;</button>
        `;
        container.appendChild(div);
        certCount++;
    });
</script>
@endsection
