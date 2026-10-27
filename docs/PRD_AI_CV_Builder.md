# Product Requirements Document (PRD)
## AI-Powered CV Builder

Main Hub: [[AI_CV_Builder_Graph]] | Related Nodes: [[ERD_AI_CV_Builder]], [[Task_Breakdown_AI_CV_Builder]]

**Versi:** 1.0 (MVP)  
**Tanggal:** 12 Juli 2026  
**Author:** Hatta Siddiq  

---

## 1. Latar Belakang & Masalah

Proses tailoring CV untuk tiap lowongan pekerjaan biasanya dilakukan manual: menyesuaikan kata kunci, mereframe pengalaman kerja agar relevan dengan job description (JD), dan memastikan CV lolos filter ATS (Applicant Tracking System). Proses ini memakan waktu dan rawan inkonsistensi antar aplikasi lamaran.

Contoh nyata: proses reframing pengalaman magang (dokumen BAST/BAP/BAA/BAI) menjadi CV yang relevan untuk posisi Acceptance Admin / ASP BAST Admin dilakukan manual — proses inilah yang ingin diotomasi.

## 2. Tujuan Produk

- Mempercepat proses pembuatan CV yang di-tailor sesuai JD tertentu
- Meningkatkan kemungkinan lolos filter ATS lewat scoring & feedback keyword
- Menjaga integritas data — sistem melakukan *reframing*, bukan *fabrikasi* pengalaman
- Membangun fondasi arsitektur yang bisa berkembang ke multi-user di fase berikutnya

## 3. Target Pengguna

**MVP:** Personal use (pemilik produk sendiri, untuk kebutuhan job hunting aktif)
**Fase berikutnya:** Multi-user (job seeker umum)

## 4. Scope MVP

### 4.1 In-Scope

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 1 | Master Profile Storage | Data pengalaman kerja, pendidikan, skill, dan project tersimpan dalam struktur data (bukan free text), sebagai sumber utama untuk tailoring |
| 2 | Input via Form Manual | User bisa isi/edit master profile lewat form terstruktur |
| 3 | Upload & Parse CV Lama | User upload CV lama (PDF/DOCX), sistem parse dengan AI untuk mengisi form otomatis — hasil parse **wajib direview/diedit user** sebelum disimpan sebagai master profile |
| 4 | Input Job Description | User paste teks JD (tidak scraping dari URL di MVP ini) |
| 5 | AI Tailoring Engine | LLM mereframe master profile agar selaras keyword & requirement JD, dengan constraint eksplisit: tidak boleh menambah pengalaman/skill yang tidak ada di master profile |
| 6 | ATS Scoring | Setelah tailoring, sistem menghasilkan skor kecocokan (match %) + feedback keyword yang kurang — bersifat **informasi**, bukan auto-loop perbaikan |
| 7 | Output Teks | Hasil CV tailored ditampilkan dalam bentuk konten terstruktur (bisa dibaca/diedit) |
| 8 | Output PDF | Generate PDF CV siap kirim, menggunakan 1 template tetap |

### 4.2 Out of Scope (Fase Berikutnya)

- Autentikasi & manajemen multi-user
- Multiple template/desain PDF
- Versioning / riwayat CV per lamaran
- Job description scraping dari URL
- ATS scoring sebagai feedback loop otomatis (auto re-tailor berdasarkan skor)

## 5. User Flow (MVP)

1. User mengisi Master Profile — manual form dan/atau upload CV lama untuk auto-fill (review manual wajib sebelum submit)
2. User paste teks Job Description pada halaman tailoring
3. Sistem menjalankan AI Tailoring Engine → menghasilkan draft CV yang sudah direframe
4. Sistem menjalankan ATS Scoring → menampilkan skor match + insight keyword yang kurang
5. User review hasil (teks) — bisa edit manual jika perlu
6. User generate PDF dari hasil final
7. User download PDF

## 6. Requirement Non-Fungsional

- **Akurasi & Integritas Data:** AI tailoring dilarang keras menghasilkan klaim pengalaman/skill yang tidak ada di master profile (anti-fabrikasi sebagai hard constraint di prompt/sistem)
- **Arsitektur:** dirancang modular agar auth & multi-tenancy bisa ditambahkan tanpa refactor besar di fase berikutnya
- **Reliability parsing:** parsing CV lama tidak dianggap sebagai source of truth langsung — selalu ada tahap review manual

## 7. Tech Stack (Rencana Awal)

- **Backend:** Laravel
- **AI:** LLM API (untuk tailoring, parsing bantu, dan ATS scoring)
- **PDF Generation:** *(perlu ditentukan di tahap teknis — misal DomPDF/Browsershot)*

## 8. Metrik Keberhasilan (MVP)

- CV berhasil di-generate end-to-end (input JD → output PDF) tanpa error
- Skor ATS yang dihasilkan sistem terasa masuk akal secara kualitatif (validasi manual oleh user)
- Waktu proses tailoring satu CV jauh lebih cepat dibanding proses manual sebelumnya

## 9. Open Questions (untuk tahap teknis berikutnya)

- Library/pendekatan spesifik untuk PDF generation
- Format prompt & struktur output JSON dari LLM untuk tailoring + scoring
- Struktur skema database untuk Master Profile (tabel experience, education, skill, project, dst.)
