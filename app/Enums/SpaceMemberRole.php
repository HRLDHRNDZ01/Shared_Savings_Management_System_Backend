<?php

namespace App\Enums;

enum SpaceMemberRole: string
{
    case Owner = 'owner';
    case Member = 'member';
}
