# Implementation Plan - Epic 1: Master Profile Management (Revised)

This plan establishes the foundation of the AI-Powered CV Builder application. It sets up the relational database tables, Eloquent models, controllers, and simple web views to manage a user's Master Profile (profile data, experience, education, skills, projects, and certifications).

## User Review Required

> [!IMPORTANT]
> - **Single Profile Model**: The profile management form will assume a single global profile record (personal MVP). We will use `Profile::firstOrNew()` to automatically fetch or initialize the single record.
> - **Dynamic Bullet Points**: Experience and Project forms will use vanilla JavaScript in Blade templates to dynamically add or remove bullet points on the frontend. The backend controller will synchronize these bullets upon submission (inserting new, updating existing, and deleting removed ones).
> - **Simple Styling**: We will use default, standard Tailwind CSS classes to style the forms, lists, and pages to ensure readability and usability, without any custom design systems or animations.
> - **Simple Navigation Landing**: Instead of a dedicated dashboard, we will modify the root route (`/`) or simple index page to display standard lists of links to each module (Profile, Experience, Education, Skills, Projects, Certifications).
> - **Database Driver**: Commands will run using Laragon's PHP executable (`C:\laragon\bin\php\php-8.3.24-nts-Win32-vs16-x64\php.exe`) since the system PHP does not have the SQLite driver enabled.

## Open Questions

None.

---

## Proposed Changes

### Database Schema (Migrations)

We will create migrations for all 9 tables defined in the ERD. Foreign key constraints will cascade deletions on sub-entities (e.g., deleting an experience deletes its bullets).

#### [NEW] [migrations](file:///c:/laragon/www/cv-builder/database/migrations/)
- `2026_07_12_000001_create_profiles_table.php`
- `2026_07_12_000002_create_experiences_table.php`
- `2026_07_12_000003_create_experience_bullets_table.php`
- `2026_07_12_000004_create_education_table.php`
- `2026_07_12_000005_create_skills_table.php`
- `2026_07_12_000006_create_projects_table.php`
- `2026_07_12_000007_create_project_bullets_table.php`
- `2026_07_12_000008_create_certifications_table.php`
- `2026_07_12_000009_create_generated_cvs_table.php`

---

### Eloquent Models

Define the standard Eloquent models using standard fillable arrays and casts.

#### [NEW] [Profile.php](file:///c:/laragon/www/cv-builder/app/Models/Profile.php)
- Attributes: `name`, `email`, `phone`, `location`, `summary`, `links` (json cast as array).

#### [NEW] [Experience.php](file:///c:/laragon/www/cv-builder/app/Models/Experience.php)
- Attributes: `company`, `position`, `start_date` (date), `end_date` (date, nullable).
- Relationship: `hasMany(ExperienceBullet::class)`.

#### [NEW] [ExperienceBullet.php](file:///c:/laragon/www/cv-builder/app/Models/ExperienceBullet.php)
- Attributes: `experience_id`, `content`, `order`.
- Relationship: `belongsTo(Experience::class)`.

#### [NEW] [Education.php](file:///c:/laragon/www/cv-builder/app/Models/Education.php)
- Attributes: `institution`, `degree`, `start_date` (date), `end_date` (date, nullable), `notes`.

#### [NEW] [Skill.php](file:///c:/laragon/www/cv-builder/app/Models/Skill.php)
- Attributes: `name` (unique), `category`.

#### [NEW] [Project.php](file:///c:/laragon/www/cv-builder/app/Models/Project.php)
- Attributes: `name`, `tech_stack` (json cast as array), `link`.
- Relationship: `hasMany(ProjectBullet::class)`.

#### [NEW] [ProjectBullet.php](file:///c:/laragon/www/cv-builder/app/Models/ProjectBullet.php)
- Attributes: `project_id`, `content`, `order`.
- Relationship: `belongsTo(Project::class)`.

#### [NEW] [Certification.php](file:///c:/laragon/www/cv-builder/app/Models/Certification.php)
- Attributes: `name`, `issuer`, `year`.

#### [NEW] [GeneratedCv.php](file:///c:/laragon/www/cv-builder/app/Models/GeneratedCv.php)
- Attributes: `job_description`, `tailored_cv_raw` (json), `tailored_cv_final` (json), `ats_score` (json, nullable), `status`.

---

### Routing & Controllers

