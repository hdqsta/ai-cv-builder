# Task Breakdown & User Stories — AI CV Builder MVP

Main Hub: [[AI_CV_Builder_Graph]] | Related Nodes: [[PRD_AI_CV_Builder]], [[Master_Profile_Database]], [[Tailoring_Engine]]

---

## Epic 1: Master Profile Management

**User Story:** Sebagai user, saya ingin menyimpan data pengalaman/skill/pendidikan saya secara terstruktur, agar bisa dipakai berulang kali untuk tailoring CV ke berbagai lowongan.

### Tasks
- [ ] Desain & migrasi database: tabel `profiles`, `experiences`, `education`, `skills`, `projects`, `certifications`
- [ ] CRUD Master Profile — form input manual (tambah/edit/hapus tiap entry: experience, education, skill, project)
- [ ] Fitur upload CV lama (PDF/DOCX)
- [ ] Integrasi LLM untuk parsing CV lama → auto-fill form (extract experience, education, skill ke struktur data)
- [ ] Sanitasi & normalisasi hasil parsing sebelum ditampilkan ke form review (standarisasi format tanggal ke `YYYY-MM-DD`, pecah string skill jadi array bersih, dsb.)
- [ ] Halaman review hasil parsing — user wajib konfirmasi/edit sebelum data tersimpan sebagai Master Profile
- [ ] Validasi input form (field wajib, format tanggal, dsb.)

---

## Epic 2: Job Description Input

**User Story:** Sebagai user, saya ingin memasukkan teks job description, agar sistem tahu konteks lowongan yang saya tuju.

### Tasks
- [ ] Halaman/form input JD (textarea paste teks)
- [ ] Validasi input (minimal panjang teks, dsb.)
- [ ] Penyimpanan sementara JD per sesi tailoring (belum perlu histori permanen di MVP)

---

## Epic 3: AI Tailoring Engine

**User Story:** Sebagai user, saya ingin CV saya di-generate ulang secara otomatis agar relevan dengan JD tertentu, tanpa AI menambah pengalaman yang tidak saya miliki.

### Tasks
- [ ] Desain prompt tailoring: input (Master Profile + JD) → output (CV terstruktur)
- [ ] Definisikan format output JSON dari LLM (field apa saja yang dikembalikan)
- [ ] Implementasi service/class pemanggil LLM API di Laravel
- [ ] Hard constraint anti-fabrikasi di prompt — cek/validasi bahwa output tidak mengandung entitas di luar Master Profile
- [ ] Error handling (API timeout, response gagal parse, dsb.)
- [ ] Tampilkan hasil tailoring dalam bentuk teks yang bisa direview/diedit user

---

## Epic 4: ATS Scoring

**User Story:** Sebagai user, saya ingin tahu seberapa cocok CV saya dengan JD dari sisi ATS, agar saya bisa menilai apakah perlu revisi manual.

### Tasks
- [ ] Desain prompt scoring: input (CV hasil tailoring + JD) → output (skor match % + daftar keyword yang kurang)
- [ ] Implementasi pemanggilan LLM untuk scoring — **wajib API call terpisah dari Call 1 (Tailoring)**, dijalankan serial setelah tailoring selesai (Single Responsibility: 1 prompt = 1 tujuan, memudahkan debugging saat salah satu call gagal/timeout)
- [ ] Tampilan hasil skor + feedback keyword di UI

---

## Epic 5: Output Generation (Teks + PDF)

**User Story:** Sebagai user, saya ingin mengunduh CV hasil tailoring dalam bentuk PDF siap kirim.

### Tasks
- [ ] Desain 1 template PDF (HTML/CSS) yang ATS-friendly (struktur simpel, tanpa elemen grafis kompleks/tabel bersarang yang sulit di-parse)
- [ ] Integrasi DomPDF di Laravel
- [ ] Simpan editan manual user (dari halaman review teks di Epic 3) ke database/session sebagai versi final, terpisah dari output mentah AI
- [ ] Mapping data **versi final yang sudah diedit user** (bukan JSON mentah dari AI) ke template HTML sebelum render PDF
- [ ] Endpoint/tombol download PDF
- [ ] Testing hasil PDF: pastikan teks bisa di-select/copy (bukan hasil render gambar) — kritis untuk ATS-friendliness

---

## Epic 6: End-to-End Flow & Polish

**User Story:** Sebagai user, saya ingin keseluruhan proses (isi profile → input JD → tailoring → skor → download PDF) berjalan mulus dalam satu alur.

### Tasks
- [ ] Rangkai flow lengkap dari Epic 1–5 jadi satu halaman/wizard yang berurutan
- [ ] Loading state saat proses AI (tailoring & scoring butuh waktu beberapa detik) — pakai opsi ringan: naikkan `max_execution_time` server + loading indicator sinkron (bisa pakai Livewire untuk UX-nya). Queue/Job di-defer ke fase multi-user.
- [ ] Basic error state (kalau LLM API gagal, JD kosong, dsb.)
- [ ] Testing manual end-to-end dengan minimal 2-3 JD berbeda sebagai skenario uji

---

## Urutan Pengerjaan yang Disarankan

1. Epic 1 (Master Profile) — fondasi data, harus jalan dulu
2. Epic 2 (Job Description Input) — simpel, cepat selesai
3. Epic 3 (AI Tailoring) — inti value produk
4. Epic 5 (Output PDF) — supaya ada hasil yang bisa dipegang/dites lebih awal, sebelum nambah ATS Scoring
5. Epic 4 (ATS Scoring) — nambah value di atas flow yang sudah jalan
6. Epic 6 (End-to-End Flow) — rangkai semuanya + polish

*Catatan: Epic 5 sengaja ditaruh sebelum Epic 4 supaya lo cepat punya "hasil jadi" (PDF) yang bisa divalidasi, baru nambah lapisan scoring di atasnya.*
