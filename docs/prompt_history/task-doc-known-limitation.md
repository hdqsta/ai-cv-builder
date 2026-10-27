# Task: Catat Known Limitation — Query Campuran di Mini-RAG Doc Chatbot

## Konteks
Fitur mini-RAG doc chatbot (lihat `task-rag-docs-chatbot.md` sebelumnya) sudah dibangun dan
diuji. Dari testing manual, ditemukan satu limitasi yang perlu didokumentasikan (bukan dibenerin
sekarang — sengaja diterima sebagai known limitation untuk versi ini).

## Temuan dari testing
Saat ditanya pertanyaan yang mencampur dua topik sekaligus dalam satu kalimat — contoh:
"Gimana cara kerja fitur ATS scoring dan gimana caranya biar CV gue lolos rekrutmen?" —
sistem gagal menarik chunk yang sebenarnya relevan (`ATS_Scoring_System.md`), padahal chunk
itu terbukti bisa ditarik dengan baik untuk pertanyaan yang lebih fokus/tunggal di test lain.

Penyebabnya: pertanyaan campuran menghasilkan satu embedding vektor yang maknanya "encer"
(blend dari dua topik berbeda), sehingga similarity-nya tidak match kuat ke topik manapun
secara spesifik — termasuk ke chunk yang sebenarnya paling relevan.

Penting: sistem tetap berperilaku benar dalam arti tidak mengarang jawaban (tetap jujur bahwa
info detail tidak ditemukan), hanya saja jawabannya jadi kurang lengkap dibanding seharusnya.
Ini bukan bug fabrikasi, melainkan keterbatasan retrieval untuk query multi-topik.

## Task
Tambahkan section baru di file dokumentasi yang relevan (`docs/nodes/` — buat file baru
misal `Doc_Chatbot_Known_Limitations.md`, atau tambahkan section di file yang sudah menjelaskan
fitur ini kalau sudah ada) dengan isi kurang lebih:

```markdown
## Known Limitation: Query Multi-Topik

Sistem retrieval saat ini menggunakan satu embedding per pertanyaan (single-query embedding,
in-memory cosine similarity, tanpa vector database). Untuk pertanyaan yang mencampur dua topik
berbeda dalam satu kalimat, embedding gabungan cenderung tidak match kuat ke chunk manapun
secara spesifik — termasuk chunk yang sebenarnya relevan untuk salah satu sub-topik.

**Contoh kasus:** pertanyaan "Gimana cara kerja ATS scoring dan gimana biar CV lolos
rekrutmen?" gagal menarik chunk ATS_Scoring_System.md yang relevan, walau chunk itu terbukti
bisa ditarik dengan baik untuk pertanyaan tunggal seperti "untuk apa website ini dibuat?".

**Dampak:** sistem tetap tidak mengarang jawaban (tidak fabrikasi), hanya jawabannya jadi
kurang lengkap untuk pertanyaan bertopik campuran.

**Status:** diterima sebagai keterbatasan versi ini. Fitur ini masih scope dev/internal tool,
bukan production-critical, jadi belum diprioritaskan untuk diperbaiki.

**Potential fix (belum dikerjakan):** query decomposition — memecah pertanyaan jadi
sub-pertanyaan sebelum retrieval, jalankan retrieval terpisah per sub-pertanyaan, gabungkan
hasilnya sebelum disuntik ke prompt jawaban. Menambah kompleksitas dan 1 API call ekstra,
sehingga ditunda sampai benar-benar dibutuhkan.
```

## Non-goals
- Jangan implementasi query decomposition di task ini — ini murni task dokumentasi.
- Jangan ubah kode `DocEmbeddingService`, controller, atau command yang sudah ada.

## Definition of done
- Section "Known Limitation: Query Multi-Topik" tersimpan di vault docs, dengan konten seperti
  di atas (boleh dirapikan gaya bahasanya, tapi substansinya harus tetap ada).
- File yang dibuat/diedit ikut ter-index kalau `php artisan docs:index` dijalankan ulang
  (opsional untuk task ini — cukup pastikan formatnya konsisten pakai heading `##` seperti
  file docs lain, biar tetap chunking-friendly di masa depan).
