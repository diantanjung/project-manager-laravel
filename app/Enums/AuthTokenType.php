<?php

namespace App\Enums;

enum AuthTokenType: string
{
    case Access = 'access';
    case Refresh = 'refresh';
}
