<script setup lang="ts">
import { BarChart, LineChart } from 'echarts/charts';
import { DatasetComponent, GridComponent, TooltipComponent } from 'echarts/components';
import * as echarts from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import type { ChartType, ReportPreviewRow } from './types';

echarts.use([
    BarChart,
    LineChart,
    DatasetComponent,
    GridComponent,
    TooltipComponent,
    CanvasRenderer,
]);

const props = defineProps<{
    rows: ReportPreviewRow[];
    dimension: string;
    measure: string;
    type: ChartType;
}>();

const chartElement = ref<HTMLDivElement | null>(null);

let chart: ReturnType<typeof echarts.init> | null = null;
let resizeObserver: ResizeObserver | null = null;

function numericValue(value: unknown): number | null {
    if (typeof value === 'number') {
        return Number.isFinite(value) ? value : null;
    }

    if (typeof value === 'string' && value.trim() !== '') {
        const parsed = Number(value);

        return Number.isFinite(parsed) ? parsed : null;
    }

    return null;
}

function renderChart(): void {
    if (chart === null) {
        return;
    }

    const source = props.rows.map((row) => ({
        [props.dimension]: String(row[props.dimension] ?? 'Unknown'),
        [props.measure]: numericValue(row[props.measure]),
    }));

    chart.setOption(
        {
            animationDuration: 300,
            tooltip: {
                trigger: 'axis',
            },
            grid: {
                left: 24,
                right: 24,
                top: 30,
                bottom: 30,
                containLabel: true,
            },
            dataset: {
                dimensions: [props.dimension, props.measure],
                source,
            },
            xAxis: {
                type: 'category',
                axisLabel: {
                    hideOverlap: true,
                },
            },
            yAxis: {
                type: 'value',
            },
            series:
                props.type === 'line'
                    ? [
                          {
                              type: 'line',
                              encode: {
                                  x: props.dimension,
                                  y: props.measure,
                              },
                              smooth: false,
                              showSymbol: source.length <= 30,
                          },
                      ]
                    : [
                          {
                              type: 'bar',
                              encode: {
                                  x: props.dimension,
                                  y: props.measure,
                              },
                          },
                      ],
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

watch(() => [props.rows, props.dimension, props.measure, props.type], renderChart, {
    deep: true,
});

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
