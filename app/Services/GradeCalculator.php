<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use InvalidArgumentException;
use RuntimeException;

final class GradeCalculator
{
    /** @return array{percentage: float, letter_grade: string, gpa_points: float} */
    public function calculate(float $marksObtained, float $maximumMarks, int $schoolId): array
    {
        if ($maximumMarks <= 0 || $marksObtained < 0 || $marksObtained > $maximumMarks) {
            throw new InvalidArgumentException('Marks must be between zero and the exam maximum.');
        }

        $percentage = round(($marksObtained / $maximumMarks) * 100, 2);
        $query = Database::connection()->prepare(
            'SELECT letter_grade, gpa_points FROM grade_scales
             WHERE school_id = :school_id AND :percentage BETWEEN min_percent AND max_percent'
        );
        $query->execute(['school_id' => $schoolId, 'percentage' => $percentage]);
        $matches = $query->fetchAll();
        if (count($matches) !== 1) {
            throw new RuntimeException('The school grading scale must cover the percentage exactly once.');
        }

        return [
            'percentage' => $percentage,
            'letter_grade' => $matches[0]['letter_grade'],
            'gpa_points' => (float) $matches[0]['gpa_points'],
        ];
    }
}
