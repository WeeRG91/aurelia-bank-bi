import './bootstrap';

import type { ReportBuilderBootstrap } from './report-builder/types';

async function mountReportBuilder(): Promise<void> {
    const root = document.querySelector<HTMLElement>(
        '[data-report-builder]',
    );

    const bootstrapElement = document.getElementById(
        'report-builder-bootstrap',
    );

    if (root === null || bootstrapElement === null) {
        return;
    }

    const bootstrap = JSON.parse(
        bootstrapElement.textContent ?? '',
    ) as ReportBuilderBootstrap;

    const [
        { createApp },
        { default: ReportBuilderApp },
    ] = await Promise.all([
        import('vue'),
        import('./report-builder/ReportBuilderApp.vue'),
    ]);

    createApp(ReportBuilderApp, {
        bootstrap,
    }).mount(root);
}

void mountReportBuilder();
