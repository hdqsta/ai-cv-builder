# Walkthrough - Epic 3: AI Tailoring Engine

Main Hub: [[AI_CV_Builder_Graph]] | Implementation Plan: [[implementation_plan_epic_3]]

Fitur **Epic 3: AI Tailoring Engine** telah selesai diimplementasikan, diuji, dan diverifikasi sepenuhnya.

---

## Perubahan yang Dilakukan

1. **Konfigurasi Gemini Model (`config/services.php`)**:
   - Menambahkan konfigurasi `tailoring_model` yang dibaca dari environment variable `GEMINI_TAILORING_MODEL` dengan fallback `gemini-3.1-flash-lite`.

2. **Core Services**:
   - `App\Services\MasterProfileFormatter`: Memformat seluruh entitas database Master Profile menjadi struktur JSON yang persis sesuai kontrak `Call 1: Tailoring Engine` pada [[JSON_Contract_Tailoring_Scoring]].
   - `App\Services\TailoringEngine`:
     - Mengubah status `GeneratedCv` ke `CvStatus::Tailoring` sebelum API call.
     - Memasang timeout 60 detik dan retry backoff pada 429 status code.
     - Membersihkan markdown code block (` ```json `) dari teks respon Gemini.
     - **Hard Constraint Validation:** Memverifikasi seluruh `source_id` dan `source_bullet_id` pada hasil reframing agar wajib ada di dalam set ID Master Profile. Jika terdeteksi ID tak dikenal, status otomatis diubah ke `CvStatus::Failed` dengan penjelasan error.
     - Mode simulasi mock otomatis aktif jika API Key berupa `mock` atau belum diset.

3. **Controller & Named Routes**:
   - `App\Http\Controllers\TailoringController`:
     - Method `generate()` untuk memicu pemrosesan AI.
     - Method `review()` untuk me-render tampilan UI berbasis status (`Pending`, `Tailoring`, `Failed`, `Review`).
   - Named Route baru: `POST /tailoring/{generatedCv}/generate` → `tailoring.generate`.

4. **UI Studio Views (`resources/views/tailoring/review.blade.php`)**:
   - Tampilan *AI CV Tailoring Studio* berdesain *glassmorphic dark theme*.
   - Menampilkan status badge interaktif, pesan error saat gagal, serta pratinjau hasil reframing lengkap dengan tag rujukan `Source ID` & `src_bullet ID`.

---

## Verifikasi dan Pengujian

- [x] Konfigurasi model Gemini dibaca dari `config('services.gemini.tailoring_model')`.
- [x] Status di-set ke `CvStatus::Tailoring` sebelum request HTTP dikirim.
- [x] Hard Constraint Validation berjalan mengecek validitas `source_id` dan `source_bullet_id`.
- [x] Kegagalan (JSON invalid / ID fabrikasi / timeout) ditangkap secara aman, di-set ke `CvStatus::Failed`, dan tidak menimbulkan HTTP 500.
- [x] Hasil tailoring valid disimpan ke `tailored_cv_raw` dan status berubah menjadi `CvStatus::Review`.
- [x] Dokumen walkthrough dan implementation plan tersimpan di `docs/walkthrough/walkthrough_epic_3.md` dan `docs/implementation_plan/implementation_plan_epic_3.md`.
