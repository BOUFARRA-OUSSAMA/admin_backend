<?php

namespace App\Repositories\Eloquent;

use App\Models\BillItem;
use App\Repositories\Interfaces\BillItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class BillItemRepository implements BillItemRepositoryInterface
{
    /**
     * @var BillItem
     */
    protected $model;

    /**
     * BillItemRepository constructor.
     *
     * @param BillItem $model
     */
    public function __construct(BillItem $model)
    {
        $this->model = $model;
    }

    /**
     * Get all items for a bill
     *
     * @param int $billId
     * @return Collection
     */
    public function getByBillId(int $billId): Collection
    {
        return $this->model->where('bill_id', $billId)->get();
    }
    
    /**
     * Create a new bill item
     *
     * @param array $data
     * @return BillItem
     */
    public function create(array $data): BillItem
    {
        return $this->model->create($data);
    }
    
    /**
     * Update a bill item
     *
     * @param BillItem $item
     * @param array $data
     * @return BillItem
     */
    public function update(BillItem $item, array $data): BillItem
    {
        $item->update($data);
        return $item->fresh();
    }
    
    /**
     * Delete a bill item
     *
     * @param BillItem $item
     * @return bool
     */
    public function delete(BillItem $item): bool
    {
        return $item->delete();
    }
    
    /**
     * Delete all items for a bill
     *
     * @param int $billId
     * @return bool
     */
    public function deleteByBillId(int $billId): bool
    {
        return $this->model->where('bill_id', $billId)->delete();
    }
    
    /**
     * Calculate the total amount for a bill based on its items
     *
     * @param int $billId
     * @return float
     */
    public function calculateBillTotal(int $billId): float
    {
        return $this->model->where('bill_id', $billId)->sum('total');
    }
}