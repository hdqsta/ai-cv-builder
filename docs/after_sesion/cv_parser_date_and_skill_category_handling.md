# CV Parser Date & Skill Category Handling

Dokumentasi implementasi alur upload, parsing, review, dan penyimpanan CV terkait granularitas tanggal (bulan) dan kategorisasi skill menggunakan Gemini API.

## Poin Penting Implementasi

### 1. Granularitas Tanggal (Month-based)
- **UI Form (`review.blade.php`)**: Mengubah semua field input tanggal mulai (`start_date`) dan tanggal selesai (`end_date`) untuk pengalaman kerja dan pendidikan dari `type="date"` menjadi `type="month"` (format nilai: `YYYY-MM`). Menampilkan nilai lama dengan memotong string tanggal menggunakan `substr($date, 0, 7)`.
- **Backend Mutation (`CvUploadController::save()`)**: Mengubah input bernilai `YYYY-MM` (panjang 7 karakter) menjadi `YYYY-MM-01` di awal method `save()` sebelum tahap validasi agar memenuhi tipe data kolom database `date`.
- **AI Prompt (`GeminiParserService.php`)**: Memperbarui instruksi system prompt agar Gemini mengembalikan format tanggal `YYYY-MM-01` (hari selalu `01`) berdasarkan bulan+tahun yang tertulis di CV asli dan melarang AI mengarang tanggal hari secara sembarangan.

### 2. Whitelist Kategori Skill
- **Service Whitelist**: Menambahkan konstanta whitelist `SKILL_CATEGORIES` di `GeminiParserService.php` yang berisi 9 kategori resmi:
  1. `Data Management`
  2. `Project Administration`
  3. `Design`
  4. `Web Development`
  5. `Marketing & Content`
  6. `Tools & Productivity`
  7. `Soft Skills`
  8. `Languages`
  9. `Other`
- **System Prompt**: Menginstruksikan Gemini untuk mengklasifikasikan setiap skill yang diekstrak berdasarkan konteks penggunaannya ke salah satu dari kategori di atas (contoh: Figma, Canva, Adobe Photoshop dipetakan ke `Design`). Menambahkan contoh few-shot di prompt untuk menjaga konsistensi.
- **Backend Sanitization**: Memeriksa kategori skill hasil parsing Gemini terhadap whitelist, dan melakukan fallback ke `'Other'` apabila kategori yang dikembalikan di luar whitelist.

### 3. Pengaturan Model Gemini
- Model default diperbarui menjadi **`gemini-3.1-flash-lite`** di seluruh konfigurasi (`.env`, `.env.example`, `config/services.php`, dan `GeminiParserService.php`).
- Menambahkan komentar pengingat siklus hidup model (deprecation) pada berkas `.env` dan `.env.example` agar memudahkan penggantian model di masa depan jika terjadi error 404 tanpa perlu mengubah kode.
