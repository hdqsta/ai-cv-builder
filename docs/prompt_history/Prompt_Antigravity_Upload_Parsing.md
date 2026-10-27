# Prompt Antigravity — Upload & AI-Parsing CV Lama (Lanjutan Epic 1)

Baca ulang dokumen berikut sebelum mulai (khususnya bagian sanitasi/normalisasi yang sudah disepakati sebelumnya):
- `docs/PRD_AI_CV_Builder.md`
- `docs/ERD_AI_CV_Builder.md`
- `docs/Task_Breakdown_AI_CV_Builder.md`
- `docs/Feedback_Revisi_Epic1.md`

Epic 1 (CRUD Master Profile manual) sudah selesai dan diverifikasi. Sekarang kerjakan bagian yang sebelumnya sengaja ditunda: **upload CV lama (PDF/DOCX) → parsing otomatis dengan AI → review manual sebelum disimpan sebagai Master Profile.**

## LLM Provider

Gunakan **Google Gemini API**. Simpan API key di `.env` sebagai `GEMINI_API_KEY` (saya akan isi manual nilainya, kamu cukup siapkan referensinya di `config/services.php` dan baca dari `env('GEMINI_API_KEY')`). Panggil API menggunakan Laravel HTTP Client (`Http::` facade) langsung ke endpoint Gemini — tidak perlu install package unofficial kecuali kamu jelaskan dulu alasannya.

## Alur Fitur

1. **Upload** — halaman baru untuk upload file CV lama (terima format PDF dan DOCX). Validasi tipe file & ukuran maksimal (misal 5MB).
2. **Ekstraksi teks mentah** — sebelum dikirim ke LLM, ekstrak teks dari file menggunakan library PHP yang sesuai (PDF dan DOCX beda cara ekstraksinya — silakan pilih library yang tepat, dan sebutkan di ringkasan akhir apa yang kamu pakai beserta alasannya).
3. **Parsing via Gemini** — kirim teks CV yang sudah diekstrak ke Gemini dengan prompt yang menginstruksikan untuk mengembalikan data terstruktur sesuai skema Master Profile kita: `profile` (name, email, phone, location, summary, links), `experiences` (company, position, start_date, end_date, bullet_points sebagai array string), `education`, `skills` (name + category), `projects` (name, bullet_points, tech_stack), `certifications`. Minta output dalam format JSON murni.
4. **Sanitasi & normalisasi** — setelah menerima JSON dari Gemini, lakukan pembersihan di backend sebelum ditampilkan ke user:
   - Standarisasi semua tanggal ke format `YYYY-MM-DD` (tangani berbagai kemungkinan format input seperti "Jan 2022", "2022-01", dll.)
   - Pastikan `skills` berupa array bersih per item, bukan satu string gabungan
   - Pastikan `bullet_points` berupa array of string yang sudah dipisah per poin, bukan satu paragraf panjang
5. **Halaman review** — tampilkan hasil parsing yang sudah disanitasi dalam bentuk form yang bisa diedit penuh oleh user (pre-filled, bukan read-only). User harus eksplisit klik "Simpan ke Master Profile" untuk data ini benar-benar masuk ke tabel `profiles`, `experiences`, `experience_bullets`, dst. Sebelum itu, data belum tersimpan permanen (boleh disimpan sementara di session).
6. **Simpan ke Master Profile** — saat user konfirmasi, insert data ke tabel-tabel terkait (termasuk membuat `experience_bullets`/`project_bullets` dengan `order` sesuai urutan tampil).

## Constraint

- Jika Gemini gagal mengembalikan JSON valid (parse error) atau API call gagal, tampilkan pesan error yang jelas ke user dan jangan biarkan sistem crash — beri opsi untuk mengisi profile secara manual sebagai fallback.
- Jangan menggabungkan proses ini dengan fitur AI Tailoring/Scoring (Epic 3 & 4) — ini murni untuk mengisi Master Profile dari CV lama.
- Ikuti gaya styling yang sudah ada saat ini (Tailwind default, tanpa custom design system) — styling final akan saya kerjakan terpisah nanti.

## Verifikasi

Setelah selesai, tunjukkan implementation plan dulu untuk saya review sebelum mulai menulis kode — sama seperti alur Epic 1 kemarin. Setelah saya approve, jalankan verifikasi dengan mencoba upload 1 contoh CV nyata dan tunjukkan hasil parsing + sanitasinya ke saya.
