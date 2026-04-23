<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class FeedbackReportExport implements FromCollection, WithHeadings, WithTitle
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'Feedback Report';
    }

    public function headings(): array
    {
        return ['Date', 'Name', 'Role', 'Department', 'Type', 'Rating', 'Feedback'];
    }

    public function collection()
    {
        return $this->data['feedbacks']->map(function ($f) {
            return [
                $f->created_at->format('Y-m-d H:i'),
                $f->name,
                $f->role,
                $f->department,
                $f->type,
                $f->rating,
                $f->feedback
            ];
        });
    }
}