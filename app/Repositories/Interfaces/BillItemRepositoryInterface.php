<?php

namespace App\Repositories\Interfaces;

use App\Models\BillItem;
use Illuminate\Database\Eloquent\Collection;

interface BillItemRepositoryInterface
{
    /**
     * Get all items for a bill
     *
     * @param int $billId
     * @return Collection
     */
    public function getByBillId(int $billId): Collection;
    
    /**
     * Create a new bill item
     *
     * @param array $data
     * @return BillItem
     */
    public function create(array $data): BillItem;
    
    /**
     * Update a bill item
     *
     * @param BillItem $item
     * @param array $data
     * @return BillItem
     */
    public function update(BillItem $item, array $data): BillItem;
    
    /**
     * Delete a bill item
     *
     * @param BillItem $item
     * @return bool
     */
    public function delete(BillItem $item): bool;
    
    /**
     * Delete all items for a bill
     *
     * @param int $billId
     * @return bool
     */
    public function deleteByBillId(int $billId): bool;
    
    /**
     * Calculate the total amount for a bill based on its items
     *
     * @param int $billId
     * @return float
     */
    public function calculateBillTotal(int $billId): float;
}