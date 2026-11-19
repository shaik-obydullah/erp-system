<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Support\Str;

/**
 * A product that already has stock records must not be deleted. The stock
 * carries quantity, cost and pricing history that would be orphaned, so the
 * product should be marked inactive instead.
 */
class ProductDeletionGuard
{
    /**
     * Return the reasons why the product cannot be deleted.
     *
     * @return list<string>
     */
    public function blockers(Product $product): array
    {
        $blockers = [];

        $stocks = Stock::where('fk_product_id', $product->id)->count();
        if ($stocks > 0) {
            $blockers[] = $stocks.' '.Str::plural('stock record', $stocks);
        }

        return $blockers;
    }

    /**
     * Build the user-facing message for a set of blockers.
     *
     * @param  list<string>  $blockers
     */
    public function message(array $blockers): string
    {
        return 'This product cannot be deleted because it is used in '
            .$this->join($blockers)
            .'. Mark the product as inactive instead.';
    }

    /**
     * @param  list<string>  $blockers
     */
    private function join(array $blockers): string
    {
        if (count($blockers) <= 1) {
            return (string) ($blockers[0] ?? '');
        }

        $last = array_pop($blockers);

        return implode(', ', $blockers).' and '.$last;
    }
}
