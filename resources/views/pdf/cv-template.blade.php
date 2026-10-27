<!DOCTYPE html>
<html lang="{{ $lang ?? 'id' }}">
<head>
    <meta charset="UTF-8">
    <title>Curriculum Vitae - {{ $profile->name ?? 'Candidate' }}</title>
    <style>
        @page {
            margin: 20pt 30pt;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9.5pt;
            line-height: 1.35;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        h1 {
            font-size: 17pt;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 2pt 0;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
        }
        .target-title {
            text-align: center;
            font-size: 10.5pt;
            font-weight: bold;
            color: #334155;
            margin: 0 0 3pt 0;
            letter-spacing: 0.2pt;
        }
        .contact-info {
            text-align: center;
            font-size: 8.5pt;
            color: #475569;
            margin-bottom: 10pt;
            padding-bottom: 6pt;
            border-bottom: 1.5pt solid #0f172a;
        }
        .contact-info span {
            margin: 0 3pt;
        }
        h2 {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5pt;
            margin: 8pt 0 3pt 0;
            padding-bottom: 1.5pt;
            border-bottom: 1pt solid #cbd5e1;
        }
        p {
            margin: 0 0 4pt 0;
            text-align: justify;
        }
        .section {
            margin-bottom: 7pt;
        }
        .entry {
            margin-bottom: 5pt;
            page-break-inside: avoid;
        }
        .entry-header {
            margin-bottom: 1.5pt;
        }
        .entry-title {
            font-weight: bold;
            font-size: 9.5pt;
            color: #0f172a;
            display: inline;
        }
        .entry-company {
            font-weight: bold;
            color: #334155;
            display: inline;
        }
        .entry-date {
            float: right;
            font-size: 8.5pt;
            color: #64748b;
            font-style: italic;
        }
        .clear {
            clear: both;
        }
        ul {
            margin: 1.5pt 0 4pt 0;
            padding-left: 13pt;
        }
        li {
            margin-bottom: 1.5pt;
            text-align: justify;
        }
        .skill-group {
            margin-bottom: 3pt;
            font-size: 9pt;
        }
        .skill-category {
            font-weight: bold;
            color: #1e293b;
        }
    </style>
</head>
<body>

    @php
        $currentLang = $lang ?? 'id';
        $targetPos = $targetPosition ?? ($generatedCv->target_position ?? '');
        $cleanLinks = \App\Support\CvLocale::filterProfessionalLinks($profile->links ?? []);
    @endphp

    <!-- 1. HEADER (Candidate Name & Contact Info) -->
    <h1>{{ $profile->name ?? 'Candidate Name' }}</h1>
    @if(!empty($targetPos))
        <div class="target-title">{{ $targetPos }}</div>
    @endif
    <div class="contact-info">
        @if(!empty($profile->email))
            <span>{{ $profile->email }}</span>
        @endif
        @if(!empty($profile->phone))
            <span>&bull; {{ $profile->phone }}</span>
        @endif
        @if(!empty($profile->location))
            <span>&bull; {{ $profile->location }}</span>
        @endif
        @foreach($cleanLinks as $link)
            <span>&bull; {{ $link }}</span>
        @endforeach
    </div>

    <!-- 2. SUMMARY -->
    @if(!empty($tailoredCv['summary']))
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('summary', $currentLang) }}</h2>
            <p>{{ $tailoredCv['summary'] }}</p>
        </div>
    @endif

    <!-- 3. KEY PROJECTS -->
    @if(!empty($tailoredCv['projects']) && is_array($tailoredCv['projects']))
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('projects', $currentLang) }}</h2>
            @foreach($tailoredCv['projects'] as $proj)
                <div class="entry">
                    <div class="entry-header">
                        <span class="entry-title">{{ $proj['name'] ?? '' }}</span>
                        @if(!empty($proj['tech_stack']) && is_array($proj['tech_stack']))
                            <span style="color: #64748b; font-style: italic; font-size: 8.5pt;">({{ implode(', ', $proj['tech_stack']) }})</span>
                        @endif
                    </div>
                    @if(!empty($proj['bullet_points']) && is_array($proj['bullet_points']))
                        <ul>
                            @foreach($proj['bullet_points'] as $b)
                                <li>{{ is_array($b) ? ($b['content'] ?? '') : $b }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- 4. WORK EXPERIENCE -->
    @if(!empty($tailoredCv['experiences']) && is_array($tailoredCv['experiences']))
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('experience', $currentLang) }}</h2>
            @foreach($tailoredCv['experiences'] as $exp)
                <div class="entry">
                    <div class="entry-header">
                        <span class="entry-date">{{ \App\Support\CvLocale::formatRange($exp['start_date'] ?? '', $exp['end_date'] ?? '', $currentLang) }}</span>
                        <span class="entry-title">{{ $exp['position'] ?? '' }}</span> | <span class="entry-company">{{ $exp['company'] ?? '' }}</span>
                        <div class="clear"></div>
                    </div>
                    @if(!empty($exp['bullet_points']) && is_array($exp['bullet_points']))
                        <ul>
                            @foreach($exp['bullet_points'] as $b)
                                <li>{{ is_array($b) ? ($b['content'] ?? '') : $b }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <!-- 5. EDUCATION -->
    @if(!empty($tailoredCv['education']) && is_array($tailoredCv['education']))
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('education', $currentLang) }}</h2>
            @foreach($tailoredCv['education'] as $edu)
                <div class="entry">
                    <div class="entry-header">
                        <span class="entry-date">{{ \App\Support\CvLocale::formatRange($edu['start_date'] ?? '', $edu['end_date'] ?? '', $currentLang, true) }}</span>
                        <span class="entry-title">{{ $edu['degree'] ?? '' }}</span> | <span class="entry-company">{{ $edu['institution'] ?? '' }}</span>
                        @if(!empty($edu['notes']))
                            <span style="font-size: 8.5pt; color: #475569; font-weight: normal;"> &bull; {{ $edu['notes'] }}</span>
                        @endif
                        <div class="clear"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 6. SKILLS & COMPETENCIES -->
    @if(!empty($tailoredCv['skills_highlighted']) && is_array($tailoredCv['skills_highlighted']))
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('skills', $currentLang) }}</h2>
            @foreach($tailoredCv['skills_highlighted'] as $skGroup)
                <div class="skill-group">
                    <span class="skill-category">{{ $skGroup['category'] ?? 'Skills' }}:</span>
                    <span>{{ is_array($skGroup['skills'] ?? null) ? implode(', ', $skGroup['skills']) : '' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- 7. CERTIFICATIONS -->
    @if(!empty($certifications) && count($certifications) > 0)
        <div class="section">
            <h2>{{ \App\Support\CvLocale::header('certifications', $currentLang) }}</h2>
            <ul>
                @foreach($certifications as $cert)
                    <li>
                        <strong>{{ $cert->name }}</strong> &ndash; {{ $cert->issuer }} ({{ $cert->year }})
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</body>
</html>
