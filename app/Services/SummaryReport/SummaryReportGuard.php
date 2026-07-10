<?php

declare(strict_types=1);

namespace App\Services\SummaryReport;

use App\Models\Student;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function in_array;

class SummaryReportGuard
{
    public const PROGRAM_MAX_LENGTH = 100;

    private const VALID_PERIODS = ['this_week', 'this_month', 'all_time'];
    private const VALID_TREND_DAYS = [7, 30];

    /**

     * @throws NotFoundHttpException
     */
    public function ensureProgramExists(string $program): string
    {
        $program = trim($program);

        if ($program === '' || mb_strlen($program) > self::PROGRAM_MAX_LENGTH) {
            throw new NotFoundHttpException('Unknown program.');
        }

        if (! in_array($program, Student::verifiedPrograms(), true)) {
            throw new NotFoundHttpException('Unknown program.');
        }

        return $program;
    }

    public function normalisePeriod(?string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'this_week';
    }

    public function normaliseTrendDays(int $trendDays): int
    {
        return in_array($trendDays, self::VALID_TREND_DAYS, true) ? $trendDays : 7;
    }
}
