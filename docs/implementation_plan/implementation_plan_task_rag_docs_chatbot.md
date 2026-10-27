# Implementation Plan — Mini-RAG Doc Chatbot untuk Vault Dokumentasi

Membangun fitur Mini-RAG (Retrieval-Augmented Generation) internal/dev tool untuk melakukan tanya-jawab berbasis dokumentasi project (`docs/`) menggunakan Google Gemini API dan in-memory cosine similarity (file `storage/app/docs_index.json`).

## User Review Required

> [!IMPORTANT]
> - **API Model Embedding**: Menggunakan model Gemini `text-embedding-004` (atau `text-embedding-04`) via endpoint `generativelanguage.googleapis.com`.
> - **Lokasi Index**: Hasil indexing dokumen akan disimpan di `storage/app/docs_index.json`.
> - **Akses Internal/Dev**: Route `/docs-qa` disiapkan sebagai halaman dev internal sederhana tanpa mengubah navigasi utama aplikasi CV Builder.

## Open Questions

Tidak ada blocking questions. Persyaratan dari spesifikasi [task-rag-docs-chatbot.md](file:///c:/laragon/www/cv-builder/docs/prompt_history/task-rag-docs-chatbot.md) sudah sangat jelas.

## Proposed Changes

### Backend Services & Math Helpers

#### [NEW] [DocEmbeddingService.php](file:///c:/laragon/www/cv-builder/app/Services/DocEmbeddingService.php)
- Membuat service `App\Services\DocEmbeddingService`.
- Method `embed(string $text): array` untuk mengambil embedding vector dari Gemini API (`text-embedding-004`).
- Method `cosineSimilarity(array $a, array $b): float` untuk menghitung kemiripan vektor.
- Penanganan retry/exponential backoff & fallback mock jika API key `'mock'` atau kosong.

### Artisan Console Commands

#### [NEW] [IndexDocsCommand.php](file:///c:/laragon/www/cv-builder/app/Console/Commands/IndexDocsCommand.php)
- Membuat command Artisan `docs:index` (`php artisan docs:index`).
- Memindai file `.md` secara rekursif di folder `docs/`.
- Membagi (chunk) isi file berdasarkan heading level 2 (`##`).
- Menyimpan struktur array `{source, heading, content, embedding}` ke `storage/app/docs_index.json`.
- Menampilkan indikator progress di konsol.

### Controllers & Routes

#### [NEW] [DocsQaController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/DocsQaController.php)
- Controller `App\Http\Controllers\DocsQaController`.
- Method `index()` untuk menampilkan Blade view dev.
- Method `ask(Request $request)` untuk memproses pertanyaan user:
  1. Memuat index `storage/app/docs_index.json`.
  2. Embed pertanyaan user.
  3. Menghitung cosine similarity terhadap seluruh chunk dokumen.
  4. Mengambil Top 4 chunk paling relevan.
  5. Menyusun prompt dengan instruksi ketat (strict RAG: jawab hanya berdasar context).
  6. Memanggil Gemini Chat API dan mengembalikan response berisi `answer` dan `sources`.

#### [MODIFY] [web.php](file:///c:/laragon/www/cv-builder/routes/web.php)
- Mendaftarkan route `/docs-qa` (GET & POST).

### Frontend / View

#### [NEW] [docs-qa.blade.php](file:///c:/laragon/www/cv-builder/resources/views/docs-qa.blade.php)
- Menyiapkan tampilan Blade internal yang bersih dan modern untuk testing tanya-jawab dokumentasi project.
- Menyediakan textarea input pertanyaan, submit button, indikator loading, area jawaban markdown, dan daftar referensi dokumen (`sources`).

---

## Verification Plan

### Automated Tests / Artisan Command
1. Jalankan `php artisan docs:index` dan pastikan file `storage/app/docs_index.json` berhasil terbuat dan berisi vektor embedding tiap chunk.

### Manual Verification
1. Buka route `/docs-qa` di browser.
2. Ajukan pertanyaan seputar PRD/ERD (misal: *"Bagaimana flow ATS scoring bekerja?"* atau *"Database apa yang digunakan?"*).
3. Verifikasi bahwa jawaban yang diberikan akurat sesuai file di folder `docs/` beserta `sources` (nama file + heading).
4. Ajukan pertanyaan di luar konteks dokumentasi (misal: *"Siapa presiden pertama Amerika Serikat?"*) dan pastikan chatbot menjawab secara jujur bahwa informasi tidak tersedia di dokumentasi.
