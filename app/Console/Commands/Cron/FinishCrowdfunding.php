<?php

namespace App\Console\Commands\Cron;

use App\Jobs\RefundCrowdfundingOrders;
use App\Models\Order\Order;
use App\Models\Product\Crowdfunding;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FinishCrowdfunding extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cron:finish-crowdfunding';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '结束众筹';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Crowdfunding::query()
            ->where('end_at', '<=', Carbon::now())
            ->where('status', Crowdfunding::STATUS_FUNDING)
            ->get()
            ->each(function (Crowdfunding $crowdfunding) {
                if ($crowdfunding->total_amount > $crowdfunding->target_amount) {
                    $this->crowdfundingSucceed($crowdfunding);
                } else {
                    $this->crowdfundingFailed($crowdfunding);
                }
            });
    }

    protected function crowdfundingSucceed(Crowdfunding $crowdfunding)
    {
        $crowdfunding->update([
            'status' => Crowdfunding::STATUS_SUCCESS,
        ]);
    }

    protected function crowdfundingFailed(Crowdfunding $crowdfunding)
    {
        $crowdfunding->update([
            'status' => Crowdfunding::STATUS_FAIL,
        ]);

        RefundCrowdfundingOrders::dispatch($crowdfunding);
    }
}
