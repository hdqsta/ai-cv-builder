---
title: Tailoring Engine Node
tags:
  - node
  - ai
  - tailoring
  - epic-3
---

# 🎯 Tailoring Engine Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[JSON_Contract_Tailoring_Scoring]], [[Master_Profile_Database]]

## Overview
Layanan inti yang mengambil data dari [[Master_Profile_Database]] dan teks Job Description target, kemudian menggunakan LLM untuk mereframe bullet point pengalaman agar relevan dengan kualifikasi pekerjaan sasaran.

## Core Constraints
- **Strict Anti-Fabrication Rule:** AI dilarang keras mengarang/menambah pengalaman, skill, atau perusahaan yang tidak tercantum dalam Master Profile.
- **Output JSON Contract:** Sesuai spesifikasi pada [[JSON_Contract_Tailoring_Scoring#1-json-contract-ai-tailoring-engine-call-1]].

## Connections
- Inputs: Data dari [[Master_Profile_Database]] + Target Job Description
- Next Stage: [[ATS_Scoring_System]]
- Final Output Generation: [[PDF_Generator]]
