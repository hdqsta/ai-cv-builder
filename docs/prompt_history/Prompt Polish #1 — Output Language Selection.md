Konteks: Laravel project AI CV Builder (MVP personal). Epic 1-5 sudah selesai 
dan fungsional. Sekarang masuk fase polishing. Tugas ini menambahkan opsi 
pilihan bahasa output tailoring yang sebelumnya di-hardcode ke Bahasa Indonesia.

Tugas:

1. Migration — tambah kolom baru ke tabel `generated_cvs`:
   - `output_language` (string, default 'id') 
   - Nilai yang valid: 'id' (Bahasa Indonesia), 'en' (English)

2. Update Model `GeneratedCv`:
   - Tambah 'output_language' ke $fillable
   - Tambah konstanta: OUTPUT_LANG_ID = 'id', OUTPUT_LANG_EN = 'en'

3. Update form input JD (halaman Epic 2):
   - Tambah radio button atau select di bawah textarea JD:
     - "🇮🇩 Bahasa Indonesia" (default, selected)
     - "🇬🇧 English"
   - Label: "Bahasa output CV yang akan di-generate"
   - Styling: konsisten dengan form yang sudah ada

4. Update `StoreJobDescriptionRequest`:
   - Tambah validasi: output_language — required, in:id,en

5. Update `TailoringController@store`:
   - Simpan nilai output_language dari request ke record generated_cvs baru

6. Update `TailoringEngine::buildSystemPrompt()`:
   - Terima parameter $outputLanguage (string, default 'id')
   - Ganti instruksi bahasa yang sekarang hardcode ke Bahasa Indonesia menjadi 
     dinamis berdasarkan nilai $outputLanguage:
     - 'id' → "Output MUST be written entirely in Bahasa Indonesia..."
     - 'en' → "Output MUST be written entirely in English..."
   - Instruksi bahasa tetap di posisi constraint nomor 4 (setelah OUTPUT FORMAT), 
     tetap dengan bobot CRITICAL — jangan turunkan levelnya
   - Panggil generate() dengan meneruskan $generatedCv->output_language ke prompt

7. Update `ATSScoringEngine::buildSystemPrompt()`:
   - Sama seperti TailoringEngine — instruksi bahasa untuk field free-text 
     (feedback.message, suitability.reason, suitability.suggestions) ikut 
     nilai output_language, bukan hardcode Bahasa Indonesia
   - Catatan: matched_keywords dan missing_keywords TETAP mengikuti bahasa 
     yang dipakai di JD (bukan dipaksa ikut output_language) karena ini 
     adalah keyword matching, bukan generated content

Constraint penting:
- Default value 'id' — kalau kolom ini null atau tidak ada, engine harus 
  fallback ke Bahasa Indonesia, tidak boleh error
- Jangan ubah struktur JSON output (tailored_cv_raw, ats_score) — hanya 
  konten teks di dalamnya yang ikut bahasa yang dipilih
- Jangan sentuh GeminiParserService.php — parser CV lama pakai "language 
  preservation" (ikut bahasa sumber), bukan output_language ini

Output yang diharapkan setelah task ini selesai:
- User bisa pilih bahasa saat input JD
- Hasil tailoring dan scoring mengikuti bahasa yang dipilih secara konsisten
- Default tetap Bahasa Indonesia untuk semua record lama yang belum punya 
  nilai output_language