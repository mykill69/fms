<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionLog extends Model
{
    use HasFactory;

    protected $table = 'submission_logs';

    protected $fillable = [
        'ip_address',
        'user_agent',
        'session_id',
        'department',
        'device_fingerprint',
        'feedback_id',
        'rating',
        'status',
        'block_reason'
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id');
    }

    public function scopeAllowed($query)
    {
        return $query->where('status', 'allowed');
    }

    public function scopeBlocked($query)
    {
        return $query->where('status', 'blocked');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLastHour($query)
    {
        return $query->where('created_at', '>=', now()->subHour());
    }

    public function scopeForDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    public function scopeFromIp($query, $ip)
    {
        return $query->where('ip_address', $ip);
    }
}