<?php

namespace App\Services\Qontak;

use Illuminate\Support\Facades\DB;

class QontakDashboardService
{
    public function getChartWonByActor($metric, $startDateApi, $endDateApi): array
    {
        $aggregate = $metric === 'amount' ? 'SUM(amount)' : 'COUNT(id)';
        $rawData = DB::table('qontak_deals')
            ->select(
                'creator_name',
                DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as date"),
                DB::raw("$aggregate as total")
            )
            ->whereBetween('created_at', [$startDateApi, $endDateApi])
            ->groupBy('creator_name', DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d')"))
            ->orderBy('date', 'asc')
            ->get();
        $period = \Carbon\CarbonPeriod::create($startDateApi, $endDateApi);
        $allDates = [];
        foreach ($period as $date) {
            $allDates[] = $date->format('Y-m-d');
        }
        $creators = $rawData->pluck('creator_name')->unique()->values()->toArray();

        $series = [];
        $symbols = ['circle', 'diamond', 'rect', 'triangle'];

        foreach ($creators as $index => $creator) {
            $dataPoints = [];

            foreach ($allDates as $date) {
                $record = $rawData->where('creator_name', $creator)
                    ->where('date', $date)
                    ->first();

                $dataPoints[] = $record ? (float) $record->total : 0;
            }

            $series[] = [
                'name' => $creator,
                'type' => 'line',
                'data' => $dataPoints,
                'smooth' => false,
                'symbol' => $symbols[$index % count($symbols)],
                'symbolSize' => 8,
                'lineStyle' => ['width' => 2]
            ];
        }

        $dealwonchart = [
            'categories' => $allDates,
            'series' => $series,
            'legend' => $creators
        ];
        return $dealwonchart;
    }
}
