# Task: CV Assistant Agent (Tool-Calling / Function Calling dengan Gemini)

## Konteks
Project CV Builder (Laravel) sudah punya fitur mini-RAG doc chatbot (lihat
`task-rag-docs-chatbot.md`) yang bisa menjawab pertanyaan berdasarkan dokumentasi project.
Task ini membangun lapisan baru di atasnya: sebuah **agent** yang memakai tool-calling (function
calling) Gemini API, sehingga LLM sendiri yang memutuskan tool mana yang perlu dipanggil untuk
menjawab pertanyaan user — bukan alur linear yang sudah ditentukan di kode seperti RAG chatbot
biasa.

Fitur ini scope-nya dev/internal tool untuk belajar konsep agent, sama seperti mini-RAG chatbot
sebelumnya — tidak perlu masuk ke flow utama aplikasi atau UI produksi.

## Konsep yang diimplementasikan
Gemini API mendukung *function calling*: kita kirim daftar tools (nama, deskripsi, parameter)
bersama prompt user. Model bisa merespons dengan permintaan "panggil tool X dengan parameter Y"
alih-alih langsung menjawab teks. Kode kita yang mengeksekusi tool itu, lalu hasilnya dikirim
balik ke model sebagai context tambahan (function response), dan model melanjutkan — bisa
memanggil tool lain lagi, atau langsung memberi jawaban final. Proses ini bisa berulang
(loop) sampai model merasa cukup informasi.

## Scope: 2 tools

### Tool 1: `query_docs`
- **Deskripsi untuk model**: "Cari jawaban dari dokumentasi teknis project CV Builder (PRD,
  ERD, Task Breakdown, arsitektur fitur). Gunakan tool ini untuk pertanyaan seputar desain,
  skema, atau cara kerja sistem yang terdokumentasi."
- **Parameter**: `question` (string) — pertanyaan yang mau ditanyakan ke dokumentasi.
- **Implementasi**: panggil ulang logic yang sudah ada di `DocsQaController@ask` (retrieval +
  cosine similarity + jawaban dari `docs_index.json`). Reuse service yang sudah ada, jangan
  duplikasi logic — refactor jadi method yang bisa dipanggil dari controller lama maupun dari
  tool baru ini kalau perlu.
- **Return ke model**: jawaban dari RAG chatbot (string), bisa juga sertakan source chunk-nya.

### Tool 2: `get_master_profile_summary`
- **Deskripsi untuk model**: "Ambil ringkasan data Master Profile user saat ini: jumlah
  experience bullets, jumlah skill, jumlah sertifikasi, dan section mana saja yang sudah/belum
  terisi. Gunakan tool ini untuk pertanyaan soal kelengkapan atau isi data profile user
  sebenarnya (bukan dokumentasi)."
- **Parameter**: tidak perlu parameter (ambil dari user yang sedang login / user default kalau
  belum ada auth multi-user).
- **Implementasi**: query langsung ke model Master Profile (dan tabel relasinya seperti
  experience bullets, skills, certifications) yang sudah ada dari Epic 1. Susun jadi ringkasan
  terstruktur (jumlah per kategori + daftar section kosong).
- **Return ke model**: ringkasan dalam bentuk terstruktur (JSON atau string terformat).

## Alur teknis yang perlu dibangun

1. Buat service class baru, misal `CvAssistantAgentService`, terpisah dari
   `DocEmbeddingService`/`TailoringEngine` yang sudah ada — reuse dependency yang perlu, jangan
   duplikasi kode.
2. Definisikan skema kedua tools di atas dalam format yang dikenali Gemini function calling
   (`functionDeclarations` — cek dokumentasi Gemini API terbaru untuk format persis payload-nya,
   karena ini bagian yang paling sering berubah antar versi API).
3. Kirim request pertama ke Gemini dengan prompt user + daftar tools tersebut.
4. Kalau response dari Gemini berisi `functionCall` (bukan teks jawaban langsung):
   - Eksekusi tool yang diminta (mapping nama tool ke method PHP yang sesuai).
   - Kirim hasil eksekusi itu balik ke Gemini sebagai `functionResponse` dalam lanjutan
     percakapan yang sama.
   - Ulangi proses ini (model bisa minta tool lagi) sampai model akhirnya merespons dengan teks
     jawaban final (bukan function call lagi). Batasi maksimal loop, misal 5 iterasi, untuk
     mencegah infinite loop kalau model "nyangkut".
5. Kembalikan jawaban final ke user, idealnya sertakan juga trace tool apa saja yang dipanggil
   selama proses (untuk keperluan debugging/belajar, tampilkan di response atau log).

## Endpoint & view sederhana
- Route + controller baru, misal `AgentController@ask`, menerima `question` dari user.
- View blade sederhana: form input pertanyaan, tampilkan jawaban final, DAN tampilkan trace tool
  calls yang terjadi (nama tool + parameter + hasil ringkas) — ini penting supaya kita bisa
  belajar dan verifikasi model benar-benar memanggil tool yang tepat, bukan cuma jawab dari
  pengetahuan umumnya.

## Test case yang wajib dicoba manual setelah selesai
Pertanyaan ini sengaja butuh KEDUA tools sekaligus:

> "Jelasin skema database Master Profile berdasarkan dokumentasi, terus kasih tau juga apakah
> data master profile gue sekarang udah lengkap sesuai skema itu."

Yang diharapkan: agent memanggil `query_docs` (untuk bagian skema/dokumentasi) DAN
`get_master_profile_summary` (untuk bagian data user aktual), lalu menggabungkan hasil
keduanya jadi satu jawaban koheren. Cek trace tool calls untuk memverifikasi dua-duanya
benar terpanggil, bukan cuma salah satu.

## Non-goals
- Jangan integrasikan ke flow tailoring utama atau UI produksi.
- Jangan tambah tool lain di luar 2 yang disebutkan di atas untuk versi pertama ini.
- Jangan implementasikan streaming response — cukup request-response biasa dulu.

## Definition of done
- Endpoint agent bisa menerima pertanyaan dan merespons dengan jawaban final.
- Trace tool calls (tool apa yang dipanggil, dengan parameter apa) terlihat di response atau log.
- Test case di atas (butuh 2 tools sekaligus) berhasil memanggil KEDUA tools sebelum menjawab.
- Ada batas maksimal iterasi loop untuk mencegah infinite loop.
