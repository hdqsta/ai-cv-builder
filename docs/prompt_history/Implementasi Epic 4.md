Konteks: Laravel project AI CV Builder. Epic 1-3 dan Epic 5 sudah selesai.
tailored_cv_final sudah tersimpan hasil edit user. Sekarang implementasi Epic 4:
ATS Scoring (Call 2, API call terpisah dari Tailoring sesuai Single Responsibility).

LANGKAH AWAL:

- Cek ulang pattern integrasi Gemini di TailoringEngine (Epic 3) — reuse HTTP
  client config, timeout, error handling pattern yang sama, JANGAN bikin cara
  integrasi baru yang beda gaya

1. Config:
    - Tambah config key baru `config('services.gemini.scoring_model')`, env
      `GEMINI_SCORING_MODEL`, default `gemini-3.1-flash-lite` — sengaja dipisah
      dari tailoring_model (meski nilai defaultnya sama) supaya nanti bisa
      di-tuning independen tanpa saling mempengaruhi

2. Service `TailoredCvDataFormatter` (atau method di formatter yang sudah ada):
    - Transform `$generatedCv->tailored_cv_final` (yang punya source_id/
      source_bullet_id) jadi struktur `tailored_cv_data` PERSIS sesuai kontrak
      Call 2 di JSON_Contract_Tailoring_Scoring.md:
      [tempel struktur JSON tailored_cv_data dari kontrak]
    - PENTING: strip semua source_id/source_bullet_id — Call 2 cuma butuh baca
      konten (summary string, experiences jadi position/company/bullet_points
      array of string, skills jadi flat array of string dari
      skills_highlighted, projects jadi name/bullet_points array of string)

3. Service `ATSScoringEngine` (`app/Services/ATSScoringEngine.php`):
    - method `score(GeneratedCv $generatedCv): void`
    - GUARD di awal method: kalau `tailored_cv_final` masih null, throw
      exception/return early dengan pesan jelas (harusnya sudah dicegah di
      controller/route level juga, tapi service tetap defensif)
    - Set status = CvStatus::Scoring, save() SEBELUM call API
    - Build system prompt sesuai kontrak: kirim tailored_cv_data (dari
      formatter) + job_description, instruksikan LLM HANYA output JSON sesuai
      skema output Call 2 (score + feedback + meta), TANPA markdown fence,
      TANPA teks pembuka/penutup
    - Timeout eksplisit sama seperti TailoringEngine

4. Response handling:
    - Strip markdown fence kalau ada, json_decode dengan try/catch
    - Validasi tipe data output: match_percentage harus number 0-100,
      matched_keywords/missing_keywords harus array, feedback harus array of
      object dengan field type & message
    - Kalau parsing gagal ATAU validasi tipe gagal: status = CvStatus::Failed,
      simpan raw response ke kolom ats_score untuk debug (atau kolom log
      terpisah kalau ada), JANGAN throw 500
    - Kalau valid: simpan ke `$generatedCv->ats_score`, set
      status = CvStatus::Completed, save()

5. Controller `TailoringController` (tambahan):
    - method `score(GeneratedCv $generatedCv)`:
        - GUARD: kalau tailored_cv_final null → redirect ke tailoring.review
          dengan pesan error "Simpan hasil final dulu sebelum cek skor ATS"
        - panggil ATSScoringEngine->score($generatedCv)
        - redirect()->route('tailoring.review', $generatedCv)
    - method `review()` — update logic penentuan state gagal:
        - kalau status Failed DAN tailored_cv_raw masih null → tampilkan retry
          button yang submit ke tailoring.generate (gagal di tahap tailoring)
        - kalau status Failed DAN tailored_cv_final SUDAH ada tapi ats_score
          masih null → tampilkan retry button yang submit ke tailoring.score
          (gagal di tahap scoring)
        - kalau status Completed → tampilkan hasil score (match_percentage,
          matched/missing keywords) + list feedback, di bawah/samping hasil
          tailoring yang sudah ada

6. Route baru:
    - POST /tailoring/{generatedCv}/score → nama `tailoring.score`

7. UI review.blade.php (tambahan section, bukan halaman baru):
    - Tombol "Cek Skor ATS" — tampil/aktif HANYA kalau tailored_cv_final sudah
      ada (pola sama seperti tombol Download PDF di Epic 5)
    - Section hasil skor: match_percentage ditampilkan jelas (misal progress
      bar atau angka besar), matched_keywords vs missing_keywords dibedakan
      visual (misal warna beda), feedback list ditampilkan sebagai list poin

Constraint penting:

- Semua route WAJIB named route
- JANGAN ubah tailored_cv_final di proses ini — Call 2 read-only terhadap
  data itu, cuma nulis ke kolom ats_score
- API call Call 2 WAJIB request HTTP terpisah dari Call 1 — jangan digabung
  jadi 1 prompt/1 call meskipun secara teknis bisa

Output yang diharapkan:

- User yang sudah simpan tailored_cv_final bisa klik "Cek Skor ATS" → sistem
  panggil Gemini (call kedua, terpisah), hasil skor + feedback tersimpan di
  ats_score, status jadi Completed, dan tampil di halaman review yang sama
- Kalau gagal → status Failed, retry button mengarah ke tahap yang benar
  (tailoring atau scoring) tergantung di mana kegagalannya
