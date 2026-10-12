<?php

namespace App\Providers;

use App\Analytics\Auditing\AuditRecorder;
use App\Analytics\Auditing\DatabaseAuditRecorder;
use App\Analytics\Dashboards\DashboardCacheGeneration;
use App\Analytics\Dashboards\RepositoryDashboardCacheGeneration;
use App\Analytics\Warehouse\Aggregates\DailyTransactionAggregateRefresher;
use App\Analytics\Warehouse\Aggregates\DatabaseDailyTransactionAggregateRefresher;
use App\Analytics\Warehouse\Dimensions\AccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\AccountDimensionWriter;
use App\Analytics\Warehouse\Dimensions\BranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\BranchDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DatabaseAccountDimensionSource;
use App\Analytics\Warehouse\Dimensions\DatabaseAccountDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DatabaseBranchDimensionSource;
use App\Analytics\Warehouse\Dimensions\DatabaseBranchDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DatabaseDateDimensionWriter;
use App\Analytics\Warehouse\Dimensions\DateDimensionWriter;
use App\Analytics\Warehouse\Etl\BatchedTransactionStagingLoader;
use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingBatchWriter;
use App\Analytics\Warehouse\Etl\DatabaseTransactionStagingSource;
use App\Analytics\Warehouse\Etl\EloquentEtlRunStore;
use App\Analytics\Warehouse\Etl\EtlRunStore;
use App\Analytics\Warehouse\Etl\TransactionStagingBatchWriter;
use App\Analytics\Warehouse\Etl\TransactionStagingLoader;
use App\Analytics\Warehouse\Etl\TransactionStagingSource;
use App\Analytics\Warehouse\Facts\DatabaseTransactionFactSource;
use App\Analytics\Warehouse\Facts\DatabaseTransactionFactWriter;
use App\Analytics\Warehouse\Facts\TransactionFactSource;
use App\Analytics\Warehouse\Facts\TransactionFactWriter;
use App\Models\AnalyticsAuditEvent;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\ReportExport;
use App\Models\SavedReport;
use App\Models\ScheduledReport;
use App\Policies\AnalyticsAuditEventPolicy;
use App\Policies\BranchPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\ReportExportPolicy;
use App\Policies\SavedReportPolicy;
use App\Policies\ScheduledReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            AuditRecorder::class,
            DatabaseAuditRecorder::class,
        );

        $this->app->bind(
            EtlRunStore::class,
            EloquentEtlRunStore::class,
        );

        $this->app->bind(
            TransactionStagingSource::class,
            DatabaseTransactionStagingSource::class,
        );

        $this->app->bind(
            TransactionStagingLoader::class,
            BatchedTransactionStagingLoader::class,
        );

        $this->app->bind(
            TransactionStagingBatchWriter::class,
            DatabaseTransactionStagingBatchWriter::class,
        );

        $this->app->bind(
            DateDimensionWriter::class,
            DatabaseDateDimensionWriter::class,
        );

        $this->app->bind(
            BranchDimensionSource::class,
            DatabaseBranchDimensionSource::class,
        );

        $this->app->bind(
            BranchDimensionWriter::class,
            DatabaseBranchDimensionWriter::class,
        );

        $this->app->bind(
            AccountDimensionSource::class,
            DatabaseAccountDimensionSource::class,
        );

        $this->app->bind(
            AccountDimensionWriter::class,
            DatabaseAccountDimensionWriter::class,
        );

        $this->app->bind(
            TransactionFactSource::class,
            DatabaseTransactionFactSource::class,
        );

        $this->app->bind(
            TransactionFactWriter::class,
            DatabaseTransactionFactWriter::class,
        );

        $this->app->bind(
            DailyTransactionAggregateRefresher::class,
            DatabaseDailyTransactionAggregateRefresher::class,
        );

        $this->app->bind(
            DashboardCacheGeneration::class,
            RepositoryDashboardCacheGeneration::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(SavedReport::class, SavedReportPolicy::class);
        Gate::policy(ReportExport::class, ReportExportPolicy::class);
        Gate::policy(ScheduledReport::class, ScheduledReportPolicy::class);
        Gate::policy(AnalyticsAuditEvent::class, AnalyticsAuditEventPolicy::class);
    }
}
