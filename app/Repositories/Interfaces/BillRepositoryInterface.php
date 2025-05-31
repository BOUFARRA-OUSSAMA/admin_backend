<?php

namespace App\Repositories\Interfaces;

use App\Models\Bill;
use Illuminate\Pagination\LengthAwarePaginator;

interface BillRepositoryInterface
{
    /**
     * Get all bills with optional filtering and pagination
     *
     * @param array $filters
     * @param array $relationships
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], array $relationships = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Get a bill by ID with optional relationships
     *
     * @param int $id
     * @param array $relationships
     * @return Bill|null
     */
    public function findById(int $id, array $relationships = []): ?Bill;
    
    /**
     * Create a new bill
     *
     * @param array $data
     * @return Bill
     */
    public function create(array $data): Bill;
    
    /**
     * Update a bill
     *
     * @param Bill $bill
     * @param array $data
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill;
    
    /**
     * Delete a bill
     *
     * @param Bill $bill
     * @return bool
     */
    public function delete(Bill $bill): bool;
    
    /**
     * Get bills for a specific patient
     *
     * @param int $patientId
     * @param array $filters
     * @param array $relationships
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByPatientId(int $patientId, array $filters = [], array $relationships = [], int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Generate a unique bill number
     *
     * @return string
     */
    public function generateBillNumber(): string;

    /**
     * Get bills within a specific date range
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getBillsByDateRange(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection;

    /**
     * Get total revenue from all bills
     *
     * @return float
     */
    public function getTotalRevenue(): float;

    /**
     * Get service analytics data
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getServiceAnalytics(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection;

    /**
     * Get doctor revenue analytics data
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getDoctorRevenueAnalytics(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection;
}