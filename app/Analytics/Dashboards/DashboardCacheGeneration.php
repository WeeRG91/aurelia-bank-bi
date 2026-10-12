<?php

namespace App\Analytics\Dashboards;

interface DashboardCacheGeneration
{
    public function current(): string;

    public function rotate(): string;
}
