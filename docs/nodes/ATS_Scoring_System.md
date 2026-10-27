---
title: ATS Scoring System Node
tags:
  - node
  - ai
  - ats-score
  - epic-4
---

# 📊 ATS Scoring System Node

Main Hub: [[AI_CV_Builder_Graph]] | Related: [[Tailoring_Engine]], [[JSON_Contract_Tailoring_Scoring]]

## Overview
Layanan evaluasi independen yang membandingkan CV tailored (hasil dari [[Tailoring_Engine]]) dengan Job Description sasaran untuk menghasilkan skor kuantitatif dan masukan kata kunci.

## Key Metrics
1. **Overall Match Percentage (%)**
2. **Missing Hard Skills & Soft Skills**
3. **Keyword Density & Action Verbs Feedback**

## Single Responsibility API Call
- Pemanggilan API ke LLM dilakukan secara **terpisah dari Call 1 (Tailoring)** agar debugging dan error handling terisolasi.
- Kontrak JSON detail: [[JSON_Contract_Tailoring_Scoring#2-json-contract-ats-scoring-engine-call-2]]
