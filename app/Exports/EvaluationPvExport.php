<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class EvaluationPvExport implements FromView, WithTitle
{
    public function __construct(
        private Collection $rows,
        private array $meta,
    ) {
    }

    public function view(): View
    {
        return view('evaluations.exports.excel', [
            'rows' => $this->rows,
            'meta' => $this->meta,
        ]);
    }

    public function title(): string
    {
        return 'PV Notes';
    }
}
