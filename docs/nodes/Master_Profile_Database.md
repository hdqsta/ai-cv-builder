---
title: Master Profile Database Node
tags:
  - node
  - database
  - epic-1
  - backend
---

# 🗄️ Master Profile Database Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[ERD_AI_CV_Builder]], [[PRD_AI_CV_Builder]]

## Overview
Master Profile adalah *single source of truth* data profesional pengguna. Seluruh data disimpan secara terstruktur di database PostgreSQL/MySQL/SQLite Laravel, bukan berupa free text.

## Related Entities & Models
- `Profile`: [[ERD_AI_CV_Builder#tabel-profiles]] — Menyimpan nama, email, phone, location, summary, dan links.
- `Experience` & `ExperienceBullet`: [[ERD_AI_CV_Builder#tabel-experiences]] — Menyimpan riwayat pekerjaan dan poin pencapaian.
- `Project` & `ProjectBullet`: [[ERD_AI_CV_Builder#tabel-projects]] — Menyimpan portofolio proyek dan tech stack.
- `Education`: [[ERD_AI_CV_Builder#tabel-education]] — Menyimpan riwayat akademis.
- `Skill`: [[ERD_AI_CV_Builder#tabel-skills]] — Inventaris keahlian & tools.
- `Certification`: [[ERD_AI_CV_Builder#tabel-certifications]] — Lisensi & sertifikasi profesional.

## Connections
- Extracted by: [[Gemini_AI_Parser]]
- Fed into: [[Tailoring_Engine]]
- Progress Log: [[walkthrough_epic_1]]
