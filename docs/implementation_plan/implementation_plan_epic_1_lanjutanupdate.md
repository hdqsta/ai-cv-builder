# Implementation Plan - Upload & AI-Parsing CV Lama (Revised)

This plan extends Epic 1 to support uploading a legacy CV (in PDF or DOCX format), extracting its raw text, parsing it into a structured JSON representation using the Google Gemini API, sanitizing the parsed data (especially dates and arrays), and presenting it in an editable review form before saving it to the Master Profile database.

## User Review Required

> [!IMPORTANT]
> - **Dependency Installation**: We will install `smalot/pdfparser` via Composer to parse text from uploaded PDF files. DOCX text extraction will be handled natively using PHP's built-in `ZipArchive` and `DOMDocument` extensions.
> - **Gemini API Configuration**: We will register `services.gemini.key` pointing to `env('GEMINI_API_KEY')` and `services.gemini.model` pointing to `env('GEMINI_MODEL', 'gemini-3.1-flash-lite')` in `config/services.php`.
> - **429 Rate Limit Handling**: `GeminiParserService` will implement a retry loop with exponential backoff (e.g. delay 1s, 2s, 4s) when encountering status 429. If all retries fail, it throws an informative exception.
> - **Session-based Review State**: The raw parsed and sanitized data will be stored temporarily in the session (e.g., `session('parsed_cv')`). The user must view and click "Save to Master Profile" on the review page to commit the data.
> - **Date and Text Sanitization**: Dates like "Jan 2022", "2022-01", etc. will be normalized to `YYYY-MM-DD` on the backend. Skills and bullet points will be forced into clean flat arrays of strings.

## Open Questions

None.

---

## Proposed Changes

### Configuration

#### [MODIFY] [.env.example](file:///c:/laragon/www/cv-builder/.env.example)
- Append placeholders:
  ```env
  GEMINI_API_KEY=
  GEMINI_MODEL=gemini-3.1-flash-lite
  ```

#### [MODIFY] [services.php](file:///c:/laragon/www/cv-builder/config/services.php)
- Add configuration for gemini:
  ```php
  'gemini' => [
      'key' => env('GEMINI_API_KEY'),
      'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
  ],
  ```

---

### Routing & Controllers

#### [MODIFY] [web.php](file:///c:/laragon/www/cv-builder/routes/web.php)
Register routing endpoints for the upload and review flows:
- `GET /upload` (shows upload page)
- `POST /upload` (processes file, calls LLM, redirects to review)
- `GET /review` (shows pre-filled form with parsed data)
- `POST /review/save` (commits form data to the database)

#### [NEW] [CvUploadController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/CvUploadController.php)
Methods:
- `showUploadForm()`: Returns upload Blade view.
- `upload(Request $request)`:
  - Validates that the uploaded file is PDF or DOCX (max 5MB).
  - Extracts raw text from the file.
  - Calls `GeminiParserService` to obtain structured CV data. Handles 429/connection exception by catching it and redirecting back with a friendly alert ("Layanan AI sedang sibuk, coba lagi beberapa saat").
  - Sanitizes the data (dates, skills, projects).
  - Stores the structured data in the session and redirects to `review()`.
- `showReviewForm()`:
  - Fetches the parsed CV data from the session. If empty, redirects to `/upload`.
  - Returns the review Blade view.
- `save(Request $request)`:
  - Validates the editable review form.
  - Empties any existing Master Profile tables (`profiles`, `experiences`, `experience_bullets`, `education`, `skills`, `projects`, `project_bullets`, `certifications`) since it replaces the profile.
  - Saves the new data to the respective tables.
  - Clears the session state and redirects home with a success message.

---

### Helper/Service Classes

#### [NEW] [TextExtractor.php](file:///c:/laragon/www/cv-builder/app/Services/TextExtractor.php)
Utility class to extract text:
- `extract($filePath, $extension)`: Dispatches to `extractPdf()` or `extractDocx()`.
- `extractPdf($filePath)`: Uses `smalot/pdfparser` to get raw text.
- `extractDocx($filePath)`: Opens DOCX archive using `ZipArchive` and extracts paragraphs (`<w:p>`) from `word/document.xml`.

#### [NEW] [GeminiParserService.php](file:///c:/laragon/www/cv-builder/app/Services/GeminiParserService.php)
Service class to interact with Gemini:
- `parse($rawText)`: Calls `https://generativelanguage.googleapis.com/v1beta/models/{MODEL}:generateContent?key={API_KEY}`.
- Wraps the request in a retry loop (2-3 attempts) with a short sleep (exponential backoff) if status code 429 is encountered.
- Configures prompt to return JSON adhering strictly to a JSON contract.
- Normalizes/sanitizes parsing errors, returning a structured associative array or throwing an exception on failure.

---

### UI Views (Blade Templates)

#### [NEW] [cv/upload.blade.php](file:///c:/laragon/www/cv-builder/resources/views/cv/upload.blade.php)
- Basic file upload form with drag-and-drop design or standard input.
- Displays loading spinner/message during upload and processing.

#### [NEW] [cv/review.blade.php](file:///c:/laragon/www/cv-builder/resources/views/cv/review.blade.php)
- Comprehensive pre-filled form displaying all extracted blocks (Profile, Experiences, Projects, Education, Skills, Certifications).
- Features dynamic additions/deletions of bullets, links, and entries identical to the CRUD modules.

---

## Verification Plan

### Automated/Local Tests
- Run `composer require smalot/pdfparser` to pull the PDF parsing dependency.
- Configure a mock or sandbox Gemini API key in `.env` to verify LLM requests.
- Verify text extraction code with a unit test or simple routing endpoint against sample `.pdf` and `.docx` files.
- Launch a browser subagent to:
  - Upload a test CV (we will generate a sample PDF/DOCX CV using a script).
  - Inspect the pre-filled review form to ensure fields (dates, bullets, links) are correctly extracted and sanitized.
  - Click "Save to Master Profile".
  - Verify that the profile, experiences, and other databases are successfully populated with the parsed CV data.
