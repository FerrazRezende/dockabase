<?php

declare(strict_types=1);

namespace App\Enums;

enum UserActivityTypeEnum: string
{
    case StatusChanged = 'status_changed';
    case DatabaseCreated = 'database_created';
    case CredentialCreated = 'credential_created';
    case PageView = 'page_view';
}
