# Walkthrough - Integrasi Upload CV & AI Parsing (Epic 2)

Fitur upload CV otomatis dan parsing bertenaga AI dengan Google Gemini API telah selesai diimplementasikan, diuji, dan diverifikasi sepenuhnya.

## Perubahan yang Dilakukan

1. **Dependensi & Konfigurasi**:
   - Menambahkan package `smalot/pdfparser` untuk ekstraksi teks dokumen PDF.
   - Mengonfigurasi endpoint Gemini API di `config/services.php` dan menyediakan variabel lingkungan di `.env.example` serta `.env`.

2. **Core Services**:
   - `App\Services\TextExtractor`: Layanan terpadu untuk ekstraksi teks mentah dari file PDF (menggunakan Smalot Parser) dan DOCX (menggunakan dekompresi ZIP native dan XML DOM parsing).
   - `App\Services\GeminiParserService`: Menghubungi Google Gemini API menggunakan HTTP Client Laravel. Mengimplementasikan rate limit retry logic dengan exponential backoff (1s -> 2s -> 4s) pada status code 429. Juga menyediakan mode simulasi lokal apabila API Key kosong atau diset sebagai `mock`.

3. **Routing & Controller**:
   - Mendaftarkan endpoint upload, parsing, review, dan save pada `routes/web.php`.
   - Membuat `App\Http\Controllers\CvUploadController` untuk menangani alur kerja file sanitasi, penyimpanan state parsing di Session, dan transaksi database (DB transaction) untuk membersihkan data master lama dan menuliskan data profil hasil review yang baru ke dalam 8 tabel relasi terkait.

4. **UI Views**:
   - `resources/views/cv/upload.blade.php`: Halaman upload file yang responsif dengan animasi spinner saat memanggil AI.
   - `resources/views/cv/review.blade.php`: Form review detail profil (Personal Details, Experience, Projects, Education, Skills, Certifications) yang sudah terisi otomatis (pre-filled) dan mendukung penambahan/penghapusan baris entri secara dinamis via JavaScript.
   - Banner pintasan "Upload CV Lama" ditambahkan ke halaman Beranda (`welcome.blade.php`).

---

## Verifikasi dan Pengujian

1. **Pengujian Ekstraksi & Retry Logic**:
   - Skrip pengujian lokal dijalankan untuk memverifikasi ekstraksi teks dari file dokumen `.docx` berhasil membaca paragraf dan list.
   - Pengujian mock HTTP client berhasil memvalidasi alur retry exponential backoff ketika Gemini mengembalikan status 429 (Rate Limit).

2. **Verifikasi Browser End-to-End**:
   - Mengunggah file CV contoh.
   - Memastikan AI Gemini berhasil mengekstrak dan menampilkan data lengkap di form review.
   - Mengubah nama dari `Jane Doe` menjadi `Jane Doe Testing`.
   - Menyimpan data ke database dan memverifikasi perubahan tersebut di halaman Master Profile.

### Rekaman Demo Browser:
![CV Upload & Parsing Flow](/C:/Users/User/.gemini/antigravity/brain/6769c2ad-bd69-4352-8d09-ff679689f6d4/cv_upload_parsing_flow_1783875904048.webp)

---

## Status Database Pasca Simpan
Tabel master profile berikut berhasil diisi ulang secara transaksional:
- `profiles` (Nama diperbarui: `Jane Doe Testing`)
- `experiences` & `experience_bullets`
- `education`
- `skills`
- `projects` & `project_bullets`
- `certifications`
