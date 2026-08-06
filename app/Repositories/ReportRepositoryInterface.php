<?php

namespace App\Repositories;

use App\Models\Report;

interface ReportRepositoryInterface
{
    public function getReportByPeriod(int $month, int $year);
    public function updateReportItems(int $reportId, array $itemsData);
    public function seedDefaultItems(Report $report);
    public function getAccountantStats($month, int $year);
}
