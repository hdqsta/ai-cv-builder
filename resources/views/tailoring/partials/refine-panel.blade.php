{{--
    Prompt Tambahan (Refinement) Panel
    -------------------------------------------------------------
    Lets the user add an extra instruction (e.g. "tambahkan keinginan
    untuk mempelajari hal baru") and re-run the AI tailoring, which
    rewrites the wording while staying inside the no-fabrication rules.

    Expects: $generatedCv (App\Models\GeneratedCv)
--}}
@php
    $supportsRefine = !$generatedCv->is_instant_check && empty($generatedCv->raw_cv_text);
    $isBusy = in_array($generatedCv->status->value, ['tailoring', 'scoring'], true);
    $hasSavedFinal = !empty($generatedCv->tailored_cv_final['tailored_cv']);
    $hasTailoredResult = !empty($generatedCv->tailored_cv_raw['tailored_cv']) || $hasSavedFinal;
    $lastInstructions = trim((string) ($generatedCv->custom_instructions ?? ''));

    $presets = [
        'Tambahkan hasrat kuat untuk mempelajari hal baru dan teknologi baru',
        'Tekankan kemampuan adaptasi & cepat menguasai sistem baru',
        'Perkuat kata kunci ATS utama dari JD pada ringkasan',
        'Ringkas menjadi maksimal 2 bullet per pengalaman kerja',
        'Soroti kontribusi teknis proyek secara lebih terukur',
    ];
@endphp

@if ($supportsRefine && !$isBusy)
    <div class="glass-panel p-6 sm:p-7 rounded-3xl border border-violet-500/30 bg-violet-950/10 space-y-4 shadow-lg">
        <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-2xl bg-violet-500/15 border border-violet-500/30 flex items-center justify-center text-violet-300 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div class="space-y-1 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="text-sm font-bold text-white">Prompt Tambahan untuk AI</h3>
                    <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full bg-violet-500/20 text-violet-300 border border-violet-500/30">
                        Opsional
                    </span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    @if ($hasTailoredResult)
                        Ada yang ingin Anda tekankan atau ubah dari hasil di bawah? Tulis instruksinya, lalu AI akan <strong class="text-violet-300">men-tailor ulang kata-katanya</strong> tanpa mengarang data baru.
                    @else
                        Sebelum tailoring dijalankan, Anda bisa menambahkan instruksi khusus agar AI langsung menyusun wording sesuai keinginan Anda.
                    @endif
                    Contoh: <em class="text-slate-300">"tambahkan keinginan kuat untuk mempelajari hal baru"</em>.
                </p>
            </div>
        </div>

        @if ($lastInstructions !== '')
            <div class="flex items-start gap-2 text-[11px] leading-relaxed">
                <span class="shrink-0 font-bold uppercase tracking-wider text-violet-400/80">Prompt diterapkan:</span>
                <span class="text-violet-100/80 italic">&ldquo;{{ $lastInstructions }}&rdquo;</span>
            </div>
        @endif

        <form
            action="{{ route('tailoring.refine', $generatedCv) }}"
            method="POST"
            class="space-y-3"
            data-refine-form
            data-has-final="{{ $hasSavedFinal ? '1' : '0' }}"
        >
            @csrf

            <div class="space-y-2">
                <label for="custom_instructions" class="block text-xs font-bold text-slate-300">
                    Instruksi / Prompt Tambahan <span class="text-slate-500 font-normal">(maks. 2000 karakter)</span>
                </label>
                <textarea
                    id="custom_instructions"
                    name="custom_instructions"
                    rows="3"
                    maxlength="2000"
                    placeholder="Contoh: tambahkan keinginan kuat untuk mempelajari hal baru dan teknologi baru, tekankan semangat belajar cepat..."
                    class="w-full p-3 text-xs bg-slate-950/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/30 font-sans leading-relaxed @error('custom_instructions') border-rose-500 @enderror"
                >{{ old('custom_instructions', $lastInstructions) }}</textarea>
                @error('custom_instructions')
                    <p class="text-[11px] text-rose-400 font-semibold">{{ $message }}</p>
                @enderror
            </div>

            {{-- Quick presets --}}
            <div class="space-y-1.5">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Contoh cepat (klik untuk mengisi)</div>
                <div class="flex flex-wrap gap-2">
                    @foreach ($presets as $preset)
                        <button
                            type="button"
                            data-refine-preset="{{ $preset }}"
                            class="px-2.5 py-1 rounded-full bg-slate-900/80 border border-slate-700/80 text-[11px] text-slate-300 hover:border-violet-500/60 hover:text-violet-200 hover:bg-violet-950/30 transition-colors text-left"
                        >{{ $preset }}</button>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                <div class="space-y-1">
                    <p class="text-[11px] text-slate-500 leading-relaxed">
                        @if ($hasSavedFinal)
                            ⚠️ Versi final yang sudah tersimpan akan diganti oleh hasil tailoring ulang.
                        @else
                            AI akan menyusun ulang ringkasan &amp; bullet sesuai instruksi, tetap tanpa fabrikasi data.
                        @endif
                    </p>
                    @if ($lastInstructions !== '')
                        <button
                            type="submit"
                            name="clear"
                            value="1"
                            data-refine-clear
                            formnovalidate
                            onclick="return confirm('AI akan men-tailor ulang TANPA prompt tambahan dan hasil sebelumnya akan diganti. Lanjutkan?')"
                            class="text-[11px] font-semibold text-rose-300/80 hover:text-rose-200 underline decoration-dotted underline-offset-2 transition-colors"
                        >
                            Hapus prompt tambahan &amp; tailor ulang
                        </button>
                    @endif
                </div>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-500 hover:to-fuchsia-500 text-white font-bold text-xs shadow-lg shadow-violet-950/40 border border-violet-400/30 transition-all hover:scale-[1.02] shrink-0"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{{ $hasTailoredResult ? 'Tailor Ulang dengan Prompt Ini' : 'Tailor dengan Prompt Ini' }}</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-refine-form]').forEach(function (form) {
                var textarea = form.querySelector('textarea[name="custom_instructions"]');
                if (!textarea) return;

                form.querySelectorAll('[data-refine-preset]').forEach(function (chip) {
                    chip.addEventListener('click', function () {
                        var preset = chip.getAttribute('data-refine-preset') || '';
                        var current = textarea.value.trim();
                        textarea.value = current === '' ? preset : current + '\n- ' + preset;
                        textarea.classList.remove('border-rose-500');
                        textarea.focus();
                    });
                });

                form.addEventListener('submit', function (event) {
                    // The "remove extra prompt" button skips the instruction check.
                    var isClear = event.submitter && event.submitter.hasAttribute('data-refine-clear');

                    if (!isClear && textarea.value.trim().length < 5) {
                        event.preventDefault();
                        textarea.classList.add('border-rose-500');
                        textarea.focus();
                        return;
                    }

                    if (!isClear
                        && form.getAttribute('data-has-final') === '1'
                        && !window.confirm('AI akan men-tailor ulang CV ini dan versi final yang sudah tersimpan akan diganti. Lanjutkan?')) {
                        event.preventDefault();
                    }
                });
            });
        })();
    </script>
@endif
