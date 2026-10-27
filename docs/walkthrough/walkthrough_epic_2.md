# Walkthrough - Epic 2: Job Description Input

Main Hub: [[AI_CV_Builder_Graph]] | Implementation Plan: [[implementation_plan_epic_2]]

Fitur input Job Description (JD) pada **Epic 2** telah berhasil diimplementasikan sepenuhnya sesuai spesifikasi arsitektur.

---

## Ringkasan Perubahan yang Dilakukan

1. **Database & Migrasi (`generated_cvs`)**:
   - Memperbarui file migrasi `database/migrations/2026_07_12_155937_create_generated_cvs_table.php` agar field `tailored_cv_raw` dan `tailored_cv_final` bersifat `nullable()`, serta `status` memiliki nilai default `'pending'`.

2. **Backed Enum `CvStatus`**:
   - Dibuat pada `app/Enums/CvStatus.php` dengan nilai enum string: `Pending`, `Tailoring`, `Review`, `Scoring`, `Completed`, dan `Failed`.

3. **Model `GeneratedCv`**:
   - Dikonfigurasi pada `app/Models/GeneratedCv.php` dengan `$fillable` eksplisit `['job_description', 'tailored_cv_raw', 'tailored_cv_final', 'ats_score', 'status']`.
   - Menggunakan `$casts` untuk enum `status` (`CvStatus::class`) dan array JSON.

4. **Form Request `StoreJobDescriptionRequest`**:
   - Dibuat pada `app/Http/Requests/StoreJobDescriptionRequest.php` dengan `authorize()` bernilai `true` dan aturan `min:100` untuk `job_description`.

5. **Controller & Routing**:
   - Dibuat `App\Http\Controllers\TailoringController` dengan method `create()`, `store()`, dan `review()`.
   - Mendaftarkan named routes pada `routes/web.php`:
     - `tailoring.create` (`GET /tailoring/create`)
     - `tailoring.store` (`POST /tailoring`)
     - `tailoring.review` (`GET /tailoring/{generatedCv}/review`)

6. **UI Views**:
   - `resources/views/tailoring/create.blade.php`: Halaman input JD berdesain *glassmorphic dark theme*, konsisten dengan layout utama (`layouts.app`), dilengkapi *character counter* JavaScript.
   - `resources/views/tailoring/review.blade.php`: Halaman *placeholder* untuk me-review JD sebelum melangkah ke **Epic 3 (AI Tailoring Engine)**.
   - Navigasi "Tailor CV (JD)" ditambahkan ke header utama.

---

## Status Verifikasi

- [x] Routing menggunakan **Named Routes** tanpa hardcoded URL string.
- [x] Form submit menyimpan data ke `generated_cvs` dengan `status = pending`.
- [x] Bebas dari error `403 Forbidden` atau `MassAssignmentException`.
- [x] Dokumen implementasi tersimpan di `docs/implementation_plan/implementation_plan_epic_2.md` dan `docs/walkthrough/walkthrough_epic_2.md`.
