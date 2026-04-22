<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeedbackAnalysis extends Model
{
    use HasFactory;
    protected $table = 'feedback_analysis';

    protected $fillable = [
        'feedback_id',
        'issue',
        'priority',
        'department',
        'raw_response',
    ];
}