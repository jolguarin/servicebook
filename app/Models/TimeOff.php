<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeOff extends Model
{
    use HasFactory;

    protected $table = 'time_offs';

    protected $fillable = ['staff_profile_id', 'date', 'start_time', 'end_time', 'reason'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }
}