#### [MODIFY] [web.php](file:///c:/laragon/www/cv-builder/routes/web.php)
Define resource endpoints for experiences, education, skills, projects, and certifications. Define custom endpoints for the single-record Profile (get/post). Define the home landing page mapping to a simple blade view.

#### [NEW] [ProfileController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/ProfileController.php)
- `edit()`: Show edit view for the first Profile (or new instance).
- `update()`: Validate input, decode/save links, and save.

#### [NEW] [ExperienceController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/ExperienceController.php)
- Handle index, create, store, edit, update, destroy.
- The `store`/`update` methods will process nested bullet arrays, saving/deleting as appropriate.

#### [NEW] [ProjectController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/ProjectController.php)
- Handle index, create, store, edit, update, destroy.
- Process nested bullet arrays and parse tech stack input into a JSON array.

#### [NEW] [EducationController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/EducationController.php)
- Handle index, create, store, edit, update, destroy.

#### [NEW] [SkillController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/SkillController.php)
- Handle index/management, store, destroy. Inline CRUD on one page.

#### [NEW] [CertificationController.php](file:///c:/laragon/www/cv-builder/app/Http/Controllers/CertificationController.php)
- Handle index, create, store, edit, update, destroy.

---

### UI Views (Blade Templates)

We will build a simple layout and views styled with basic Tailwind utility classes.

#### [NEW] [app.blade.php](file:///c:/laragon/www/cv-builder/resources/views/layouts/app.blade.php)
- Simple top/side navbar, structured container main section.
- Dynamic flash notifications for success and error states.

#### [MODIFY] [welcome.blade.php](file:///c:/laragon/www/cv-builder/resources/views/welcome.blade.php)
- Simple landing page showing clear heading and list of links to each Master Profile module.

#### [NEW] [profile/edit.blade.php](file:///c:/laragon/www/cv-builder/resources/views/profile/edit.blade.php)
- Form to edit Name, Email, Phone, Location, Summary, and Links.

#### [NEW] [experiences/index.blade.php](file:///c:/laragon/www/cv-builder/resources/views/experiences/index.blade.php)
#### [NEW] [experiences/create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/experiences/create.blade.php)
#### [NEW] [experiences/edit.blade.php](file:///c:/laragon/www/cv-builder/resources/views/experiences/edit.blade.php)
- Form with simple JS to dynamically append and remove experience bullets.

#### [NEW] [projects/index.blade.php](file:///c:/laragon/www/cv-builder/resources/views/projects/index.blade.php)
#### [NEW] [projects/create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/projects/create.blade.php)
#### [NEW] [projects/edit.blade.php](file:///c:/laragon/www/cv-builder/resources/views/projects/edit.blade.php)
- Form with simple JS to dynamically append and remove project bullets and input tag arrays.

#### [NEW] [education/index.blade.php](file:///c:/laragon/www/cv-builder/resources/views/education/index.blade.php)
#### [NEW] [education/create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/education/create.blade.php)
#### [NEW] [education/edit.blade.php](file:///c:/laragon/www/cv-builder/resources/views/education/edit.blade.php)

#### [NEW] [skills/index.blade.php](file:///c:/laragon/www/cv-builder/resources/views/skills/index.blade.php)
- Unified skill index and fast creation layout.

#### [NEW] [certifications/index.blade.php](file:///c:/laragon/www/cv-builder/resources/views/certifications/index.blade.php)
#### [NEW] [certifications/create.blade.php](file:///c:/laragon/www/cv-builder/resources/views/certifications/create.blade.php)
#### [NEW] [certifications/edit.blade.php](file:///c:/laragon/www/cv-builder/resources/views/certifications/edit.blade.php)

---

## Verification Plan

### Automated/Local Tests
- Run `npm install` and `npm run build` to build Tailwind CSS resources.
- Run migrations: `& "C:\laragon\bin\php\php-8.3.24-nts-Win32-vs16-x64\php.exe" artisan migrate:fresh`.
- Verify database tables in sqlite: `& "C:\laragon\bin\php\php-8.3.24-nts-Win32-vs16-x64\php.exe" artisan migrate:status`.
- We will start the local server: `& "C:\laragon\bin\php\php-8.3.24-nts-Win32-vs16-x64\php.exe" artisan serve`.
- Launch a browser subagent to interactively verify the CRUD views for Profile, Experience (adding and removing bullets), Projects, Education, Skills, and Certifications.
- Verify validation rules by submitting blank inputs and checking for errors.

### Manual Verification
- Review generated visual page outputs to verify correctness and functionality.
