<?php

namespace App\Repositories\Eloquent;

use App\Models\Bill;
use App\Repositories\Interfaces\BillRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BillRepository implements BillRepositoryInterface
{
    /**
     * @var Bill
     */
    protected $model;

    /**
     * BillRepository constructor.
     *
     * @param Bill $model
     */
    public function __construct(Bill $model)
    {
        $this->model = $model;
    }

    /**
     * Get all bills with optional filtering and pagination
     *
     * @param array $filters
     * @param array $relationships
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], array $relationships = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        // Load relationships if provided
        if (!empty($relationships)) {
            $query->with($relationships);
        }
        
        // Apply filters
        if (isset($filters['doctor_id'])) {
            $query->where('doctor_user_id', $filters['doctor_id']);
        }
        
        if (isset($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }
        
        if (isset($filters['amount_min'])) {
            $query->where('amount', '>=', $filters['amount_min']);
        }
        
        if (isset($filters['amount_max'])) {
            $query->where('amount', '<=', $filters['amount_max']);
        }
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'issue_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = ['issue_date', 'amount', 'bill_number'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Get a bill by ID with optional relationships
     *
     * @param int $id
     * @param array $relationships
     * @return Bill|null
     */
    public function findById(int $id, array $relationships = []): ?Bill
    {
        $query = $this->model->newQuery();
        
        if (!empty($relationships)) {
            $query->with($relationships);
        }
        
        return $query->find($id);
    }
    
    /**
     * Create a new bill
     *
     * @param array $data
     * @return Bill
     */
    public function create(array $data): Bill
    {
        return $this->model->create($data);
    }
    
    /**
     * Update a bill
     *
     * @param Bill $bill
     * @param array $data
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill
    {
        $bill->update($data);
        return $bill;
    }
    
    /**
     * Delete a bill
     *
     * @param Bill $bill
     * @return bool
     */
    public function delete(Bill $bill): bool
    {
        return $bill->delete();
    }
    
    /**
     * Get bills for a specific patient
     *
     * @param int $patientId
     * @param array $filters
     * @param array $relationships
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByPatientId(int $patientId, array $filters = [], array $relationships = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('patient_id', $patientId);
        
        // Load relationships if provided
        if (!empty($relationships)) {
            $query->with($relationships);
        }
        
        // Apply filters
        if (isset($filters['date_from'])) {
            $query->where('issue_date', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('issue_date', '<=', $filters['date_to']);
        }
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'issue_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = ['issue_date', 'amount', 'bill_number'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection === 'asc' ? 'asc' : 'desc');
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Generate a unique bill number
     *
     * @return string
     */
    public function generateBillNumber(): string
    {
        $prefix = 'BILL-';
        $year = date('Y');
        $month = date('m');
        
        $latestBill = $this->model->where('bill_number', 'like', "{$prefix}{$year}{$month}%")
            ->orderBy('id', 'desc')
            ->first();
        
        if ($latestBill) {
            $lastNumber = (int) substr($latestBill->bill_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get bills within a specific date range
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getBillsByDateRange(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection
    {
        return $this->model->whereBetween('issue_date', [
            $fromDate->toDateString(), 
            $toDate->toDateString()
        ])->get();
    }

    /**
     * Get total revenue from all bills
     *
     * @return float
     */
    public function getTotalRevenue(): float
    {
        return $this->model->sum('amount');
    }

    /**
     * Get service analytics data
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getServiceAnalytics(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection
    {
        return DB::table('bill_items')
            ->join('bills', 'bill_items.bill_id', '=', 'bills.id')
            ->whereBetween('bills.issue_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->select(
                'bill_items.service_type',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(bill_items.total) as total_revenue'),
                DB::raw('AVG(bill_items.price) as average_price')
            )
            ->groupBy('bill_items.service_type')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get doctor revenue analytics data
     *
     * @param \Carbon\Carbon $fromDate
     * @param \Carbon\Carbon $toDate
     * @return \Illuminate\Support\Collection
     */
    public function getDoctorRevenueAnalytics(\Carbon\Carbon $fromDate, \Carbon\Carbon $toDate): \Illuminate\Support\Collection
    {
        return DB::table('bills')
            ->join('users', 'bills.doctor_user_id', '=', 'users.id')
            ->whereBetween('bills.issue_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->select(
                'bills.doctor_user_id as doctor_id',
                'users.name as doctor_name',
                DB::raw('SUM(bills.amount) as total_revenue'),
                DB::raw('COUNT(bills.id) as bill_count')
            )
            ->groupBy('bills.doctor_user_id', 'users.name')
            ->having(DB::raw('COUNT(bills.id)'), '>', 0) // Fixed: Use raw expression instead of alias
            ->orderBy('total_revenue', 'desc')
            ->get();
    }
}