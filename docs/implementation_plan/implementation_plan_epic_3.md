# Implementation Plan — Epic 3: AI Tailoring Engine

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Task_Breakdown_AI_CV_Builder]], [[Tailoring_Engine]], [[JSON_Contract_Tailoring_Scoring]]

## Deskripsi Singkat
Epic 3 membangun **AI Tailoring Engine** yang bertanggung jawab untuk mengambil seluruh data Master Profile pengguna (`Profile`, `Experience`, `ExperienceBullet`, `Education`, `Skill`, `Project`, `ProjectBullet`, `Certification`) dan Job Description sasaran, lalu menggunakan LLM API (Google Gemini `gemini-3.1-flash-lite`) untuk melakukan *reframing* kalimat pencapaian tanpa menambah/mengarang data di luar Master Profile (*Anti-Fabrication Constraint*).

---

## Rincian Perubahan & Komponen Baru

### 1. Konfigurasi (`config/services.php`)
- Menambahkan entri `tailoring_model` pada `services.gemini`:
  `'tailoring_model' => env('GEMINI_TAILORING_MODEL', env('GEMINI_MODEL', 'gemini-3.1-flash-lite'))`

### 2. Service `MasterProfileFormatter` (`app/Services/MasterProfileFormatter.php`)
- **Fungsi:** Mengambil data lengkap dari database Master Profile dan menyusun payload JSON persis sesuai kontrak `Call 1: Tailoring Engine` pada [[JSON_Contract_Tailoring_Scoring#1-json-contract-ai-tailoring-engine-call-1]].
- **Entitas & ID:** Mengonversi seluruh `id` database (Experience, ExperienceBullet, Project, ProjectBullet, Education, Skill, Certification) menjadi `string` agar LLM dapat menyalin dan mereferensikannya sebagai `source_id` / `source_bullet_id`.

### 3. Service `TailoringEngine` (`app/Services/TailoringEngine.php`)
- **Fungsi Utama (`generate`):**
  1. Mengubah status `$generatedCv->status = CvStatus::Tailoring` dan menyimpan ke DB sebelum memanggil API.
  2. Menyusun system prompt dengan constraint ketat (Anti-Fabrication & Source ID Mandate).
  3. Mengirimkan HTTP request dengan timeout eksplisit 60 detik (`Http::timeout(60)`) dan retry exponential backoff pada status 429.
  4. Membersihkan markdown code block (` ```json `) dari respon LLM.
  5. **Hard Constraint ID Validation:** Mengumpulkan seluruh ID valid dari Master Profile. Memverifikasi setiap `source_id` dan `source_bullet_id` pada output AI. Jika terdapat ID yang tidak ada di Master Profile (fabrikasi), status diubah menjadi `CvStatus::Failed` dan log disimpan ke `tailored_cv_raw`.
  6. Jika validasi lolos, menyimpan respon ke `tailored_cv_raw` dan mengubah status menjadi `CvStatus::Review`.

### 4. Controller `TailoringController` (`app/Http/Controllers/TailoringController.php`)
- **Method `generate()`:** Memanggil `TailoringEngine->generate($generatedCv)` dan melakukan redirect ke `route('tailoring.review', $generatedCv)`.
- **Method `review()`:** Menampilkan halaman studio review berdasarkan status `GeneratedCv` (`Pending`, `Tailoring`, `Failed`, atau `Review`/`Completed`).

### 5. Routing Named Routes (`routes/web.php`)
- `POST /tailoring/{generatedCv}/generate` → `tailoring.generate`
- `GET /tailoring/{generatedCv}/review` → `tailoring.review`

---

## Verifikasi Test Plan
1. Buka halaman `route('tailoring.review', $generatedCv)` pada posisi status `Pending`.
2. Klik tombol "Jalankan AI Tailoring Engine".
3. Dalam mode mock atau dengan API key Gemini, verifikasi status berubah menjadi `Tailoring` kemudian `Review`.
4. Pastikan hasil reframing tampil lengkap di UI beserta tag `source_id` dan `source_bullet_id`.
5. Verifikasi bahwa tidak ada ID buatan/halusinasi di luar Master Profile.
