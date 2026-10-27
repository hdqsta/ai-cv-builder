# Implementation Plan — Epic 2: Job Description Input

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Task_Breakdown_AI_CV_Builder]], [[Job_Description_Input]]

## Deskripsi Singkat
Epic 2 menangani penginputan teks Job Description (JD) dari pengguna untuk dijadikan konteks dalam proses AI Tailoring CV (Epic 3). Data JD langsung disimpan ke dalam tabel `generated_cvs` dengan status awal `pending` (CvStatus Enum).

---

## Rincian Perubahan & Komponen

### 1. Database & Migrasi
- **File Migrasi:** `database/migrations/2026_07_12_155937_create_generated_cvs_table.php`
- **Skema Tabel `generated_cvs`:**
  - `id`: BigInt, Primary Key, Auto Increment
  - `job_description`: Text, NOT NULL
  - `tailored_cv_raw`: JSON, Nullable
  - `tailored_cv_final`: JSON, Nullable
  - `ats_score`: JSON, Nullable
  - `status`: String, Default `'pending'`
  - `created_at` & `updated_at`: Timestamps

### 2. Backed Enum `CvStatus`
- **File:** `app/Enums/CvStatus.php`
- **Cases:**
  - `Pending = 'pending'`
  - `Tailoring = 'tailoring'`
  - `Review = 'review'`
  - `Scoring = 'scoring'`
  - `Completed = 'completed'`
  - `Failed = 'failed'`

### 3. Model `GeneratedCv`
- **File:** `app/Models/GeneratedCv.php`
- **Fillable:** `['job_description', 'tailored_cv_raw', 'tailored_cv_final', 'ats_score', 'status']`
- **Casts:**
  - `'tailored_cv_raw' => 'array'`
  - `'tailored_cv_final' => 'array'`
  - `'ats_score' => 'array'`
  - `'status' => CvStatus::class`

### 4. Form Request `StoreJobDescriptionRequest`
- **File:** `app/Http/Requests/StoreJobDescriptionRequest.php`
- **Authorization:** `authorize(): bool` mengembalikan `true`
- **Aturan Validasi:** `'job_description' => ['required', 'string', 'min:100']`

### 5. Controller `TailoringController`
- **File:** `app/Http/Controllers/TailoringController.php`
- **Method `create()`:** Menampilkan view `tailoring.create`.
- **Method `store()`:** Menyimpan `GeneratedCv` baru dengan status `CvStatus::Pending`, lalu melakukan redirect ke `route('tailoring.review', $generatedCv)`.
- **Method `review()`:** Placeholder view `tailoring.review` untuk scope Epic 3.

### 6. Routing Named Routes
- `GET /tailoring/create` → `tailoring.create`
- `POST /tailoring` → `tailoring.store`
- `GET /tailoring/{generatedCv}/review` → `tailoring.review`

---

## Verifikasi Test Plan
1. Buka route `tailoring.create` di browser.
2. Submit form kosong atau kurang dari 100 karakter → Pastikan validasi error muncul sesuai standar UI Epic 1.
3. Paste teks JD > 100 karakter → Submit form.
4. Verifikasi baris baru berhasil disimpan di database tabel `generated_cvs` dengan `status = 'pending'`.
5. Verifikasi redirect berhasil menuju `tailoring.review` tanpa error 403 atau MassAssignmentException.
