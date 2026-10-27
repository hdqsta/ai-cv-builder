Konteks: Laravel project AI CV Builder (MVP personal-only). Epic 1 (Master Profile)
dan Epic 2 (Job Description Input, tabel generated_cvs + CvStatus enum) sudah selesai.
Sekarang implementasi Epic 3: AI Tailoring Engine.

LANGKAH AWAL (wajib sebelum coding):

- Cek bagaimana integrasi Gemini API sudah diimplementasikan di fitur "Upload &
  Parse CV Lama" (Epic 1) — reuse pattern yang sama untuk HTTP client, config
  API key, dan cara handling response (jangan bikin cara integrasi Gemini yang
  beda/terpisah, biar konsisten 1 pola di seluruh project).
- Cek struktur model Epic 1 (Profile, Experience + ExperienceBullet, Education,
  Skill, Project + ProjectBullet, Certification) untuk tahu relasi & field yang
  tersedia.

1. Config:
    - Pastikan model Gemini untuk tailoring dibaca dari config (misal
      `config('services.gemini.tailoring_model')`), default value
      `gemini-3.1-flash-lite`, di-set lewat `.env` (`GEMINI_TAILORING_MODEL`) —
      JANGAN hardcode nama model di dalam service class.

2. Service `MasterProfileFormatter` (atau nama serupa, taruh di `app/Services`):
    - 1 method, misal `toTailoringPayload(): array`, yang query semua data
      Master Profile (profile, experiences+bullets, education, skills, projects+bullets,
      certifications) dan susun jadi struktur PERSIS sesuai kontrak JSON berikut
      (field `master_profile` di JSON_Contract_Tailoring_Scoring.md):
      [tempel struktur JSON master_profile dari JSON_Contract_Tailoring_Scoring.md
      di sini sebagai referensi persis]
    - PENTING: `id` di setiap entity (experience, bullet, education, skill, project,
      certification) harus id asli dari database (bukan index array), karena ini
      yang dipakai LLM sebagai source_id/source_bullet_id di output

3. Service `TailoringEngine` (`app/Services/TailoringEngine.php`):
    - method `generate(GeneratedCv $generatedCv): void`
    - Set `$generatedCv->status = CvStatus::Tailoring` dan save SEBELUM memanggil
      API (biar kalau request timeout/crash di tengah jalan, status kebaca
      'tailoring' bukan nyangkut di 'pending')
    - Build system prompt yang secara eksplisit include:
      a. Instruksi bahwa LLM HANYA boleh reframe/reformat, DILARANG menambah
      pengalaman/skill/pencapaian yang tidak tersirat dari data master profile
      b. Instruksi bahwa setiap source_id dan source_bullet_id di output WAJIB
      merujuk ke id yang ada di payload master_profile yang dikirim — dilarang
      membuat id baru
      c. Instruksi HANYA output JSON valid sesuai skema output di JSON Contract
      (field tailored_cv + meta) — TANPA markdown code fence (```json),
      tanpa teks pembuka/penutup
    - Kirim payload: master_profile (dari MasterProfileFormatter) + job_description
      (dari $generatedCv->job_description)
    - Set timeout HTTP client eksplisit (misal 60 detik) — jangan pakai default
      Laravel HTTP client yang bisa terlalu pendek untuk response LLM yang panjang

4. Response handling (di dalam TailoringEngine atau service terpisah,
   misal `TailoringResponseValidator`):
    - Strip markdown code fence kalau ada (Gemini kadang tetap bungkus ```json
      meskipun sudah diinstruksikan tidak boleh) sebelum json_decode
    - Kalau json_decode gagal (response bukan JSON valid): set
      $generatedCv->status = CvStatus::Failed, simpan raw response mentah ke
      kolom tailored_cv_raw untuk debug, JANGAN throw exception yang bikin 500
      — tangkap dan tampilkan pesan error yang jelas ke user
    - VALIDASI HARD CONSTRAINT: kumpulkan semua id valid dari master_profile
      (semua experience id, experience_bullet id, project id, project_bullet id)
      jadi 1 set/array. Cek setiap source_id dan source_bullet_id di output LLM
      ada di dalam set itu. Kalau ADA yang tidak match (berarti LLM
      kemungkinan fabrikasi/halusinasi id):
        - set status = CvStatus::Failed
        - simpan tailored_cv_raw tetap (untuk audit/debug)
        - JANGAN diteruskan ke halaman review sebagai hasil valid
    - Kalau semua validasi lolos: simpan hasil ke `tailored_cv_raw`,
      set status = CvStatus::Review, save

5. Controller `TailoringController` (tambahan method ke controller yang sudah ada
   dari Epic 2):
    - method `generate(GeneratedCv $generatedCv)`:
        - panggil TailoringEngine->generate($generatedCv)
        - redirect()->route('tailoring.review', $generatedCv)
          (pakai named route yang sudah ada)
    - method `review(GeneratedCv $generatedCv)` (update dari placeholder Epic 2):
        - kalau status masih Pending → tampilkan halaman dengan tombol "Generate
          Tailored CV" yang submit ke POST route baru
        - kalau status Failed → tampilkan pesan error + tombol "Coba Lagi"
          (submit ulang ke route generate yang sama)
        - kalau status Review/Completed → tampilkan tailored_cv_raw dalam bentuk
          yang bisa dibaca (boleh textarea/form editable per section — TAPI belum
          perlu logic SAVE editan di sini, itu scope Epic 5 sesuai Task Breakdown.
          Cukup tampilkan, form submit-nya boleh disabled/placeholder dulu)

6. Routing (named routes, konsisten dengan pola Epic 2):
    - POST /tailoring/{generatedCv}/generate → nama `tailoring.generate`
    - Route GET tailoring.review sudah ada dari Epic 2, tinggal update isinya

Constraint penting:

- JANGAN implementasi ATS Scoring (Call 2) di sini — itu scope Epic 4,
  API call harus benar-benar terpisah sesuai keputusan Single Responsibility
- JANGAN implementasi save editan user ke tailored_cv_final — itu scope Epic 5
- Semua route WAJIB named route, jangan hardcode URL
- $fillable di model GeneratedCv sudah benar dari Epic 2, pastikan tidak perlu
  diubah (tailored_cv_raw sudah termasuk)

Output yang diharapkan:

- User bisa klik "Generate" di halaman review, sistem manggil Gemini, hasil
  tervalidasi (tidak ada id fabrikasi) tersimpan sebagai tailored_cv_raw,
  status berubah jadi Review, dan hasil tampil di halaman yang sama
- Kalau Gemini gagal/response invalid/id fabrikasi terdeteksi → status Failed,
  user lihat pesan error dan bisa coba lagi
