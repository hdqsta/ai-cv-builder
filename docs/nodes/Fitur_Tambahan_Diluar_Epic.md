---
title: Fitur Tambahan Diluar Epic Awal
tags:
  - node
  - features
  - enhancements
  - polish
date: 2026-08-11
---

# 🚀 Fitur Tambahan & Enhancements di Luar Epic Awal

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[PRD_AI_CV_Builder]], [[Task_Breakdown_AI_CV_Builder]]

## 📌 Overview
Dokumen ini mencatat seluruh fitur, modifikasi alur kerja, dan penyempurnaan (*enhancements/polish*) yang diimplementasikan **di luar lingkup spesifikasi Epic 1 - 6 awal**. Fitur-fitur ini dikembangkan berdasarkan kebutuhan nyata pengguna untuk meningkatkan efisiensi, fleksibilitas UX, serta performa integrasi AI.

---

## 🛠️ Daftar Fitur Tambahan

### 1. 📄 Cek ATS PDF Instan (*Direct PDF ATS Checker*)
* **Rute:** `GET /pdf-checker` & `POST /pdf-checker`
* **Deskripsi:** Pengguna dapat mengunggah file CV (PDF/DOCX) yang sudah jadi untuk diperiksa skor ATS dan kesesuaian posisinya **secara instan tanpa menyangkutpautkan atau merusak data Master Profile di database**.
* **Komponen Teknis:**
  * `TailoringController@createPdfChecker` & `storePdfChecker`
  * `TextExtractor` (`Smalot\PdfParser\Parser` & Native Zip/XML Parser)
  * View: `resources/views/tailoring/pdf-create.blade.php`

### 2. ⚡ Pre-Screening Master Profile Instan (`/master-check`)
* **Rute:** `GET /master-check`
* **Deskripsi:** Pengguna dapat menguji kecocokan data Master Profile aktif mereka saat ini terhadap suatu Job Description tanpa harus melalui alur AI Tailoring penuh terlebih dahulu.
* **Komponen Teknis:**
  * `TailoringController@createMasterCheck`
  * View: `resources/views/tailoring/master-create.blade.php`

### 3. 🌐 Pilihan Bahasa Output (*Prompt Polish #1*)
* **Kolom DB:** `generated_cvs.output_language` (default `'id'`)
* **Nilai:** `'id'` (Bahasa Indonesia) & `'en'` (English)
* **Deskripsi:** Pengguna dapat memilih apakah hasil output CV yang di-tailor dan feedback analisis ATS disajikan dalam Bahasa Indonesia atau Bahasa Inggris. Keyword matching (`matched_keywords`) tetap mempertahankan ejaan orisinal Job Description demi akurasi.
* **Komponen Teknis:**
  * `TailoringEngine::buildSystemPrompt` & `ATSScoringEngine::buildSystemPrompt`
  * `StoreJobDescriptionRequest`

### 4. 🤖 Pilihan Model AI Gemini (*Prompt Polish #2*)
* **Kolom DB:** `generated_cvs.ai_model` (nullable, fallback ke default config)
* **Nilai:**
  * `gemini-2.5-flash`: Lebih cerdas & konteks lebih dalam (untuk CV kompleks / JD detail).
  * `gemini-3.1-flash-lite`: Lebih cepat & ringan (default sistem).
* **Deskripsi:** Pilihan model AI disajikan dalam bentuk *Radio Cards* yang dirender secara dinamis di UI melalui konstanta `GeneratedCv::AI_MODELS`.
* **Komponen Teknis:**
  * `GeneratedCv::AI_MODELS`
  * `TailoringEngine` & `ATSScoringEngine` model resolution

### 5. 📅 Input Tanggal Berbasis Bulan (`type="month"`)
* **Deskripsi:** Mengubah input tanggal di form review CV dari `type="date"` ($YYYY-MM-DD$) menjadi `type="month"` ($YYYY-MM$), dengan mutasi otomatis pada backend ke $YYYY-MM-01$ agar ramah pengguna CV.
* **Komponen Teknis:**
  * `CvUploadController@save`
  * `resources/views/cv-upload/review.blade.php`

### 6. 🏷️ Categorization Skill Fungsional Baku (8 Kategori Referensi)
* **Deskripsi:** Engine parser Gemini tidak lagi mengopi label generik dari CV asal (seperti *"Hard Skills"* / *"Soft Skills"*), melainkan secara fungsional memetakan skill ke dalam 8 kategori baku:
  1. *Data Management*
  2. *Project Administration*
  3. *Design*
  4. *Web Development*
  5. *Marketing & Content*
  6. *Tools & Productivity*
  7. *Soft Skills*
  8. *Languages*
* **Komponen Teknis:** `GeminiParserService` system prompt.

### 7. 🔄 Retry Loop & Rate Limit Handling (HTTP 429)
* **Deskripsi:** Mekanisme percobaan ulang otomatis (*exponential backoff retry*) pada seluruh engine AI jika kuota API Gemini terkena pembatasan laju (*Rate Limit / HTTP 429*).

### 8. 🎨 Pemisahan UI Action Cards Dashboard
* **Deskripsi:** Penataan ulang dashboard utama (`welcome.blade.php`) dengan tombol-tombol aksi terpisah secara visual untuk memperjelas setiap alur kerja pengguna (Upload CV Lama, Cek ATS PDF Instan, Cek ATS Master Profile, Edit Master Profile).

---

## 🔗 Matriks Keterhubungan File

| Fitur Tambahan | Model / DB | Controller / Request | View / Prompt |
| :--- | :--- | :--- | :--- |
| **PDF Checker** | `generated_cvs.raw_cv_text` | `TailoringController@storePdfChecker` | `pdf-create.blade.php` |
| **Master Check** | `generated_cvs.is_instant_check` | `TailoringController@createMasterCheck` | `master-create.blade.php` |
| **Output Language** | `generated_cvs.output_language` | `StoreJobDescriptionRequest` | `TailoringEngine` / `ATSScoringEngine` |
| **AI Model Selection**| `generated_cvs.ai_model` | `StoreJobDescriptionRequest` | `GeneratedCv::AI_MODELS` Radio Cards |
