# Implementation Plan — Epic 4: ATS Scoring (Call 2)

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Task_Breakdown_AI_CV_Builder]], [[JSON_Contract_Tailoring_Scoring]], [[ATS_Scoring_System]]

## Deskripsi Singkat
Epic 4 menambahkan **ATS Scoring (Call 2)** — API call kedua yang terpisah dari Tailoring Engine, sesuai prinsip Single Responsibility. Epic ini menerima versi final CV yang sudah disunting user (`tailored_cv_final`), memanggil Gemini dengan prompt berbeda, lalu menyimpan hasil `score` (match_percentage, matched/missing keywords) dan `feedback` ke kolom `ats_score`. Status record berpindah ke `Completed` hanya setelah scoring berhasil.

---

## Rincian Perubahan & Komponen Baru

### 1. Config Key Baru `services.gemini.scoring_model` (`config/services.php` + `.env.example`)
- Menambahkan `scoring_model` yang terpisah dari `tailoring_model` agar dapat di-tuning independen.
- Default sama-sama `gemini-3.1-flash-lite` (nilai fallback dari `GEMINI_MODEL`).
- Env baru: `GEMINI_SCORING_MODEL`.

### 2. Method `toScoringPayload()` di `MasterProfileFormatter` (`app/Services/MasterProfileFormatter.php`)
- Mentransform `tailored_cv_final['tailored_cv']` → struktur `tailored_cv_data` sesuai kontrak Call 2 (`docs/JSON_Contract_Tailoring_Scoring.md`).
- **Strip** semua `source_id` / `source_bullet_id` (Call 2 hanya membaca konten, tidak melacak asal).
- Output:
  - `summary`: `string`
  - `experiences`: `[{ position, company, bullet_points: [string] }]`
  - `skills`: flat `array of string` (dari flatten `skills_highlighted`).
  - `projects`: `[{ name, bullet_points: [string] }]` (tanpa `tech_stack`, karena kontrak tidak meminta).
- Reuse formatter yang sama (bukan membuat service baru) — formatter sudah berisi kontrak data, tinggal tambah method transformasi untuk stage yang berbeda.

