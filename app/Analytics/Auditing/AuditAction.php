<?php

namespace App\Analytics\Auditing;

enum AuditAction: string
{
    case DATASET_INSPECTED = 'dataset_inspected';
    case AUDIT_TRAIL_VIEWED = 'audit_trail_viewed';
    case REPORT_OPENED = 'report_opened';
    case REPORT_PREVIEWED = 'report_previewed';
    case REPORT_CREATED = 'report_created';
    case REPORT_UPDATED = 'report_updated';
    case REPORT_DELETED = 'report_deleted';
    case REPORT_RESTORED = 'report_restored';
    case REPORT_DUPLICATED = 'report_duplicated';
    case EXPORT_QUEUED = 'export_queued';
    case EXPORT_STARTED = 'export_started';
    case EXPORT_COMPLETED = 'export_completed';
    case EXPORT_FAILED = 'export_failed';
    case EXPORT_DOWNLOADED = 'export_downloaded';
    case SCHEDULE_CREATED = 'schedule_created';
    case SCHEDULE_PAUSED = 'schedule_paused';
    case SCHEDULE_RESUMED = 'schedule_resumed';
    case SCHEDULE_DISPATCHED = 'schedule_dispatched';
    case SCHEDULE_AUTO_PAUSED = 'schedule_auto_paused';
}
