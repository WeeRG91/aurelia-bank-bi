import './bootstrap';

import type { ReportBuilderBootstrap } from './report-builder/types';
import type { DashboardBootstrap } from '@/dashboard/types.ts';

async function mountReportBuilder(): Promise<void> {
    const root = document.querySelector<HTMLElement>('[data-report-builder]');

    const bootstrapElement = document.getElementById('report-builder-bootstrap');

    if (root === null || bootstrapElement === null) {
        return;
    }

    const bootstrap = JSON.parse(bootstrapElement.textContent ?? '') as ReportBuilderBootstrap;

    const [{ createApp }, { default: ReportBuilderApp }] = await Promise.all([
        import('vue'),
        import('./report-builder/ReportBuilderApp.vue'),
    ]);

    createApp(ReportBuilderApp, {
        bootstrap,
    }).mount(root);
}

async function mountAnalyticsDashboard(): Promise<void> {
    const root = document.querySelector<HTMLElement>('[data-analytics-dashboard]');

    const bootstrapElement = document.getElementById('analytics-dashboard-bootstrap');

    if (root === null || bootstrapElement === null) {
        return;
    }

    const bootstrap = JSON.parse(bootstrapElement.textContent ?? '') as DashboardBootstrap;

    const [{ createApp }, { default: DashboardApp }] = await Promise.all([
        import('vue'),
        import('./dashboard/DashboardApp.vue'),
    ]);

    createApp(DashboardApp, {
        bootstrap,
    }).mount(root);
}

void mountReportBuilder();
void mountAnalyticsDashboard();