### 3. Service `ATSScoringEngine` (`app/Services/ATSScoringEngine.php`)
- Constructor injection: `MasterProfileFormatter`.
- Method publik `score(GeneratedCv $generatedCv): void`.
- **Defensive guard:** `tailored_cv_final` null → throw `InvalidArgumentException` dengan pesan jelas.
- Set `status = CvStatus::Scoring`, `save()` **sebelum** network call.
- HTTP client: reuse pattern `TailoringEngine` (`Http::timeout(60)`, `responseMimeType: application/json`, retry 3x dengan exponential backoff, handle 429, handle 5xx, handle connection exception).
- Konfigurasi model: `config('services.gemini.scoring_model')`.
- Mock mode: jika API key kosong/`mock` → panggil `handleMockScoring()` yang menghasilkan payload realistis (72%, 3 keyword matched, 3 missing, 3 feedback item) — paralel dengan `handleMockGeneration` di `TailoringEngine`.
- System prompt: instruksikan LLM output HANYA JSON valid (tanpa ` ```json `, tanpa teks pembuka/penutup), `match_percentage` integer 0-100, `feedback.type` ∈ {`missing_keyword`, `weak_bullet`, `format_suggestion`}, bahasa `message` = Bahasa Indonesia.
- **Response handling**: trim → strip markdown fence → `json_decode` → strict type validation (lihat poin 4).
- **Failure path** (parse gagal ATAU validasi tipe gagal): `status = Failed`, tulis debug payload ke `ats_score` (jangan throw 500).
- **Success path**: tulis hasil ke `ats_score` (preserve/add `meta.model_used` & `meta.generated_at`), set `status = Completed`, `save()`.

### 4. Strict Type Validation (method `validateOutput()` di `ATSScoringEngine`)
Memastikan output AI benar-benar mengikuti kontrak:
- `score.match_percentage`: integer (atau float tanpa desimal), range 0–100.
- `score.matched_keywords`: array of string.
- `score.missing_keywords`: array of string.
- `feedback`: array of `{ type: string, message: string }` dengan `type` whitelist.
- `meta` (opsional): jika ada, harus object/array.
- Setiap pelanggaran dikumpulkan di array `errors` (dipakai untuk pesan debug di `ats_score`).

### 5. Controller `TailoringController` Updates (`app/Http/Controllers/TailoringController.php`)
- Constructor injection: `ATSScoringEngine` (selain `TailoringEngine` existing).
- Method baru `score(GeneratedCv $generatedCv)`:
  - Guard: `tailored_cv_final` null → redirect `tailoring.review` dengan flash error `"Simpan hasil final dulu sebelum cek skor ATS."`.
  - Panggil `$this->atsScoringEngine->score($generatedCv)`.
  - Redirect balik ke `tailoring.review` dengan flash success.
- Method `review()`:
  - Tambahkan parsing `$atsScore` dan `$atsError` (subset `ats_score` yang berisi key `error`).
  - Pass keduanya ke view.
- Method `review()` (rendering): view baru menentukan **stage** Failed:
  - `$isScoringStageFailed` = status Failed **DAN** final sudah ada **DAN** belum ada score valid.
  - `$isTailoringStageFailed` = status Failed **DAN** `tailored_cv_raw.tailed_cv` masih kosong.

### 6. Named Route Baru (`routes/web.php`)
- `POST /tailoring/{generatedCv}/score` → `tailoring.score`

### 7. UI Updates (`resources/views/tailoring/review.blade.php`)
- **Status banner**: tambah pill `Scoring` (animasi pulse) & `Completed` (centang hijau), plus pill ringkas `ATS: NN%` saat skor sudah ada.
- **Tombol "Cek Skor ATS"** di header action bar — tampil hanya jika `tailored_cv_final` sudah ada dan status bukan `scoring`/`tailoring` (pola sama seperti tombol Download PDF di Epic 5). Label otomatis berubah ke "Hitung Ulang Skor ATS" jika skor sudah ada.
- **State SCORING**: kartu loading dengan auto-reload halaman setiap 4 detik (sama pola dengan Tailoring).
- **State FAILED** (refined): pesan error & retry button berbeda tergantung stage:
  - Tahap scoring → tombol "Coba Lagi (Scoring)" → submit ke `tailoring.score`.
  - Tahap tailoring → tombol "Coba Lagi (Tailoring)" → submit ke `tailoring.generate`.
- **Section baru "6. ATS Scoring Result"** (di bawah form review, hanya jika skor valid):
  - Hero block: angka `match_percentage` besar dengan gradasi warna (≥75 emerald, ≥50 amber, <50 rose) + label kategori.
  - Progress bar dengan lebar sesuai persentase.
  - Dua kartu ringkasan: jumlah matched vs missing.
  - Dua kolom keyword: matched (badge emerald) vs missing (badge rose).
  - List feedback per item dengan badge kategori (`missing_keyword` / `weak_bullet` / `format_suggestion`) berwarna berbeda.
  - Footer: model yang dipakai + timestamp generate.

---

## Verifikasi Test Plan
1. `composer test` — semua test existing (`TailoringTest`, `FinalizeAndPdfTest`, `ExampleTest`) tetap pass.
2. Buka halaman review pada session dengan `tailored_cv_final` sudah ada → tombol "Cek Skor ATS" terlihat.
3. Klik tombol → status berpindah ke `scoring` (animasi pulse) → setelah selesai kembali ke `review` dengan section ATS Score muncul.
4. Cek `database/generate_cvs.ats_score` (via SQLite/SQL client) → berisi JSON lengkap `{ score, feedback, meta }`.
5. Set `GEMINI_API_KEY=mock` di `.env` → panggil scoring → dapat mock payload (72%, 3 keyword).
6. Simulasi Gemini error → status `failed`, `ats_score` berisi `{ error, failed_at, stage: "scoring" }`; retry button mengarah ke `tailoring.score` (bukan `tailoring.generate`).
7. Buka halaman review dengan `status=failed` dan `tailored_cv_raw.tailed_cv` kosong → tombol retry ke `tailoring.generate` (gagal tahap tailoring).

---

## Catatan Arsitektur
- **Single Responsibility**: API call scoring benar-benar terpisah dari call tailoring. Prompt, validation, retry logic, dan mock handler semua self-contained di `ATSScoringEngine`.
- **Defensive layers**: guard di controller (UX-friendliness, flash message) + guard di service (kalau ada bug / cron job panggil langsung).
- **Read-only terhadap `tailored_cv_final`**: engine tidak pernah memodifikasi kolom tersebut; hanya menulis ke `ats_score`. Aman untuk re-run (idempotent).
- **Kontrak JSON**: lihat `docs/JSON_Contract_Tailoring_Scoring.md` section "Call 2" — ini source of truth untuk input/output shape.
