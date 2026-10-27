<?php

namespace App\Services;

use App\Models\Profile;
use App\Support\CvLocale;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

class DocxGeneratorService
{
    /**
     * Generate an ATS-friendly DOCX document and return the path to the temporary file.
     *
     * @param Profile|null $profile
     * @param Collection|array $certifications
     * @param array $tailoredCv
     * @param string $lang
     * @param string|null $targetPosition
     * @return string
     */
    public function generate(?Profile $profile, $certifications, array $tailoredCv, string $lang = 'id', ?string $targetPosition = null): string
    {
        // Crucial: enable XML entity escaping so special characters like '&' don't corrupt the document
        Settings::setOutputEscapingEnabled(true);

        $phpWord = new PhpWord();

        // Standard typography
        $fontFamily = 'Calibri';
        $phpWord->setDefaultFontName($fontFamily);
        $phpWord->setDefaultFontSize(10);

        // Standard page margins: 0.5 inch (720 twips), A4 integer dimensions
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
            'marginTop' => 720,
            'marginBottom' => 720,
            'marginLeft' => 720,
            'marginRight' => 720,
        ]);

        // Styles
        $headerNameStyle = ['name' => $fontFamily, 'size' => 17, 'bold' => true, 'color' => '0F172A'];
        $targetTitleStyle = ['name' => $fontFamily, 'size' => 10.5, 'bold' => true, 'color' => '334155'];
        $headerContactStyle = ['name' => $fontFamily, 'size' => 9, 'color' => '475569'];
        $sectionTitleStyle = ['name' => $fontFamily, 'size' => 11, 'bold' => true, 'color' => '0F172A'];
        $entryTitleBold = ['name' => $fontFamily, 'size' => 10, 'bold' => true, 'color' => '0F172A'];
        $entryDateStyle = ['name' => $fontFamily, 'size' => 9, 'italic' => true, 'color' => '64748B'];
        $bodyStyle = ['name' => $fontFamily, 'size' => 9.5, 'color' => '1E293B'];

        // Paragraph styles
        $centerAlign = ['alignment' => Jc::CENTER, 'spaceAfter' => 20];
        $sectionHeadingPara = [
            'spaceBefore' => 140,
            'spaceAfter' => 40,
            'borderBottomSize' => 6,
            'borderBottomColor' => 'CBD5E1',
        ];
        $bulletPara = [
            'spaceBefore' => 10,
            'spaceAfter' => 20,
        ];

        // 1. HEADER (Candidate Name & Contact Info)
        $candidateName = $profile->name ?? 'Candidate Name';
        $section->addText($candidateName, $headerNameStyle, $centerAlign);

        if (!empty($targetPosition)) {
            $section->addText($targetPosition, $targetTitleStyle, ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        }

        $contactParts = [];
        if (!empty($profile->email)) {
            $contactParts[] = $profile->email;
        }
        if (!empty($profile->phone)) {
            $contactParts[] = $profile->phone;
        }
        if (!empty($profile->location)) {
            $contactParts[] = $profile->location;
        }
        $filteredLinks = CvLocale::filterProfessionalLinks($profile->links ?? []);
        foreach ($filteredLinks as $l) {
            $contactParts[] = $l;
        }

        if (!empty($contactParts)) {
            $section->addText(implode('  •  ', $contactParts), $headerContactStyle, [
                'alignment' => Jc::CENTER,
                'spaceAfter' => 120,
            ]);
        }

        // 2. SUMMARY
        if (!empty($tailoredCv['summary'])) {
            $section->addText(CvLocale::header('summary', $lang), $sectionTitleStyle, $sectionHeadingPara);
            $section->addText($tailoredCv['summary'], $bodyStyle, ['spaceAfter' => 80, 'alignment' => Jc::BOTH]);
        }

        // 3. KEY PROJECTS
        if (!empty($tailoredCv['projects']) && is_array($tailoredCv['projects'])) {
            $section->addText(CvLocale::header('projects', $lang), $sectionTitleStyle, $sectionHeadingPara);

            foreach ($tailoredCv['projects'] as $proj) {
                $name = $proj['name'] ?? '';
                $techStack = !empty($proj['tech_stack']) && is_array($proj['tech_stack']) ? ' (' . implode(', ', $proj['tech_stack']) . ')' : '';

                $textrun = $section->addTextRun(['spaceBefore' => 20, 'spaceAfter' => 10]);
                $textrun->addText($name, $entryTitleBold);
                if (!empty($techStack)) {
                    $textrun->addText($techStack, ['name' => $fontFamily, 'size' => 9, 'italic' => true, 'color' => '64748B']);
                }

                if (!empty($proj['bullet_points']) && is_array($proj['bullet_points'])) {
                    foreach ($proj['bullet_points'] as $b) {
                        $content = is_array($b) ? ($b['content'] ?? '') : $b;
                        if (!empty($content)) {
                            $section->addListItem($content, 0, $bodyStyle, null, $bulletPara);
                        }
                    }
                }

                $section->addText('', [], ['spaceAfter' => 20]);
            }
        }

        // 4. WORK EXPERIENCE
        if (!empty($tailoredCv['experiences']) && is_array($tailoredCv['experiences'])) {
            $section->addText(CvLocale::header('experience', $lang), $sectionTitleStyle, $sectionHeadingPara);

            foreach ($tailoredCv['experiences'] as $exp) {
                $pos = $exp['position'] ?? '';
                $comp = $exp['company'] ?? '';
                $dateRange = CvLocale::formatRange($exp['start_date'] ?? '', $exp['end_date'] ?? '', $lang);

                // Header line: Position | Company and Date
                $headerTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
                $row = $headerTable->addRow();
                $cellLeft = $row->addCell(7200);
                $cellRight = $row->addCell(2800);

                $textrun = $cellLeft->addTextRun(['spaceAfter' => 10]);
                $textrun->addText($pos, $entryTitleBold);
                if (!empty($comp)) {
                    $textrun->addText(" | {$comp}", ['name' => $fontFamily, 'size' => 10, 'color' => '334155']);
                }

                $cellRight->addText($dateRange, $entryDateStyle, ['alignment' => Jc::RIGHT, 'spaceAfter' => 10]);

                // Bullet points
                if (!empty($exp['bullet_points']) && is_array($exp['bullet_points'])) {
                    foreach ($exp['bullet_points'] as $b) {
                        $content = is_array($b) ? ($b['content'] ?? '') : $b;
                        if (!empty($content)) {
                            $section->addListItem($content, 0, $bodyStyle, null, $bulletPara);
                        }
                    }
                }

                $section->addText('', [], ['spaceAfter' => 30]);
            }
        }

        // 5. EDUCATION
        if (!empty($tailoredCv['education']) && is_array($tailoredCv['education'])) {
            $section->addText(CvLocale::header('education', $lang), $sectionTitleStyle, $sectionHeadingPara);

            foreach ($tailoredCv['education'] as $edu) {
                $degree = $edu['degree'] ?? '';
                $inst = $edu['institution'] ?? '';
                $dateRange = CvLocale::formatRange($edu['start_date'] ?? '', $edu['end_date'] ?? '', $lang, true);

                $headerTable = $section->addTable(['width' => 100 * 50, 'unit' => 'pct']);
                $row = $headerTable->addRow();
                $cellLeft = $row->addCell(7200);
                $cellRight = $row->addCell(2800);

                $textrun = $cellLeft->addTextRun(['spaceAfter' => 10]);
                $textrun->addText($degree, $entryTitleBold);
                if (!empty($inst)) {
                    $textrun->addText(" | {$inst}", ['name' => $fontFamily, 'size' => 10, 'color' => '334155']);
                }
                if (!empty($edu['notes'])) {
                    $textrun->addText(" • {$edu['notes']}", ['name' => $fontFamily, 'size' => 9, 'color' => '475569']);
                }

                $cellRight->addText($dateRange, $entryDateStyle, ['alignment' => Jc::RIGHT, 'spaceAfter' => 10]);
                $section->addText('', [], ['spaceAfter' => 20]);
            }
        }

        // 6. SKILLS & COMPETENCIES
        if (!empty($tailoredCv['skills_highlighted']) && is_array($tailoredCv['skills_highlighted'])) {
            $section->addText(CvLocale::header('skills', $lang), $sectionTitleStyle, $sectionHeadingPara);

            foreach ($tailoredCv['skills_highlighted'] as $skGroup) {
                $category = $skGroup['category'] ?? 'Skills';
                $skillsList = is_array($skGroup['skills'] ?? null) ? implode(', ', $skGroup['skills']) : '';

                if (!empty($skillsList)) {
                    $textrun = $section->addTextRun(['spaceAfter' => 20]);
                    $textrun->addText("{$category}: ", ['name' => $fontFamily, 'size' => 9.5, 'bold' => true, 'color' => '0F172A']);
                    $textrun->addText($skillsList, $bodyStyle);
                }
            }
        }

        // 7. CERTIFICATIONS
        if (!empty($certifications) && count($certifications) > 0) {
            $section->addText(CvLocale::header('certifications', $lang), $sectionTitleStyle, $sectionHeadingPara);

            foreach ($certifications as $cert) {
                $certName = $cert->name ?? '';
                $issuer = $cert->issuer ?? '';
                $year = $cert->year ?? '';
                $text = "{$certName} – {$issuer} ({$year})";

                $section->addListItem($text, 0, $bodyStyle, null, $bulletPara);
            }
        }

        // Save to temporary file
        $tempPath = tempnam(sys_get_temp_dir(), 'cv_docx_') . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }
}
