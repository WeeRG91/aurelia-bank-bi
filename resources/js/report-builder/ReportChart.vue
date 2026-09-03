<script setup lang="ts">
import { BarChart, LineChart } from 'echarts/charts';
import { GridComponent, LegendComponent, TooltipComponent } from 'echarts/components';
import * as echarts from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import type { ChartType, ReportPreviewRow } from './types';

echarts.use([
    BarChart,
    LineChart,
    GridComponent,
    LegendComponent,
    TooltipComponent,
    CanvasRenderer,
]);

const props = defineProps<{
    rows: ReportPreviewRow[];
    dimension: string;
    measure: string;
    seriesDimension: string | null;
    type: ChartType;
}>();

const chartElement = ref<HTMLDivElement | null>(null);

let chart: ReturnType<typeof echarts.init> | null = null;
let resizeObserver: ResizeObserver | null = null;

function numericValue(value: unknown): number | null {
    const parsed =
        typeof value === 'number'
            ? value
            : typeof value === 'string' && value.trim() !== ''
              ? Number(value)
              : Number.NaN;

    return Number.isFinite(parsed) ? parsed : null;
}

function categoryValue(row: ReportPreviewRow): string {
    return String(row[props.dimension] ?? 'Unknown');
}

function seriesValue(row: ReportPreviewRow): string {
    if (props.seriesDimension === null) {
        return props.measure;
    }

    return String(row[props.seriesDimension] ?? 'Unknown');
}

function buildSeries(name: string, data: Array<number | null>) {
    if (props.type === 'line') {
        return {
            name,
            type: 'line' as const,
            data,
            connectNulls: false,
            showSymbol: data.length <= 30,
        };
    }

    return {
        name,
        type: 'bar' as const,
        data,
    };
}

function renderChart(): void {
    if (chart === null) {
        return;
    }

    const categories = [...new Set(props.rows.map(categoryValue))];

    const seriesNames =
        props.seriesDimension === null
            ? [props.measure]
            : [...new Set(props.rows.map(seriesValue))];

    const series = seriesNames.map((name) => {
        const values = categories.map((category) => {
            const row = props.rows.find(
                (candidate) =>
                    categoryValue(candidate) === category && seriesValue(candidate) === name,
            );

            return row === undefined ? null : numericValue(row[props.measure]);
        });

        return buildSeries(name, values);
    });

    chart.setOption(
        {
            animationDuration: 300,
            tooltip: {
                trigger: 'axis',
            },
            legend: {
                show: series.length > 1,
                type: 'scroll',
            },
            grid: {
                left: 24,
                right: 24,
                top: series.length > 1 ? 60 : 30,
                bottom: 30,
                containLabel: true,
            },
            xAxis: {
                type: 'category',
                data: categories,
                axisLabel: {
                    hideOverlap: true,
                },
            },
            yAxis: {
                type: 'value',
            },
            series,
        },
        true,
    );
}

onMounted(async (): Promise<void> => {
    await nextTick();

    if (chartElement.value === null) {
        return;
    }

    chart = echarts.init(chartElement.value);

    resizeObserver = new ResizeObserver(() => {
        chart?.resize();
    });

    resizeObserver.observe(chartElement.value);

    renderChart();
});

watch(
    () => [props.rows, props.dimension, props.measure, props.seriesDimension, props.type],
    renderChart,
    {
        deep: true,
    },
);

onBeforeUnmount((): void => {
    resizeObserver?.disconnect();
    chart?.dispose();
});
</script>

<template>
    <div
        ref="chartElement"
        class="h-96 w-full"
        role="img"
        :aria-label="`${type} chart of ${measure} by ${dimension}`"
    />
</template>
