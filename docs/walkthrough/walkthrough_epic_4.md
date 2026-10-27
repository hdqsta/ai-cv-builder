# Walkthrough — Epic 4: ATS Scoring (Call 2)

Main Hub: [[AI_CV_Builder_Graph]] | Implementation Plan: [[implementation_plan_epic_4]]

Fitur **Epic 4: ATS Scoring (Call 2)** telah berhasil diimplementasikan. Skor kecocokan CV dengan Job Description sekarang dapat dihitung oleh AI Gemini melalui API call terpisah (Call 2), disimpan ke kolom `ats_score`, dan ditampilkan di halaman review yang sama.

---

## Perubahan yang Dilakukan

1. **Config & Env** (`config/services.php`, `.env.example`):
   - Menambahkan `services.gemini.scoring_model` (env `GEMINI_SCORING_MODEL`).
   - Dipisah dari `tailoring_model` agar bisa di-tuning independen. Default keduanya `gemini-3.1-flash-lite`.

2. **Method `toScoringPayload()`** di `app/Services/MasterProfileFormatter.php`:
   - Mengubah `tailored_cv_final` → struktur `tailored_cv_data` sesuai kontrak Call 2 (`summary`, `experiences: [{position, company, bullet_points}]`, `skills: [string]`, `projects: [{name, bullet_points}]`).
   - **Strip** semua `source_id` / `source_bullet_id`.
   - Reuse formatter yang sama (tidak membuat service baru) — formatter sudah berisi kontrak data, tinggal tambah method transformasi untuk stage berbeda.

3. **Service `ATSScoringEngine`** (`app/Services/ATSScoringEngine.php`):
   - `score(GeneratedCv $generatedCv): void` — method utama.
   - Defensive guard: `tailored_cv_final` null → `InvalidArgumentException`.
   - Set `status = CvStatus::Scoring` **sebelum** network call, lalu `save()`.
   - HTTP client reuse pattern dari `TailoringEngine`: `Http::timeout(60)`, retry 3x exponential backoff, handle 429, 5xx, dan connection exception.
   - Mock mode paralel dengan Tailoring — saat API key kosong/mock, hasilkan payload realistis (match 72%, 3 keywords, 3 feedback items).
   - System prompt: instruksi output HANYA JSON valid (tanpa markdown fence, tanpa preamble), bahasa Indonesia untuk `message`, `match_percentage` integer 0-100, `feedback.type` ∈ {`missing_keyword`, `weak_bullet`, `format_suggestion`}.
   - Response handling: trim → strip markdown fence → `json_decode` → strict type validation.
   - **Failure path**: `status = Failed`, simpan debug payload ke `ats_score` (jangan throw 500).
   - **Success path**: tulis ke `ats_score`, `status = Completed`.

4. **Strict Type Validation** (`validateOutput()` di `ATSScoringEngine`):
   - `match_percentage`: integer 0-100.
   - `matched_keywords` & `missing_keywords`: array of string.
   - `feedback`: array of `{ type, message }` dengan `type` whitelist.
   - Pelanggaran dikumpulkan jadi pesan debug.

5. **Controller Updates** (`app/Http/Controllers/TailoringController.php`):
   - Constructor injection `ATSScoringEngine`.
   - Method baru `score(GeneratedCv)`: guard `tailored_cv_final` → panggil engine → redirect `tailoring.review`.
   - Method `review()`: parsing `atsScore` & `atsError`, teruskan ke view.

6. **Named Route** (`routes/web.php`):
   - `POST /tailoring/{generatedCv}/score` → `tailoring.score`.

7. **UI Updates** (`resources/views/tailoring/review.blade.php`):
   - Status banner: tambah pill `Scoring` & `Completed`, plus ringkasan `ATS: NN%`.
   - Tombol **"Cek Skor ATS"** di header — tampil hanya jika `tailored_cv_final` ada & status bukan `scoring`/`tailoring` (pola sama seperti tombol Download PDF).
   - State SCORING: kartu loading dengan auto-reload 4 detik.
   - State FAILED: pesan error + retry button yang **stage-aware**:
     - Gagal di scoring → tombol "Coba Lagi (Scoring)" → `tailoring.score`.
     - Gagal di tailoring → tombol "Coba Lagi (Tailoring)" → `tailoring.generate`.
   - Section baru **"6. ATS Scoring Result"** (di bawah form review, hanya jika skor valid):
     - Hero block: `match_percentage` besar + label kategori (≥75 Kuat, ≥50 Cukup, <50 Lemah) + progress bar.
     - Kartu ringkasan jumlah matched vs missing.
     - Dua kolom keyword: matched (badge emerald) vs missing (badge rose).
     - List feedback per item dengan badge kategori berwarna.
     - Footer: model + timestamp.

---

## Status Verifikasi

- [x] Config key `services.gemini.scoring_model` ditambahkan & env didokumentasikan.
- [x] Method `toScoringPayload()` strip semua `source_id` & `source_bullet_id` (verifikasi manual via dd() di test environment).
- [x] `ATSScoringEngine.score()` mereplikasi retry/timeout pattern dari `TailoringEngine`.
- [x] Mock scoring mode aktif ketika `GEMINI_API_KEY=mock` / kosong.
- [x] Status `Failed` (parse error / type validation) → debug payload tertulis di `ats_score` (kolom `error`, `failed_at`, `stage`).
- [x] Status `Completed` hanya tercapai setelah lulus type validation.
- [x] Controller guard mencegah scoring saat `tailored_cv_final` masih null (flash error message).
- [x] Route `tailoring.score` terdaftar sebagai named route.
- [x] UI menampilkan tombol "Cek Skor ATS" kondisional (hanya saat `tailored_cv_final` ada).
- [x] UI menampilkan section hasil skor (matched/missing keywords + feedback list).
- [x] UI menentukan **stage** Failed dan mengarahkan retry button ke endpoint yang benar.
- [x] Build Vite (`npm run build`) sukses.
- [x] `tailored_cv_final` TIDAK dimodifikasi oleh engine (read-only).
- [x] API Call 2 adalah request HTTP terpisah dari Call 1 (tidak digabung jadi 1 prompt).

---

## Catatan Penggunaan
- **Lokal tanpa API key**: set `GEMINI_API_KEY=mock` di `.env` untuk mencoba flow end-to-end dengan mock payload (match 72%, dll).
- **Production**: tinggal isi `GEMINI_API_KEY` dan (opsional) `GEMINI_SCORING_MODEL` env. Jika tidak diisi, default `gemini-3.1-flash-lite` dipakai.
- **Re-run aman**: user bisa klik "Hitung Ulang Skor ATS" kapan saja (tombol label berubah dari "Cek Skor ATS" → "Hitung Ulang Skor ATS" setelah skor pertama dihasilkan). `tailored_cv_final` tidak pernah dimodifikasi — hanya `ats_score` yang di-overwrite.
