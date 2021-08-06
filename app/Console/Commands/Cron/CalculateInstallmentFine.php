<?php

namespace App\Console\Commands\Cron;

use App\Models\Installment\Item;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculateInstallmentFine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:calculate-installment-fine';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '计算分期订单逾期费用';

    public function handle()
    {
        Item::query()
            ->with(['installment'])
            ->where('due_at', '<=', Carbon::now())
            ->where('paid', false)
            ->chunkById(1000, function ($items) {
                foreach ($items as $item) {
                    $overdueDays = Carbon::now()->diffInDays($item->due_at);

                    $base = big_number($item->base)->add($item->feee)->getValue();

                    $fine = big_number($base)
                        ->multiply($overdueDays)
                        ->multiply($item->installment->fine_rate)
                        ->divide(100)
                        ->getValue();
                    
                    $fine = big_number($fine)->compareTo($base) === 1 ? $base : $fine;

                    $item->update([
                        'fine' => $fine,
                    ]);
                }
            });
    }
}
