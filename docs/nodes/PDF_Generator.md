---
title: PDF Generator Node
tags:
  - node
  - pdf
  - epic-5
---

# 📄 PDF Generator Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Tailoring_Engine]], [[PRD_AI_CV_Builder]]

## Overview
Komponen penentu akhir yang mengonversi data CV yang telah di-tailor dan diedit oleh pengguna menjadi dokumen PDF siap kirim yang ATS-friendly.

## Rules for ATS PDF Compatibility
1. **Single-Column Layout:** Menghindari kolom ganda atau tabel kompleks yang membingungkan parser ATS.
2. **Standard Selectable Text:** Render berbasis font vector murni (bukan gambar/rasterized text).
3. **Clean HTML/CSS Template:** Memanfaatkan DomPDF Laravel.

## Connections
- Data source: Final edited version from [[Tailoring_Engine]]
- Flow spec: [[Task_Breakdown_AI_CV_Builder#epic-5-output-generation-teks--pdf]]
