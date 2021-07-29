<?php

namespace App\Listeners;

use App\Events\OrderReviewed;
use App\Models\Order\Item;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use DB;

class UpdateProductRating implements ShouldQueue
{
    public function handle(OrderReviewed $event)
    {
        $items = $event->getOrder()->items()->with(['product'])->get();

        foreach ($items as $item) {
            $result = Item::query()
                ->where('product_id', $item->product_id)
                ->whereNotNull('reviewed_at')
                ->whereHas('order', function ($query) {
                    $query->where('paid', true);
                })
                ->first([
                    DB::raw('count(*) as review_count'),
                    DB::raw('avg(rating) as rating'),
                ]);

            $item->product->update([
                'rating' => $result->rating,
                'review_count' => $result->review_count,
            ]);
        }
    }
}
