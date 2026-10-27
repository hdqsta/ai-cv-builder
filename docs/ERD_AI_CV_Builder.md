# Entity Relationship Diagram (ERD) — AI CV Builder

Main Hub: [[AI_CV_Builder_Graph]] | Related Nodes: [[PRD_AI_CV_Builder]], [[Master_Profile_Database]]

---

Referensi: PRD_AI_CV_Builder.md, Task_Breakdown_AI_CV_Builder.md, JSON_Contract_Tailoring_Scoring.md

Skema ini disusun MVP personal-only (belum ada `user_id`/multi-tenancy), tapi setiap tabel disiapkan supaya penambahan `user_id` di fase berikutnya tinggal 1 kolom + foreign key, tanpa restrukturisasi tabel.

---

## 1. `profiles`

Data diri dasar. Untuk MVP personal, tabel ini realistis cuma berisi 1 baris.

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar | |
| `email` | varchar | |
| `phone` | varchar | |
| `location` | varchar | |
| `summary` | text, nullable | ringkasan/bio default sebelum di-tailor |
| `links` | json, nullable | array string (LinkedIn, portfolio, dst.) |
| `created_at`, `updated_at` | timestamp | |

---

## 2. `experiences`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `company` | varchar | |
| `position` | varchar | |
| `start_date` | date | |
| `end_date` | date, nullable | null = masih berjalan |
| `created_at`, `updated_at` | timestamp | |

**Relasi:** `experiences` 1 → N `experience_bullets`

---

## 3. `experience_bullets`

Tabel anak — pecahan poin tanggung jawab/pencapaian per pengalaman kerja. Granular karena jadi unit terkecil yang di-reframe AI (lihat JSON Contract: `source_bullet_id`).

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | dipakai sebagai `source_bullet_id` di JSON Contract |
| `experience_id` | bigint, FK → `experiences.id` | |
| `content` | text | 1 kalimat/poin |
| `order` | integer | urutan tampil |
| `created_at`, `updated_at` | timestamp | |

---

## 4. `education`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `institution` | varchar | |
| `degree` | varchar | |
| `start_date` | date | |
| `end_date` | date, nullable | |
| `notes` | text, nullable | GPA, prestasi, dsb. |
| `created_at`, `updated_at` | timestamp | |

---

## 5. `skills`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar, unique | dipakai sebagai kunci matching dari output AI (`whereIn('name', ...)`) |
| `category` | varchar | contoh: Backend, Design, Tools |
| `created_at`, `updated_at` | timestamp | |

---

## 6. `projects`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar | |
| `tech_stack` | json | array string |
| `link` | varchar, nullable | |
| `created_at`, `updated_at` | timestamp | |

**Relasi:** `projects` 1 → N `project_bullets`

---

## 7. `project_bullets`

Sama seperti `experience_bullets`, tapi untuk project.

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | dipakai sebagai `source_bullet_id` |
| `project_id` | bigint, FK → `projects.id` | |
| `content` | text | |
| `order` | integer | |
| `created_at`, `updated_at` | timestamp | |

---

## 8. `certifications`

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `name` | varchar | |
| `issuer` | varchar | |
| `year` | varchar | |
| `created_at`, `updated_at` | timestamp | |

---

## 9. `generated_cvs`

Menyimpan hasil tiap sesi tailoring — sudah dibahas di JSON Contract, disertakan ulang di sini sebagai bagian ERD resmi.

| Field | Tipe | Keterangan |
|---|---|---|
| `id` | bigint, PK | |
| `job_description` | text | |
| `tailored_cv_raw` | json | hasil mentah Call 1, untuk audit/debug |
| `tailored_cv_final` | json | versi setelah diedit user — dipakai untuk render PDF & input Call 2 |
| `ats_score` | json, nullable | hasil Call 2 |
| `status` | enum/varchar | `pending`, `tailoring`, `review`, `scoring`, `completed`, `failed` |
| `created_at`, `updated_at` | timestamp | |

Tabel ini tidak berelasi langsung (FK) ke tabel Master Profile lain, karena isinya adalah **snapshot hasil AI** (yang menyimpan referensi via `source_id`/`source_bullet_id` di dalam JSON-nya sendiri), bukan data relasional yang perlu di-join.

---

## Diagram Relasi (ringkas)

```
profiles (standalone, 1 baris)

experiences (1) ──< experience_bullets (N)

projects (1) ──< project_bullets (N)

education (standalone)

skills (standalone)

certifications (standalone)

generated_cvs (standalone — snapshot, referensi ke tabel lain via ID di dalam JSON, bukan FK)
```

---

## Catatan Desain

1. **Kenapa `experience_bullets` & `project_bullets` dipisah jadi tabel sendiri, bukan kolom JSON di tabel induk?**
   Karena tiap baris butuh `id` sendiri yang stabil untuk dirujuk sebagai `source_bullet_id` di JSON Contract. Kalau disimpan sebagai kolom JSON di `experiences`, tetap bisa dikasih ID manual di dalam struktur JSON-nya, tapi lebih rawan inkonsistensi (index array bisa berubah kalau ada reorder) dan lebih sulit di-query langsung (misal butuh cari 1 bullet spesifik). Tabel relasional lebih aman untuk kebutuhan ini.

2. **Kenapa `skills` tidak punya tabel bullet/anak?**
   Karena skill sifatnya atomik (1 nama = 1 unit), tidak punya "sub-poin" yang perlu direframe kalimatnya seperti bullet pengalaman.

3. **Kesiapan multi-user (fase berikutnya):**
   Tambahkan kolom `user_id` (FK → `users.id`) di semua tabel kecuali `experience_bullets` dan `project_bullets` (yang cukup ikut lewat relasi parent-nya). Tidak ada restrukturisasi besar yang dibutuhkan dari skema MVP ini ke versi multi-user.
