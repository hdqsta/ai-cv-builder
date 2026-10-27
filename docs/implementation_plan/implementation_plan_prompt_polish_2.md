# Rencana Implementasi: Pilihan Model AI Gemini (Prompt Polish #2)

Tugas ini menambahkan fitur pilihan model AI Gemini (`ai_model`) pada proses tailoring dan ATS scoring. Pengguna dapat memilih antara model **Gemini 2.5 Flash** (lebih cerdas & dalam) atau **Gemini 3.1 Flash Lite** (lebih cepat & ringan, default).

## Perubahan yang Diusulkan

### 1. Database & Schema

#### [NEW] [Migration File](file:///c:/laragon/www/cv-builder/database/migrations/2026_08_10_103000_add_ai_model_to_generated_cvs_table.php)
Membuat file migrasi baru untuk menambahkan kolom `ai_model` ke tabel `generated_cvs`:
- `ai_model`: string (nullable, default null)

#### [MODIFY] [GeneratedCv.php](file:///c:/laragon/www/cv-builder/app/Models/GeneratedCv.php)
- Menambahkan `'ai_model'` ke `$fillable`.
- Menambahkan konstanta `AI_MODELS`:
  ```php
  public const AI_MODELS = [
      'gemini-2.5-flash' => [
          'label'       => 'Gemini 2.5 Flash',
          'description' => 'Lebih cerdas & konteks lebih dalam. Cocok untuk CV dengan pengalaman kompleks atau JD yang detail. Sedikit lebih lambat.',
      ],
      'gemini-3.1-flash-lite' => [
          'label'       => 'Gemini 3.1 Flash Lite',
          'description' => 'Lebih cepat & ringan. Cocok untuk draft cepat atau JD yang straightforward. Default sistem.',
      ],
  ];
  ```

---

### 2. Request & Controller

#### [MODIFY] [StoreJobDescriptionRequest.php](file:///c:/laragon/www/cv-builder/app/Http/Requests/StoreJobDescriptionRequest.php)
- Menambahkan validasi dinamis berdasarkan `GeneratedCv::AI_MODELS`:
  ```php
  'ai_model' => ['required', 'string', 'in:' . implode(',', array_keys(GeneratedCv::AI_MODELS))],
  ```

#### [MODIFY] [TailoringController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/TailoringController.php)
- **`store()`**: Menyimpan `ai_model` dari request ke database.
- **`storePdfChecker()`**:
  - Menambahkan validasi `ai_model`.
  - Menyimpan `ai_model` ke database.

---

### 3. AI Engines (Tailoring & Scoring)

#### [MODIFY] [TailoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/TailoringEngine.php)
- Mengambil model secara hierarkis di `generate()`:
  ```php
  $model = $generatedCv->ai_model
      ?? config('services.gemini.tailoring_model')
      ?? config('services.gemini.model', 'gemini-3.1-flash-lite');
  ```

#### [MODIFY] [ATSScoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/ATSScoringEngine.php)
- Mengambil model secara hierarkis di `score()` menggunakan `$generatedCv->ai_model`:
  ```php
  $model = $generatedCv->ai_model
      ?? config('services.gemini.scoring_model')
      ?? config('services.gemini.model', 'gemini-3.1-flash-lite');
  ```

---

### 4. UI & Blade Views

Menambahkan radio cards dinamis (mengisi data dengan meloop `GeneratedCv::AI_MODELS`) pada 3 view form:
- **`create.blade.php`** (Full Tailoring)
- **`master-create.blade.php`** (Master Check)
- **`pdf-create.blade.php`** (PDF Checker)

Desain UI:
- Label section: "Model AI yang digunakan"
- Radio Card Layout (bukan dropdown) menampilkan nama model (bold) & deskripsi trade-off (muted).
- Default selected: `gemini-3.1-flash-lite` (atau `old('ai_model')`).

---

## Rencana Verifikasi

### Automated Tests
- Memperbarui `tests/Feature/TailoringTest.php` untuk memvalidasi pengiriman & penyimpanan `ai_model` serta pengujian fallback jika `ai_model` bernilai `null`.
