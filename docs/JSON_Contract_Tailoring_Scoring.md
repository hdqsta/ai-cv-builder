# JSON Output Contract — AI Tailoring & ATS Scoring Engine

Main Hub: [[AI_CV_Builder_Graph]] | Related Nodes: [[Tailoring_Engine]], [[ATS_Scoring_System]]

---

Referensi: PRD_AI_CV_Builder.md, Task_Breakdown_AI_CV_Builder.md

Dokumen ini mendefinisikan bentuk input/output pertukaran data antara Laravel dan LLM API untuk 2 call yang terpisah (Call 1: Tailoring, Call 2: Scoring), sesuai keputusan Single Responsibility.

---

## Call 1: Tailoring Engine

### Input (dikirim ke LLM)

```json
{
  "master_profile": {
    "profile": {
      "name": "string",
      "email": "string",
      "phone": "string",
      "location": "string",
      "links": ["string"]
    },
    "experiences": [
      {
        "id": "string",
        "company": "string",
        "position": "string",
        "start_date": "YYYY-MM-DD",
        "end_date": "YYYY-MM-DD | null",
        "bullet_points": [
          { "id": "string", "content": "string" }
        ]
      }
    ],
    "education": [
      {
        "id": "string",
        "institution": "string",
        "degree": "string",
        "start_date": "YYYY-MM-DD",
        "end_date": "YYYY-MM-DD | null",
        "notes": "string | null"
      }
    ],
    "skills": [
      { "id": "string", "name": "string", "category": "string" }
    ],
    "projects": [
      {
        "id": "string",
        "name": "string",
        "bullet_points": [
          { "id": "string", "content": "string" }
        ],
        "tech_stack": ["string"],
        "link": "string | null"
      }
    ],
    "certifications": [
      { "id": "string", "name": "string", "issuer": "string", "year": "string" }
    ]
  },
  "job_description": "string (teks JD mentah)"
}
```

### Output (diterima dari LLM)

```json
{
  "tailored_cv": {
    "summary": "string (ringkasan singkat disesuaikan JD)",
    "experiences": [
      {
        "source_id": "string (mengacu ke id di master_profile.experiences)",
        "company": "string",
        "position": "string",
        "start_date": "YYYY-MM-DD",
        "end_date": "YYYY-MM-DD | null",
        "bullet_points": [
          { "source_bullet_id": "string (mengacu ke id bullet di master_profile.experiences.bullet_points)", "content": "string (hasil reframing kalimat)" }
        ]
      }
    ],
    "education": [
      { "source_id": "string", "institution": "string", "degree": "string", "start_date": "YYYY-MM-DD", "end_date": "YYYY-MM-DD | null" }
    ],
    "skills_highlighted": [
      { "category": "string", "skills": ["string (nama skill, di-matching ke DB via nama di backend, bukan ID)"] }
    ],
    "projects": [
      {
        "source_id": "string",
        "name": "string",
        "bullet_points": [
          { "source_bullet_id": "string", "content": "string" }
        ],
        "tech_stack": ["string"]
      }
    ]
  },
  "meta": {
    "model_used": "string",
    "generated_at": "ISO8601 timestamp"
  }
}
```

**Constraint wajib (masuk system prompt):**
- Setiap `source_id`/`source_bullet_id` di output **harus** merujuk ke `id` yang ada di `master_profile` yang dikirim — LLM dilarang membuat entitas baru (perusahaan, project) yang tidak ada di source
- Khusus `skills_highlighted`: LLM cukup mengembalikan nama skill (bukan ID) — pencocokan ke record skill di database dilakukan backend Laravel via matching nama (`whereIn('name', $skills)`), karena LLM kurang reliable mempertahankan ID untuk item primitif sebanyak ini
- `content` bullet hasil reframing boleh diringkas/disusun ulang kalimatnya, tapi tidak boleh mengklaim pencapaian/tanggung jawab yang tidak tersirat dari bullet asli yang dirujuk `source_bullet_id`-nya
- Jika field source (misal `projects`) tidak relevan dengan JD, boleh dikosongkan dari output (array kosong) — bukan berarti harus selalu diisi semua

