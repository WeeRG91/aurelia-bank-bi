<?php

namespace App\Analytics\Auditing;

enum AuditSubjectType: string
{
    case DATASET = 'dataset';
    case SAVED_REPORT = 'saved_report';
    case REPORT_EXPORT = 'report_export';
    case SCHEDULED_REPORT = 'scheduled_report';
}
