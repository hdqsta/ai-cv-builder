# Walkthrough: Pilihan Model AI Gemini (Prompt Polish #2)

Kami telah mempublikasikan perbaikan **Prompt Polish #2: Pilihan Model AI Gemini** (`ai_model`) pada alur kerja Tailoring dan ATS Scoring. Pengguna dapat memilih model AI mana yang akan digunakan saat memproses CV mereka, dengan visualisasi trade-off yang jelas dan arsitektur dinamis.

---

## 🛠️ Perubahan yang Dilakukan

### 1. Database & Schema
*   **Migrasi Baru:** Membuat file migrasi [[2026_08_10_103000_add_ai_model_to_generated_cvs_table.php](file:///c:/laragon/www/cv-builder/database/migrations/2026_08_10_103000_add_ai_model_to_generated_cvs_table.php)] untuk menambahkan kolom `ai_model` (string, nullable).
*   **Model Update:** Memperbarui [[GeneratedCv.php](file:///c:/laragon/www/cv-builder/app/Models/GeneratedCv.php)] untuk mendaftarkan `'ai_model'` pada `$fillable` dan menambahkan konstanta `AI_MODELS` berisi label & deskripsi trade-off:
    *   `gemini-2.5-flash`: Lebih cerdas & konteks lebih dalam (untuk CV kompleks / JD detail).
    *   `gemini-3.1-flash-lite`: Lebih cepat & ringan (default sistem).

### 2. Request & Controller Validation
*   **Validation Request:** Memperbarui [[StoreJobDescriptionRequest.php](file:///c:/laragon/www/cv-builder/app/Http/Requests/StoreJobDescriptionRequest.php)] untuk memvalidasi `ai_model` secara dinamis berdasarkan `in:` dari `array_keys(GeneratedCv::AI_MODELS)`.
*   **Controller Storing:** Memperbarui method `store()` dan `storePdfChecker()` pada [[TailoringController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/TailoringController.php)] untuk menyimpan `ai_model` dari form pengguna.

### 3. AI Engines (Tailoring & Scoring)
*   **Tailoring Engine:** Memperbarui [[TailoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/TailoringEngine.php)] agar memilih model AI secara hierarkis dari `$generatedCv->ai_model`, baru kemudian fallback ke config default jika bernilai `null`.
*   **ATS Scoring Engine:** Memperbarui [[ATSScoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/ATSScoringEngine.php)] agar memilih model AI dari `$generatedCv->ai_model` sehingga alur tailoring dan scoring pada CV yang sama selalu menggunakan model AI yang identik.

### 4. UI & View Updates
Menambahkan Radio Cards pilihan model AI yang di-loop secara dinamis dari `GeneratedCv::AI_MODELS` pada 3 view form:
1.  **Form Tailoring Biasa:** [[create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/create.blade.php)]
2.  **Form Master Pre-Screening:** [[master-create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/master-create.blade.php)]
3.  **Form PDF Upload Checker:** [[pdf-create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/pdf-create.blade.php)]

---

## 🧪 Hasil Pengujian & Verifikasi

### 1. Automated Test Cases
Memperbarui test suite [[TailoringTest.php](file:///c:/laragon/www/cv-builder/tests/Feature/TailoringTest.php)]:
*   Pengujian pengiriman parameter `ai_model` dan verifikasi penyimpanannya di database.
*   Test case `test_legacy_record_with_null_ai_model_runs_without_errors()` untuk menjamin ketersediaan fallback aman pada record lama yang tidak memiliki nilai `ai_model`.

### 2. Aset Kompilasi (Client Environment)
Vite build berhasil dikompilasi dengan lancar.
