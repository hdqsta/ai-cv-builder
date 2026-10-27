# Prompt Awal — Antigravity (Epic 1: Master Profile Management)

Baca dulu dokumen berikut di folder `docs/` sebelum mulai kerja:
- `docs/PRD_AI_CV_Builder.md`
- `docs/ERD_AI_CV_Builder.md`
- `docs/Task_Breakdown_AI_CV_Builder.md`
- `docs/JSON_Contract_Tailoring_Scoring.md`

Konteks project: ini adalah aplikasi Laravel untuk AI CV Builder. Untuk tahap ini, kerjakan **HANYA Epic 1: Master Profile Management** dari Task_Breakdown_AI_CV_Builder.md. Jangan mulai Epic lain dulu sebelum saya review dan approve hasil Epic 1 ini.

## Tugas

1. Buat migration untuk semua tabel yang didefinisikan di ERD_AI_CV_Builder.md: `profiles`, `experiences`, `experience_bullets`, `education`, `skills`, `projects`, `project_bullets`, `certifications`. Ikuti tipe data dan relasi (foreign key) persis seperti yang tertulis di dokumen ERD.
2. Buat Model Eloquent untuk masing-masing tabel di atas, lengkap dengan relasi antar model (contoh: `Experience` hasMany `ExperienceBullet`).
3. Buat Controller + route + view (boleh pakai Blade sederhana dulu, belum perlu styling khusus) untuk CRUD:
   - Profile (form single-record, karena MVP ini personal-only — cukup 1 baris data)
   - Experience beserta bullet points-nya (nested form: tambah/edit/hapus bullet dalam satu experience)
   - Education
   - Skills
   - Projects beserta bullet points-nya
   - Certifications
4. Tambahkan validasi input dasar di tiap form (field wajib, format tanggal, dsb.) sesuai constraint yang masuk akal dari ERD.
5. Jangan implementasi fitur upload & AI-parsing CV lama dulu — itu task terpisah setelah CRUD manual ini selesai dan saya review.

## Batasan

- Jangan install package tambahan di luar yang sudah ada di Laravel default, kecuali memang dibutuhkan untuk hal di atas (kalau perlu, jelaskan dulu alasannya sebelum install).
- Jangan buat sistem autentikasi/login — ini di luar scope MVP saat ini.
- Setelah selesai, buatkan ringkasan singkat: file apa saja yang dibuat/diubah, dan langkah apa yang saya perlu lakukan secara manual (misal jalankan `php artisan migrate`) untuk mengetes hasilnya.

Tunggu review saya sebelum lanjut ke Epic berikutnya.
