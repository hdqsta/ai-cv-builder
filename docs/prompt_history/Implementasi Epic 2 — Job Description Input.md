Konteks: Laravel project AI CV Builder (MVP personal-only). Epic 1 (Master Profile
Management) sudah selesai dan fungsional. Sekarang lanjut ke Epic 2: Job Description Input.

LANGKAH AWAL (wajib sebelum coding):

- Cek struktur `resources/views` yang sudah ada dari Epic 1 (layout Blade apa yang
  dipakai — `<x-app-layout>`, `@extends('layouts.app')`, atau lainnya) dan ikuti
  pattern yang sama persis untuk halaman baru di Epic 2 ini. Jangan bikin layout
  baru yang berdiri sendiri.
- Cek juga pattern penulisan form & error display yang dipakai di form Epic 1
  (misal Master Profile form) untuk konsistensi styling.

1. Migration untuk tabel `generated_cvs` sesuai skema ERD (kalau belum ada):
    - id (bigint, PK)
    - job_description (text)
    - tailored_cv_raw (json, nullable)
    - tailored_cv_final (json, nullable)
    - ats_score (json, nullable)
    - status (string, default 'pending')
    - timestamps

2. Buat PHP Enum `CvStatus` (backed enum, string) di `app/Enums/CvStatus.php`
   dengan case: Pending = 'pending', Tailoring = 'tailoring', Review = 'review',
   Scoring = 'scoring', Completed = 'completed', Failed = 'failed'.

3. Model `GeneratedCv`:
    - Definisikan `$fillable` eksplisit: ['job_description', 'tailored_cv_raw',
      'tailored_cv_final', 'status'] — JANGAN pakai `$guarded = []`
    - $casts: tailored_cv_raw, tailored_cv_final, ats_score sebagai 'array';
      status sebagai CvStatus::class (enum cast)

4. Halaman form input JD:
    - Route GET, named `tailoring.create` → path bebas (misal /tailoring/create)
      tapi WAJIB pakai named route, jangan hardcode URL di Blade/redirect
    - Form action pakai `route('tailoring.store')`, bukan URL string manual
    - 1 textarea untuk paste teks JD, styling ikut pattern Epic 1
    - Character counter sederhana (opsional)

5. Form Request `StoreJobDescriptionRequest`:
    - method `authorize()` WAJIB return `true` (MVP belum ada auth — kalau
      dibiarkan default `false`, submit form akan gagal dengan 403)
    - rules: job_description → required, string, min:100
    - Tampilkan error validasi konsisten dengan pattern Epic 1

6. Controller `TailoringController`:
    - method `create()` → return view form, route name `tailoring.create`
    - method `store(StoreJobDescriptionRequest $request)`:
        - `GeneratedCv::create([...$request->validated(), 'status' => CvStatus::Pending])`
        - redirect ke named route placeholder `tailoring.review` dengan parameter
          `$generatedCv` (route model binding), misal
          `redirect()->route('tailoring.review', $generatedCv)`
    - Route GET placeholder `tailoring.review` (path misal /tailoring/{generatedCv}/review)
      — cukup return view kosong/placeholder dulu, isinya scope Epic 3
    - JANGAN implementasi pemanggilan LLM di controller ini

Constraint penting:

- Jangan bikin session-based storage untuk JD — langsung persist ke `generated_cvs`
- Semua route WAJIB named route (tailoring.create, tailoring.store, tailoring.review)
  — jangan hardcode URL string di mana pun (Blade form action, redirect, link)
- Belum perlu halaman riwayat JD atau auth/user_id — sesuai scope MVP

Output yang diharapkan:

- User bisa buka halaman via route('tailoring.create'), paste JD, submit tanpa
  error 403/MassAssignmentException, dan JD tersimpan sebagai row baru dengan
  status Pending (enum)
- Siap dilanjutkan ke Epic 3 (AI Tailoring Engine)