---

## Call 2: ATS Scoring Engine

### Input (dikirim ke LLM)

```json
{
  "tailored_cv_data": {
    "summary": "string",
    "experiences": [
      { "position": "string", "company": "string", "bullet_points": ["string"] }
    ],
    "skills": ["string"],
    "projects": [
      { "name": "string", "bullet_points": ["string"] }
    ]
  },
  "job_description": "string"
}
```

*Catatan: `tailored_cv_data` diambil dari `generated_cvs.tailored_cv_final` (versi setelah diedit user), diformat ulang jadi struktur ringkas ini (tanpa ID, karena Call 2 cuma butuh baca isi, bukan melacak sumber) — dikirim sebagai JSON terstruktur, bukan digabung jadi plain text, supaya LLM lebih akurat memisahkan bagian pengalaman/skill/project saat menilai kecocokan dengan JD.*

### Output (diterima dari LLM)

```json
{
  "score": {
    "match_percentage": "number (0-100)",
    "matched_keywords": ["string"],
    "missing_keywords": ["string"]
  },
  "feedback": [
    {
      "type": "string (contoh: 'missing_keyword' | 'weak_bullet' | 'format_suggestion')",
      "message": "string (penjelasan singkat untuk user)"
    }
  ],
  "meta": {
    "model_used": "string",
    "generated_at": "ISO8601 timestamp"
  }
}
```

---

## Alur Data End-to-End (ringkasan teknis)

1. Laravel kirim `master_profile` + `job_description` → **Call 1** → terima `tailored_cv`
2. `tailored_cv` ditampilkan di halaman review (Epic 3) → user boleh edit teks
3. Editan user disimpan sebagai **versi final** di tabel tersendiri, misal `generated_cvs` (lihat catatan skema di bawah)
4. Versi final (bukan `tailored_cv` mentah) di-reformat jadi `tailored_cv_data` (JSON ringkas tanpa ID) → dikirim ke **Call 2** → terima `score` + `feedback`
5. Saat generate PDF (Epic 5): render dari **versi final di `generated_cvs`**, bukan dari output mentah Call 1

## Catatan Skema Tambahan: tabel `generated_cvs`

Untuk menampung hasil per sesi tailoring (menyimpan JSON `tailored_cv`, versi final setelah edit user, dan hasil `score`), disarankan 1 tabel baru terpisah dari Master Profile:

- `id`
- `job_description` (teks JD yang dipakai)
- `tailored_cv_raw` (JSON, hasil mentah Call 1 — untuk audit/debug)
- `tailored_cv_final` (JSON, versi setelah diedit user — ini yang dipakai untuk render PDF)
- `ats_score` (JSON, hasil Call 2)
- `status` (String — `pending`, `tailoring`, `review`, `scoring`, `completed`, `failed` — untuk melacak posisi dokumen di wizard flow Epic 6 dan menampilkan status loading yang akurat ke user)
- `created_at`, `updated_at`

Ini juga jadi fondasi natural untuk fitur **Versioning/histori CV** yang tadi kita defer ke fase berikutnya — tabelnya sudah siap, tinggal diaktifkan fiturnya nanti.

## Konsekuensi ke Desain Master Profile

Karena `source_bullet_id` sekarang wajib ada di output, struktur bullet point di Master Profile (baik `experiences` maupun `projects`) **harus granular sampai level per-baris** dengan ID sendiri — bukan cuma disimpan sebagai satu kolom teks panjang. Ini perlu masuk pertimbangan saat desain ERD nanti: kemungkinan besar butuh tabel anak (`experience_bullets`, `project_bullets`) alih-alih kolom JSON/teks tunggal di tabel `experiences`/`projects`.
