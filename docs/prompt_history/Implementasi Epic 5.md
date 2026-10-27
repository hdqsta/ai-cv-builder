Konteks: Laravel project AI CV Builder. Epic 1-3 sudah selesai. tailored_cv_raw
sudah tersimpan hasil AI Tailoring (Epic 3), status GeneratedCv saat ini 'review'.
Sekarang implementasi Epic 5: simpan editan user jadi versi final + generate PDF.
(Epic 4 - ATS Scoring - sengaja dilewati dulu sesuai urutan pengerjaan yang sudah
direncanakan; nanti disambung di Epic 6.)

LANGKAH AWAL:

- Cek apakah package `barryvdh/laravel-dompdf` sudah ter-install (cek composer.json).
  Kalau belum, install dulu.
- Cek ulang halaman resources/views/tailoring/review.blade.php dari Epic 3 — form
  editnya saat ini disabled/placeholder, sekarang diaktifkan.

1. Update halaman review.blade.php jadi editable:
    - Tiap section (summary, experiences, education, skills, projects) jadi field
      yang bisa diedit user: textarea untuk summary & tiap bullet_point.content,
      input text untuk skill names, dsb.
    - source_id dan source_bullet_id TETAP dikirim tapi sebagai hidden input
      (read-only dari user, bukan field yang diedit) — supaya struktur relasinya
      tetap terjaga waktu disave
    - Form submit ke route baru `tailoring.finalize` (POST)

2. Form Request `SaveFinalCvRequest`:
    - authorize() → true
    - Validasi struktur array (summary: required string; experiences: array;
      experiences._.bullet_points._.content: required string; dst — sesuaikan
      field yang benar-benar bisa diedit user)

3. Controller `TailoringController@finalize`:
    - Terima input tervalidasi, susun ulang jadi struktur JSON PERSIS sama seperti
      tailored_cv_raw (field sama, cuma content yang mungkin berubah)
    - Simpan ke `$generatedCv->tailored_cv_final`, save()
    - Status TETAP 'review' (belum diubah ke 'completed' — status completed
      idealnya nunggu scoring juga selesai di Epic 6, jangan diubah prematur di sini)
    - Redirect balik ke tailoring.review dengan flash message "Tersimpan"

4. Route baru:
    - POST /tailoring/{generatedCv}/finalize → nama `tailoring.finalize`
    - GET /tailoring/{generatedCv}/pdf → nama `tailoring.pdf`

5. Template PDF (`resources/views/pdf/cv-template.blade.php`):
    - HTML/CSS SEDERHANA, ATS-friendly:
        - HANYA elemen semantik (h1, h2, h3, ul, li, p) — TIDAK BOLEH pakai
          CSS grid/flexbox multi-kolom kompleks, TIDAK BOLEH nested table,
          TIDAK BOLEH gambar/icon sebagai pengganti teks
        - 1 kolom, urutan linear: Header kontak → Summary → Experience →
          Education → Skills → Projects → Certifications
    - Data source WAJIB gabungan 2 sumber:
      a. Header kontak (nama, email, telepon, lokasi, links) → query LANGSUNG
      dari tabel `profiles`, BUKAN dari tailored_cv_final (field ini memang
      tidak ada di JSON Contract output tailoring)
      b. Certifications → query LANGSUNG dari tabel `certifications`, BUKAN
      dari tailored_cv_final (field ini juga tidak ada di kontrak output
      tailoring — certifications adalah fakta statis, tidak direframe)
      c. Summary, experience, education, skills, project → dari
      `$generatedCv->tailored_cv_final`

6. Controller `TailoringController@downloadPdf(GeneratedCv $generatedCv)`:
    - GUARD: kalau `tailored_cv_final` masih null (user belum pernah klik
      "Simpan"), redirect balik ke halaman review dengan pesan error
      "Selesaikan review & simpan dulu sebelum download PDF" — JANGAN coba
      render PDF dengan data kosong
    - Ambil profile + certifications (query langsung), gabung dengan
      tailored_cv_final, render ke view pdf.cv-template
    - Generate via DomPDF, return sebagai download response
      (nama file: slug nama profile + tanggal, misal `CV_HattaSiddiq_2026-07-24.pdf`)

7. Tombol "Download PDF" di halaman review — tampil/aktif HANYA kalau
   `tailored_cv_final` sudah ada (cek di Blade dengan @if)

Constraint penting:

- Semua route WAJIB named route
- JANGAN implementasi ATS Scoring di sini — scope Epic 4
- JANGAN ubah status ke 'completed' di step finalize — itu keputusan yang
  diatur ulang di Epic 6 saat wiring flow lengkap
- Test manual wajib: buka PDF hasil download, coba select & copy teks di
  dalamnya — pastikan teks bisa di-select (bukan hasil render sebagai gambar)

Output yang diharapkan:

- User bisa edit hasil tailoring di halaman review, klik "Simpan" →
  tersimpan sebagai tailored_cv_final
- Tombol "Download PDF" muncul setelah data final ada, klik → PDF ke-generate
  berisi kontak (dari profiles) + certifications (dari master profile) +
  konten tailored (dari tailored_cv_final), teks bisa di-select
