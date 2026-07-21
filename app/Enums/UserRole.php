<?php

namespace App\Enums;

enum UserRole: string
{
    case TeamMember = 'teamMember';
    case ProjectManager = 'projectManager';
    case ProductOwner = 'productOwner';
    case Admin = 'admin';
}
