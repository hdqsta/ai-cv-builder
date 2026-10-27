# Task: Mini-RAG Doc Chatbot untuk Vault Dokumentasi CV Builder

## Konteks
Project CV Builder (Laravel) punya vault Obsidian di direktori `cv-builder`, dengan file-file
dokumentasi (PRD, Task Breakdown, ERD, dll) dalam format markdown (`.md`) di folder `docs/`.
Tujuan: bikin fitur tanya-jawab berbasis dokumentasi ini sendiri, pakai pendekatan RAG
(Retrieval-Augmented Generation) sederhana — tanpa vector database eksternal dulu, cukup
in-memory cosine similarity, karena jumlah dokumen masih kecil.

LLM provider: Google Gemini API (sudah dipakai di project ini untuk TailoringEngine dan
GeminiParserService — ikuti pola service class yang sudah ada).

## Scope
Fitur ini terpisah dari flow utama tailoring CV — anggap sebagai tool internal/dev untuk
"tanya dokumentasi project sendiri". Tidak perlu UI mewah, cukup functional.

## Langkah teknis

### 1. Command untuk baca & chunk dokumen
- Buat `php artisan make:command IndexDocsCommand` (nama bebas, misal `docs:index`).
- Scan semua file `.md` di folder `docs/` (pakai `Symfony\Component\Finder\Finder` atau `glob()`).
- Untuk tiap file, split isinya berdasarkan heading level 2 (`##`) — tiap section jadi satu chunk.
  Jangan split di heading level 3 ke bawah (biar chunk gak kepotong terlalu kecil).
- Tiap chunk simpan sebagai array: `['source' => filename, 'heading' => judul_section, 'content' => isi_teks]`.
- Skip chunk yang kosong atau di bawah ~20 karakter (noise).

### 2. Generate embedding
- Buat service class `DocEmbeddingService` (ikuti pola `GeminiParserService` yang sudah ada
  di project — pakai `Http::` facade, retry dengan exponential backoff seperti di
  `ATSScoringEngine`).
- Method `embed(string $text): array` — panggil Gemini embedding endpoint
  (`text-embedding-004` atau model embedding terbaru yang tersedia), return array vektor.
- Di dalam `IndexDocsCommand`, loop tiap chunk dari langkah 1, panggil `embed()`, simpan hasil
  gabungan `{source, heading, content, embedding}` ke file `storage/app/docs_index.json`.
- Tambahkan progress output (`$this->info(...)`) tiap N chunk selesai, biar kelihatan jalan
  kalau dokumennya banyak.

### 3. Fungsi cosine similarity
- Buat helper method (bisa di service yang sama atau class baru `VectorMath`):
  `cosineSimilarity(array $a, array $b): float`
- Rumus: dot product dibagi (magnitude A * magnitude B). Tangani edge case pembagian nol.

### 4. Endpoint tanya-jawab
- Buat route + controller (misal `DocsQaController@ask`), method POST menerima `question`.
- Flow di controller:
  1. Load `docs_index.json` (cache di memory kalau dipanggil berkali-kali, tidak perlu re-read
     tiap request kalau ada cara simple caching, tapi tidak wajib untuk versi pertama).
  2. Embed pertanyaan user pakai `DocEmbeddingService::embed()`.
  3. Hitung cosine similarity ke semua chunk tersimpan.
  4. Ambil top 3-5 chunk dengan skor tertinggi.
  5. Susun system prompt yang menyuntikkan chunk-chunk itu sebagai context, dengan instruksi
     eksplisit: jawab HANYA berdasarkan context yang diberikan, kalau tidak ada info yang
     relevan, katakan terus terang bahwa dokumentasi belum membahas hal itu — jangan mengarang.
  6. Kirim ke Gemini chat completion, kembalikan jawaban ke user beserta sumber chunk yang
     dipakai (nama file + heading) untuk transparansi.
- Response JSON: `{ answer: string, sources: [{file, heading}] }`

### 5. View/testing sederhana
- Halaman blade sederhana (form input pertanyaan + tampilkan jawaban dan sources) di route
  terpisah, tidak perlu masuk ke navigasi utama aplikasi — cukup halaman dev/internal.

## Non-goals (jangan dikerjakan di task ini)
- Jangan pakai pgvector atau database vector store — cukup file JSON + in-memory similarity.
- Jangan integrasikan ke flow tailoring utama.
- Jangan bikin UI polished — functional saja.

## Definition of done
- `php artisan docs:index` berhasil generate `storage/app/docs_index.json` dari semua file
  markdown di `docs/`.
- Endpoint `/docs-qa` (atau nama route lain) bisa menerima pertanyaan dan mengembalikan
  jawaban yang jelas berdasarkan isi dokumentasi, disertai sumber chunk yang dipakai.
- Kalau ditanya hal yang tidak ada di dokumentasi, sistem menjawab jujur bahwa infonya
  tidak tersedia, bukan mengarang.
