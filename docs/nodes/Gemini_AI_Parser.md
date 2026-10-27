---
title: Gemini AI Parser Node
tags:
  - node
  - ai
  - gemini
  - epic-1
---

# 🤖 Gemini AI Parser Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Master_Profile_Database]], [[walkthrough_epic_1_lanjutan]]

## Overview
Engine pemrosesan dokumen berbasis Google Gemini API (`gemini-3.1-flash-lite`). Engine ini mengambil teks mentah dari file PDF/DOCX melalui `TextExtractor` dan mengonversinya menjadi JSON terstruktur yang memetakan:
1. Personal Bio & Contacts
2. Work Experiences & Bullets
3. Projects & Tech Stacks
4. Academic Degrees
5. Categorized Skills
6. Certifications

## Technical Components
- **Class:** `App\Services\GeminiParserService`
- **Extractor:** `App\Services\TextExtractor` (Smalot PDF Parser & Native DOCX XML Unzipper)
- **Controller:** `App\Http\Controllers\CvUploadController`
- **Sanitizer:** [[cv_parser_date_and_skill_category_handling]]

## Connections
- Output populates: [[Master_Profile_Database]]
- Document Flow: [[Task_Breakdown_AI_CV_Builder#epic-1-master-profile-management]]
