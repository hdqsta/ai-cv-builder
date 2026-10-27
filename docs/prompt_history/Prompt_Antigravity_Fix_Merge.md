# Prompt Antigravity — Perbaikan: Save Hasil Parsing dari Replace ke Merge/Append

## Masalah

Di `CvUploadController::save()` saat ini, method tersebut mengosongkan (truncate/delete) seluruh data Master Profile yang sudah ada (`experiences`, `experience_bullets`, `education`, `skills`, `projects`, `project_bullets`, `certifications`) sebelum menyimpan hasil parsing baru. Ini berbahaya: data yang sudah diinput manual sebelumnya (lewat CRUD di Epic 1) akan hilang tanpa peringatan setiap kali user upload & simpan CV baru.

Perilaku yang benar: hasil parsing CV harus **ditambahkan (append/merge)** ke Master Profile yang sudah ada, bukan menggantikannya. Master Profile dirancang sebagai kumpulan data yang terus bertambah dari waktu ke waktu (kombinasi input manual dan hasil parsing beberapa CV), bukan snapshot tunggal yang di-replace tiap kali.

## Perbaikan yang Diminta

Revisi `CvUploadController::save()` dengan logika berikut:

1. **`profiles`** — ini tabel single-record (MVP personal-only). Untuk field ini, tetap **update** record yang ada (`Profile::firstOrNew()->fill(...)->save()`), karena secara logis cuma ada 1 profil diri. Tapi tampilkan hasil parsing di halaman review dengan jelas menandai bahwa field ini **akan menimpa** data profil saat ini (beri label/warning kecil di UI, bukan diam-diam menimpa).

2. **`experiences`, `education`, `projects`, `certifications`** — **jangan dihapus dulu**. Insert data hasil parsing sebagai **entri baru**, ditambahkan ke entri yang sudah ada. Untuk `experiences` dan `projects`, sertakan juga insert `experience_bullets`/`project_bullets` terkait seperti biasa.

3. **`skills`** — gunakan `Skill::firstOrCreate(['name' => $skillName], ['category' => $category])` per skill, supaya skill yang namanya sudah ada di database tidak duplikat (memanfaatkan unique constraint pada `name` yang sudah ada), tapi skill baru dari hasil parsing tetap ditambahkan.

4. **Tambahkan konfirmasi eksplisit di halaman review (`review.blade.php`)** sebelum data disimpan: tampilkan ringkasan singkat seperti *"Data ini akan ditambahkan ke Master Profile yang sudah ada. Data Personal Details akan diperbarui/ditimpa dengan data di bawah ini."* — supaya user sadar konsekuensinya sebelum klik simpan, bukan cuma tombol submit polos seperti form CRUD biasa.

## Batasan

- Jangan menambahkan mekanisme deteksi duplikat otomatis untuk `experiences`/`projects` (misal cek kesamaan company+posisi) — itu di luar scope perbaikan ini. Kalau ada entri duplikat dari upload CV yang sama dua kali, user cukup hapus manual lewat CRUD Epic 1 yang sudah ada.
- Jangan ubah logika sanitasi tanggal/skill category yang sudah berjalan (bagian itu sudah benar dan tidak perlu disentuh).
- Setelah selesai, tunjukkan ringkasan perubahan kode dan konfirmasi bahwa logika lama (truncate) sudah sepenuhnya dihapus, bukan hanya dinonaktifkan.

## Verifikasi

Setelah revisi, uji dengan skenario ini di browser subagent:
1. Pastikan ada minimal 1 experience/skill yang sudah tersimpan sebelumnya (dari data manual/testing sebelumnya).
2. Upload CV, review, lalu simpan.
3. Verifikasi bahwa entri lama **masih ada**, dan entri baru dari hasil parsing **ditambahkan** di sampingnya — bukan menggantikan.
