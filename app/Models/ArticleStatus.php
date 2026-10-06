<?php

namespace App\Models;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Archived = 'archived';

    public function badge(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'neutral',
            self::Submitted => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Published => 'success',
        };
    }
}