<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class IssueExceptionDetail extends Model
{
    protected $fillable = [
        'user_count',
    ];

    protected function casts(): array
    {
        return [
            'user_count' => 'integer',
        ];
    }

    public function issue(): MorphOne
    {
        return $this->morphOne(Issue::class, 'detail');
    }
}
