<?php
namespace App\Repositories;

interface ReportRepositoryInterface
{
    public function getReportByPeriod(int $month, int $year);
    public function updateReportItems(int $reportId, array $itemsData);
    public function getAccountantStats($month, int $year);
}