# Implementation Plan — Epic 5: Output Generation (Text Final & PDF)

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Task_Breakdown_AI_CV_Builder]], [[PDF_Generator]], [[Tailoring_Engine]]

## Deskripsi Singkat
Epic 5 menangani penyimpanan hasil editan pengguna dari tampilan review AI Tailoring menjadi versi final (`tailored_cv_final`), serta meng-generate dokumen PDF yang ATS-friendly berdasarkan gabungan data `Profile`, `Certification`, dan `tailored_cv_final`.

---

## Rincian Perubahan & Komponen Baru

### 1. Package PDF (`barryvdh/laravel-dompdf`)
- Menginstal package `barryvdh/laravel-dompdf` (v3.1.2) via Composer untuk me-render HTML/CSS menjadi file PDF secara native.

### 2. Form Request `SaveFinalCvRequest` (`app/Http/Requests/SaveFinalCvRequest.php`)
- **Fungsi:** Memvalidasi input form editan pengguna dari halaman review.
- **Validasi:** `summary` (required), `experiences` & `bullet_points` (dengan `source_id` dan `source_bullet_id` sebagai hidden fields), `education`, `skills_highlighted`, dan `projects`.

### 3. Controller Method Updates (`app/Http/Controllers/TailoringController.php`)
- **Method `finalize()`:**
  - Menerima input validasi dari `SaveFinalCvRequest`.
  - Menyimpan struktur JSON ke `$generatedCv->tailored_cv_final`.
  - Status tetap `'review'` (tidak diubah ke `'completed'` secara prematur sebelum ATS Scoring di Epic 6).
  - Redirect balik ke `route('tailoring.review', $generatedCv)` dengan pesan sukses.
- **Method `downloadPdf()`:**
  - **Guard Check:** Jika `tailored_cv_final` masih null, redirect ke `tailoring.review` dengan pesan error `"Selesaikan review & simpan dulu sebelum download PDF"`.
  - Mengambil data `Profile::first()` (kontak, nama, email, phone, location, links) dan `Certification::all()`.
  - Menggabungkan data dengan `$generatedCv->tailored_cv_final['tailored_cv']`.
  - Me-render ke view `pdf.cv-template` via DomPDF.
  - Menghasilkan file PDF terunduh dengan format nama: `CV_{NamaKandidat}_{YYYY-MM-DD}.pdf`.

### 4. Template PDF ATS-Friendly (`resources/views/pdf/cv-template.blade.php`)
- Menggunakan HTML/CSS sederhana berstruktur linear 1-kolom.
- HANYA menggunakan elemen semantik (`h1`, `h2`, `h3`, `p`, `ul`, `li`).
- TIDAK menggunakan CSS grid/flexbox multi-kolom, TIDAK menggunakan nested table, dan TIDAK menggunakan icon/gambar sebagai pengganti teks.
- Teks terjamin bisa di-select & copy (vector text) untuk lolos filter ATS parser.

### 5. Interaktif Review View (`resources/views/tailoring/review.blade.php`)
- Mengubah seluruh field review menjadi form input/textarea editable.
- Menyimpan `source_id` dan `source_bullet_id` sebagai `<input type="hidden">`.
- Tombol "Download PDF (ATS)" muncul dan aktif secara kondisional jika `tailored_cv_final` sudah ada.

### 6. Named Routes (`routes/web.php`)
- `POST /tailoring/{generatedCv}/finalize` → `tailoring.finalize`
- `GET /tailoring/{generatedCv}/pdf` → `tailoring.pdf`

---

## Verifikasi Test Plan
1. Jalankan test suite `FinalizeAndPdfTest` (3 Passed, 12 Assertions).
2. Verifikasi bahwa mencoba mengunduh PDF saat `tailored_cv_final` null memicu redirect error guard.
3. Edit teks pada form review, klik "Simpan Versi Final", dan pastikan data tersimpan di kolom `tailored_cv_final`.
4. Klik "Download PDF (ATS)" dan pastikan PDF ter-download dengan nama file `CV_Name_YYYY-MM-DD.pdf`.
5. Buka file PDF dan uji seleksi teks (select & copy) untuk memastikan ATS-friendliness.
