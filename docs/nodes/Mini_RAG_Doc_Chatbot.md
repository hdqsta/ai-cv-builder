---
title: Mini-RAG Doc Chatbot Node & Known Limitations
tags:
  - node
  - rag
  - chatbot
  - docs
---

# 🤖 Mini-RAG Doc Chatbot Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Project_Guidelines_Dos_and_Donts]], [[PRD_AI_CV_Builder]]

## Overview
Fitur internal/dev tool berbasis RAG (Retrieval-Augmented Generation) yang memungkinkan developer/user bertanya seputar dokumentasi project di folder `docs/`. Fitur ini menggunakan Google Gemini Embedding API (`text-embedding-004`), in-memory cosine similarity math, dan diproses tanpa vector database eksternal. Hasil index disimpan di `storage/app/docs_index.json`.

## Technical Components
- **Command:** `App\Console\Commands\IndexDocsCommand` (`php artisan docs:index`)
- **Service:** `App\Services\DocEmbeddingService` (Embedding & Cosine Similarity Math)
- **Controller:** `App\Http\Controllers\DocsQaController`
- **View:** `resources/views/docs-qa.blade.php` (`/docs-qa`)

## Known Limitation: Query Multi-Topik

Sistem retrieval saat ini menggunakan satu embedding per pertanyaan (*single-query embedding*, *in-memory cosine similarity*, tanpa vector database). Untuk pertanyaan yang mencampur dua topik berbeda dalam satu kalimat, embedding gabungan cenderung tidak match kuat ke chunk manapun secara spesifik — termasuk chunk yang sebenarnya relevan untuk salah satu sub-topik.

**Contoh kasus:** pertanyaan *"Gimana cara kerja ATS scoring dan gimana biar CV lolos rekrutmen?"* cenderung menghasilkan similarity score yang terbagi (encer), sehingga gagal menarik chunk `ATS_Scoring_System.md` yang relevan, padahal chunk tersebut terbukti dapat ditarik dengan presisi tinggi jika diajukan pertanyaan tunggal.

**Dampak:** sistem tetap tidak mengarang jawaban (*anti-fabrikasi/anti-halusinasi* tetap terjaga), hanya saja jawabannya menjadi kurang lengkap untuk pertanyaan bertopik campuran.

**Status:** diterima sebagai keterbatasan versi ini. Fitur ini berada dalam scope dev/internal tool, bukan production-critical, sehingga belum diprioritaskan untuk diperbaiki.

**Potential fix (belum dikerjakan):** *query decomposition* — memecah pertanyaan bertopik ganda menjadi beberapa sub-pertanyaan sebelum retrieval, menjalankan retrieval terpisah per sub-pertanyaan, lalu menggabungkan hasilnya sebelum disuntikkan ke prompt LLM. Metode ini menambah 1 API call ekstra & kompleksitas, sehingga ditunda sampai benar-benar dibutuhkan.
