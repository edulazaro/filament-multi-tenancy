<?php

namespace App\Filament\Widgets;
 
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
 
class OrdersPerDayChart extends ChartWidget
{

    protected int | string | array $columnSpan = 'full';
 
    protected static ?string $maxHeight = '300px';
    protected static ?string $heading = 'Orders per day';
 
    protected function getData(): array
    {
        $data = Trend::model(Order::class)
            ->between(
                start: now()->subDays(60),
                end: now(),
            )
            ->perDay()
            ->count();
 
        return [
            'datasets' => [
                [
                    'label' => 'Orders per day',
                    'data' => $data->map(fn (TrendValue $value) => $value->aggregate),
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => $value->date),
        ];
    }
 
    protected function getType(): string
    {
        return 'bar';
    }
}