<?php

namespace App\Modules\Reports\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One generic export class for all four reports in this module, rather
 * than four near-identical Maatwebsite Excel classes - every report
 * ultimately boils down to the same shape (rows of cell values, a
 * heading row, a sheet title), and the per-report structure (grouped
 * sections, subtotals) is flattened into that shape by each report's own
 * controller action before this class ever sees it.
 */
class ReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        protected array $rows,
        protected array $headings,
        protected string $title,
    ) {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->title;
    }
}
