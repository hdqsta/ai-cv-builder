# Rencana Implementasi: Pilihan Bahasa Output (Prompt Polish #1)

Tugas ini bertujuan menambahkan fitur pilihan bahasa output (`output_language`) saat melakukan proses tailoring dan ATS scoring, yang sebelumnya di-hardcode ke Bahasa Indonesia. Pengguna dapat memilih antara Bahasa Indonesia (`id`) atau Bahasa Inggris (`en`).

## Perubahan yang Diusulkan

### 1. Database & Schema

#### [NEW] [Migration File](file:///c:/laragon/www/cv-builder/database/migrations/2026_08_05_102000_add_output_language_to_generated_cvs_table.php)
Membuat file migrasi baru untuk menambahkan kolom `output_language` ke tabel `generated_cvs`:
- `output_language`: string (default 'id')

#### [MODIFY] [GeneratedCv.php](file:///c:/laragon/www/cv-builder/app/Models/GeneratedCv.php)
- Menambahkan `'output_language'` ke `$fillable`.
- Menambahkan konstanta:
  ```php
  public const OUTPUT_LANG_ID = 'id';
  public const OUTPUT_LANG_EN = 'en';
  ```

---

### 2. Request Validation & Controller

#### [MODIFY] [StoreJobDescriptionRequest.php](file:///c:/laragon/www/cv-builder/app/Http/Requests/StoreJobDescriptionRequest.php)
- Menambahkan validasi:
  ```php
  'output_language' => ['required', 'string', 'in:id,en'],
  ```

#### [MODIFY] [TailoringController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/TailoringController.php)
- **`store()`**: Menyimpan `output_language` ke record `GeneratedCv` yang baru dibuat.
- **`storePdfChecker()`**:
  - Menambahkan validasi: `'output_language' => ['required', 'string', 'in:id,en']`.
  - Menyimpan `output_language` ke database.

---

### 3. AI Engines (Tailoring & Scoring)

#### [MODIFY] [TailoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/TailoringEngine.php)
- Memperbarui `generate()` agar meneruskan `$generatedCv->output_language` (fallback ke `'id'` jika null) ke `buildSystemPrompt()`.
- Memperbarui `buildSystemPrompt()` untuk menerima `$outputLanguage` (string, default 'id').
- Menyesuaikan instruksi bahasa di poin 4 *Strict Hard Constraints* secara dinamis:
  - Jika `'id'`: "Output MUST be written entirely in Bahasa Indonesia..."
  - Jika `'en'`: "Output MUST be written entirely in English..."
  - Dan detail terkait translasi dan istilah teknis.

#### [MODIFY] [ATSScoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/ATSScoringEngine.php)
- Memperbarui `score()` agar meneruskan `$generatedCv->output_language ?? 'id'` ke `buildSystemPrompt()`.
- Memperbarui `buildSystemPrompt()` untuk menerima `$outputLanguage` (string, default 'id').
- Menyesuaikan instruksi bahasa di poin 4 *Strict Output Rules*:
  - Output teks bebas (`feedback.message`, `suitability.reason`, `suitability.suggestions`) mengikuti `$outputLanguage`.
  - Catatan: `matched_keywords` dan `missing_keywords` tetap mengikuti bahasa dari Job Description (agar keyword matching akurat).

---

### 4. UI & Blade Views

Menambahkan radio button pilihan bahasa pada seluruh form input Job Description:
- **`create.blade.php`** (Full Tailoring)
- **`master-create.blade.php`** (Master Check)
- **`pdf-create.blade.php`** (PDF Checker)

Desain UI:
- Label: "Bahasa output CV yang akan di-generate"
- Pilihan:
  - 🇮🇩 Bahasa Indonesia (default, checked)
  - 🇬🇧 English

---

## Rencana Verifikasi

### Automated Tests
- Menambahkan test case baru di `TailoringTest.php` untuk memverifikasi alur input dengan bahasa `'en'` dan memastikannya tersimpan dengan benar di database.
