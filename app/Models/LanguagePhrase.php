<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Ein wichtiger Satz in einer Landessprache (siehe App\Support\Phrases\PhraseCatalog). */
class LanguagePhrase extends Model
{
    public const STATUS_AI_DRAFT = 'ai_draft';

    public const STATUS_REVIEWED = 'reviewed';

    protected $fillable = ['language', 'key', 'text', 'romanization', 'status'];
}
