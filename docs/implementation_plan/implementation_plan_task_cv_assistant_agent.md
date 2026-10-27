# Implementation Plan — CV Assistant Agent (Gemini Function Calling)

Membangun dev tool **CV Assistant Agent** berbasis *Function Calling / Tool-Calling* dengan Gemini API. Agent ini mampu secara mandiri memutuskan kapan harus mencari informasi di dokumentasi project (`query_docs`) dan kapan harus mengambil ringkasan data aktual pengguna dari database (`get_master_profile_summary`).

## User Review Required

> [!IMPORTANT]
> - **Gemini Function Calling**: Menggunakan endpoint `models/{model}:generateContent` dengan payload `tools` (`functionDeclarations`).
> - **Loop Control**: Batas maksimum iterasi tool-calling ditetapkan 5 kali untuk mencegah *infinite loop*.
> - **Trace UI**: Halaman dev `/agent-qa` akan menampilkan timeline *Tool Calls Trace* agar alur eksekusi tool oleh LLM terlihat secara transparan.

## Open Questions

Tidak ada. Persyaratan dari spesifikasi [task-cv-assistant-agent.md](file:///c:/laragon/www/cv-builder/docs/prompt_history/task-cv-assistant-agent.md) sudah sangat jelas.

## Proposed Changes

### Backend Services & Refactoring

#### [MODIFY] [DocsQaController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/DocsQaController.php)
- Mengekstrak logic RAG retrieval dari `DocsQaController` menjadi helper method / service method publik agar dapat di-reuse oleh tool `query_docs` tanpa duplikasi kode.

#### [NEW] [CvAssistantAgentService.php](file:///c:/laragon/www/cv-builder/app/Services/CvAssistantAgentService.php)
- Service utama agen `App\Services\CvAssistantAgentService`.
- Mendefinisikan 2 skema tool:
  1. `query_docs`: Mencari jawaban dari index dokumentasi RAG.
  2. `get_master_profile_summary`: Query ke database Master Profile (`Profile`, `Experience`, `Education`, `Skill`, `Project`, `Certification`) dan merangkum kelengkapan section.
- Mengelola perulangan (loop agentic): menguji respons `functionCall`, mengeksekusi tool PHP yang sesuai, mengirim balik `functionResponse`, hingga memperoleh jawaban teks final dari Gemini.
- Mencatat *trace history* dari setiap *tool call*.

### Controllers & Routes

#### [NEW] [AgentController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/AgentController.php)
- Controller `App\Http\Controllers\AgentController`.
- Method `index()` untuk menampilkan Blade view dev agent.
- Method `ask(Request $request)` untuk memproses pertanyaan, mengeksekusi agent loop, dan mengembalikan jawaban beserta trace tool calls.

#### [MODIFY] [web.php](file:///c:/laragon/www/cv-builder/routes/web.php)
- Mendaftarkan route dev `/agent-qa` (GET & POST).

### Frontend / View

#### [NEW] [agent-qa.blade.php](file:///c:/laragon/www/cv-builder/resources/views/agent-qa.blade.php)
- Halaman UI dev agent berbasis Tailwind CSS + Glassmorphism.
- Menyediakan textarea input pertanyaan, button preset (termasuk test case wajib yang membutuhkan 2 tools sekaligus), indikator loading agent, area jawaban markdown, serta **Timeline Trace Tool Calls** (menampilkan tool mana yang dipanggil, parameternya, dan ringkasan hasilnya).

---

## Verification Plan

### Manual Verification Test Case
1. Jalankan server Laravel dan buka `/agent-qa`.
2. Uji pertanyaan spesifik berikut:
   > *"Jelasin skema database Master Profile berdasarkan dokumentasi, terus kasih tau juga apakah data master profile gue sekarang udah lengkap sesuai skema itu."*
3. Verifikasi pada **Trace Timeline** bahwa Gemini memanggil **KEDUA tools** (`query_docs` DAN `get_master_profile_summary`) secara otomatis sebelum menyusun jawaban akhir secara koheren.
