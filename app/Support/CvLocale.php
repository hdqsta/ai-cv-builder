<?php

namespace App\Support;

use Carbon\Carbon;

class CvLocale
{
    private const HEADERS = [
        'id' => [
            'summary' => 'RINGKASAN PROFIL',
            'experience' => 'PENGALAMAN KERJA',
            'education' => 'PENDIDIKAN',
            'skills' => 'KEAHLIAN & KOMPETENSI',
            'projects' => 'PROYEK UTAMA',
            'certifications' => 'SERTIFIKASI & PELATIHAN',
        ],
        'en' => [
            'summary' => 'PROFESSIONAL SUMMARY',
            'experience' => 'WORK EXPERIENCE',
            'education' => 'EDUCATION',
            'skills' => 'SKILLS & COMPETENCIES',
            'projects' => 'KEY PROJECTS',
            'certifications' => 'CERTIFICATIONS & CREDENTIALS',
        ],
    ];

    private const MONTHS_ID = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    private const MONTHS_EN = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * Get section header text based on language.
     */
    public static function header(string $key, string $lang = 'id'): string
    {
        $lang = strtolower($lang) === 'en' ? 'en' : 'id';
        return self::HEADERS[$lang][$key] ?? self::HEADERS['en'][$key] ?? strtoupper($key);
    }

    /**
     * Format a single date string (e.g. "2025-07-01") to localized month & year.
     */
    public static function date(?string $date, string $lang = 'id', bool $yearOnly = false): string
    {
        if (empty($date) || strtolower(trim($date)) === 'present' || strtolower(trim($date)) === 'sekarang') {
            return strtolower($lang) === 'en' ? 'Present' : 'Sekarang';
        }

        try {
            $c = Carbon::parse($date);
            if ($yearOnly) {
                return $c->format('Y');
            }

            $m = (int) $c->format('n');
            $month = strtolower($lang) === 'en'
                ? (self::MONTHS_EN[$m] ?? $c->format('M'))
                : (self::MONTHS_ID[$m] ?? $c->format('M'));

            return $month . ' ' . $c->format('Y');
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Format a date range (start – end) neatly.
     */
    public static function formatRange(?string $start, ?string $end, string $lang = 'id', bool $yearOnly = false): string
    {
        $startStr = self::date($start, $lang, $yearOnly);
        $endStr = self::date($end, $lang, $yearOnly);

        if (empty($startStr) && empty($endStr)) {
            return '';
        }

        if (empty($startStr)) {
            return $endStr;
        }

        if (empty($endStr) || $endStr === 'Present' || $endStr === 'Sekarang') {
            return $startStr . ' – ' . (strtolower($lang) === 'en' ? 'Present' : 'Sekarang');
        }

        if ($yearOnly && $startStr === $endStr) {
            return $startStr;
        }

        return $startStr . ' – ' . $endStr;
    }

    /**
     * Clean and normalize a URL for CV presentation.
     * Strips query tracking params like '?isSelfProfile=true', 'https://', 'www.', trailing slashes.
     */
    public static function cleanUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $trimmed = trim($url);
        $parsed = parse_url($trimmed);

        if (!$parsed || empty($parsed['host'])) {
            // Might be a handle or path like linkedin.com/...
            $trimmed = preg_replace('~^https?://~i', '', $trimmed);
            $trimmed = preg_replace('~^www\.~i', '', $trimmed);
            $parts = explode('?', $trimmed);
            return rtrim($parts[0], '/');
        }

        $host = preg_replace('~^www\.~i', '', $parsed['host']);
        $path = rtrim($parsed['path'] ?? '', '/');

        return $host . $path;
    }

    /**
     * Filter links to only keep relevant professional links (LinkedIn, GitHub, Portfolio).
     * Excludes Instagram/TikTok/Twitter/Facebook for formal job applications.
     */
    public static function filterProfessionalLinks(array|string|null $links): array
    {
        if (empty($links)) {
            return [];
        }

        if (is_string($links)) {
            $links = json_decode($links, true) ?? [$links];
        }

        $filtered = [];
        $disallowedPatterns = ['instagram.com', 'tiktok.com', 'facebook.com', 'threads.net'];

        foreach ($links as $link) {
            if (empty($link)) {
                continue;
            }

            $lower = strtolower($link);
            $isDisallowed = false;
            foreach ($disallowedPatterns as $pattern) {
                if (str_contains($lower, $pattern)) {
                    $isDisallowed = true;
                    break;
                }
            }

            if (!$isDisallowed) {
                $cleaned = self::cleanUrl($link);
                if (!empty($cleaned)) {
                    $filtered[] = $cleaned;
                }
            }
        }

        return $filtered;
    }

    /**
     * Auto-detect whether Job Description is in Indonesian or English based on stop words.
     */
    public static function detectLanguage(string $jd): string
    {
        $text = ' ' . mb_strtolower($jd) . ' ';
        $idWords = ['yang', 'dan', 'untuk', 'dengan', 'dalam', 'atau', 'kami', 'memiliki', 'tanggung', 'jawab', 'kualifikasi', 'pekerjaan'];
        $enWords = ['the', 'and', 'for', 'with', 'you', 'our', 'will', 'have', 'responsibilities', 'qualifications', 'requirements', 'experience'];

        $idScore = 0;
        foreach ($idWords as $w) {
            $idScore += substr_count($text, " $w ");
        }

        $enScore = 0;
        foreach ($enWords as $w) {
            $enScore += substr_count($text, " $w ");
        }

        return $idScore >= $enScore ? 'id' : 'en';
    }
}
