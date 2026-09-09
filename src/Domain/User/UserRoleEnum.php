<?php

namespace App\Domain\User;

enum UserRoleEnum: string
{
    case COMPANY_ADMIN = 'ROLE_COMPANY_ADMIN';
    case ADMIN = 'ROLE_ADMIN';
}
