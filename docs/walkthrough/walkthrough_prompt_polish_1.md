# Walkthrough: Pilihan Bahasa Output (Prompt Polish #1)

Kami telah sukses mengimplementasikan fitur **Pilihan Bahasa Output** (`output_language`) untuk proses tailoring dan scoring ATS. Fitur ini memungkinkan pengguna untuk memilih apakah hasil output CV yang di-generate dan evaluasi ATS disajikan dalam Bahasa Indonesia atau Bahasa Inggris.

---

## 🛠️ Perubahan yang Dilakukan

### 1. Database & Schema
*   **Migrasi Baru:** Membuat file migrasi [[2026_08_05_102000_add_output_language_to_generated_cvs_table.php](file:///c:/laragon/www/cv-builder/database/migrations/2026_08_05_102000_add_output_language_to_generated_cvs_table.php)] untuk menambahkan kolom `output_language` (string, default 'id').
*   **Model Update:** Memasukkan `output_language` ke array `$fillable` dan mendefinisikan konstanta bahasa (`OUTPUT_LANG_ID = 'id'`, `OUTPUT_LANG_EN = 'en'`) di [[GeneratedCv.php](file:///c:/laragon/www/cv-builder/app/Models/GeneratedCv.php)].

### 2. Request & Controller Validation
*   **Validation Request:** Memperbarui [[StoreJobDescriptionRequest.php](file:///c:/laragon/www/cv-builder/app/Http/Requests/StoreJobDescriptionRequest.php)] untuk menambahkan aturan validasi `output_language` (wajib, harus bernilai `id` atau `en`).
*   **Controller Storing:**
    *   Memperbarui method `store()` di [[TailoringController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/TailoringController.php)] untuk menyimpan bahasa output pilihan pengguna.
    *   Memperbarui method `storePdfChecker()` untuk memvalidasi dan menyimpan bahasa output pilihan dari request file upload.

### 3. AI Engine Prompt Updates
*   **Tailoring Engine:** Memperbarui [[TailoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/TailoringEngine.php)] agar meneruskan bahasa pilihan ke prompt builder. AI prompt memuat instruksi dinamis pada bagian *Strict Hard Constraints* untuk melarang translasi/bahasa yang tidak sesuai dan memaksa output sepenuhnya ditulis dalam bahasa pilihan (`id` / `en`).
*   **Scoring Engine:** Memperbarui [[ATSScoringEngine.php](file:///c:/laragon/www/cv-builder/app/Services/ATSScoringEngine.php)] agar free-text fields (alasan kecocokan, feedback kesalahan, dan saran-saran) di-generate dalam bahasa pilihan. Poin keyword (`matched_keywords` dan `missing_keywords`) tetap mempertahankan bahasa orisinal Job Description agar pencocokan tepat sasaran.

### 4. UI & View Updates
Menambahkan radio button pemilihan bahasa di bawah textarea Job Description pada 3 form utama dengan styling premium yang konsisten:
1.  **Form Tailoring Biasa:** [[create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/create.blade.php)] (Menggunakan accent color Indigo).
2.  **Form Master Pre-Screening:** [[master-create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/master-create.blade.php)] (Menggunakan accent color Indigo).
3.  **Form PDF Upload Checker:** [[pdf-create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/tailoring/pdf-create.blade.php)] (Menggunakan accent color Fuchsia).

---

## 🧪 Hasil Pengujian & Verifikasi

### 1. Automated Test Cases
Memperbarui test suite [[TailoringTest.php](file:///c:/laragon/www/cv-builder/tests/Feature/TailoringTest.php)]:
*   Semua pengisian data form disesuaikan agar mengirim parameter `output_language`.
*   Menambahkan verifikasi database bahwa bahasa terpilih disimpan dengan benar (misal: memvalidasi value `'en'` terarsip sukses di record tabel `generated_cvs`).
*   Menambahkan test case `test_can_display_master_check_create_page` untuk memvalidasi halaman form master check berjalan sukses.

### 2. Aset Kompilasi (Client Environment)
Vite build berhasil dikompilasi dengan lancar:
```bash
vite v8.1.4 building client environment for production...
built in 667ms
```
