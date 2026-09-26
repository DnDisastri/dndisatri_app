<?php

declare(strict_types=1);

namespace App\Domain\Dnd;

enum SkillProficiency: string
{
    case None = 'none';
    case Proficient = 'proficient';
    case Expert = 'expert';
}
