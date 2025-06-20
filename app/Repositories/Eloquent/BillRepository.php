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
        $query = $this->model->newQuery();
        
        // Apply filters using the new method
        $query = $this->applyFilters($query, $filters);
        
        // Load relationships
        if (!empty($relationships)) {
            $query->with($relationships);
        }
        
        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        // Validate sort column to prevent SQL injection
        $allowedSortColumns = ['id', 'bill_number', 'amount', 'issue_date', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
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

    /**
     * Get bills with filtering and pagination
     *
     * @param array $filters
     * @param array $relations
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBills(array $filters = [], array $relations = [], int $perPage = 15): LengthAwarePaginator
    {
        // If no relations specified, use defaults
        if (empty($relations)) {
            $relations = ['patient', 'doctor', 'items'];
        }
        
        return $this->getAllWithFilters($filters, $relations, $perPage);
    }
    
    /**
     * Get the repository's model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Apply filters to the query (Azure PostgreSQL optimized)
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        // Handle patient_id filter (comma-separated support)
        if (!empty($filters['patient_id'])) {
            if (is_string($filters['patient_id']) && strpos($filters['patient_id'], ',') !== false) {
                // Convert comma-separated string to array of integers
                $patientIds = array_map('intval', array_filter(explode(',', $filters['patient_id']), 'is_numeric'));
                if (!empty($patientIds)) {
                    $query->whereIn('patient_id', $patientIds);
                }
            } else {
                $query->where('patient_id', (int)$filters['patient_id']);
            }
        }
        
        // Handle doctor_user_id filter (comma-separated support)
        if (!empty($filters['doctor_user_id'])) {
            if (is_string($filters['doctor_user_id']) && strpos($filters['doctor_user_id'], ',') !== false) {
                $doctorIds = array_map('intval', array_filter(explode(',', $filters['doctor_user_id']), 'is_numeric'));
                if (!empty($doctorIds)) {
                    $query->whereIn('doctor_user_id', $doctorIds);
                }
            } else {
                $query->where('doctor_user_id', (int)$filters['doctor_user_id']);
            }
        }
        
        // Handle legacy doctor_id filter (maps to doctor_user_id)
        if (!empty($filters['doctor_id'])) {
            if (is_string($filters['doctor_id']) && strpos($filters['doctor_id'], ',') !== false) {
                $doctorIds = array_map('intval', array_filter(explode(',', $filters['doctor_id']), 'is_numeric'));
                if (!empty($doctorIds)) {
                    $query->whereIn('doctor_user_id', $doctorIds);
                }
            } else {
                $query->where('doctor_user_id', (int)$filters['doctor_id']);
            }
        }
        
        // Handle doctor name filter (comma-separated support)
        if (!empty($filters['doctor_name'])) {
            if (strpos($filters['doctor_name'], ',') !== false) {
                $doctorNames = array_map('trim', explode(',', $filters['doctor_name']));
                $query->whereHas('doctor', function($q) use ($doctorNames) {
                    $q->where(function($nameQuery) use ($doctorNames) {
                        foreach ($doctorNames as $name) {
                            $nameQuery->orWhere('name', 'ILIKE', '%' . $name . '%'); // ILIKE for case-insensitive
                        }
                    });
                });
            } else {
                $query->whereHas('doctor', function($q) use ($filters) {
                    $q->where('name', 'ILIKE', '%' . $filters['doctor_name'] . '%');
                });
            }
        }
        
        // Handle date filtering with proper casting
        if (!empty($filters['date_from'])) {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }
        
        // Handle amount filtering with proper numeric casting
        if (!empty($filters['amount_min'])) {
            $query->where('amount', '>=', (float)$filters['amount_min']);
        }
        
        if (!empty($filters['amount_max'])) {
            $query->where('amount', '<=', (float)$filters['amount_max']);
        }
        
        // Handle payment method filter (comma-separated support)
        if (!empty($filters['payment_method'])) {
            if (strpos($filters['payment_method'], ',') !== false) {
                $paymentMethods = array_map('trim', explode(',', $filters['payment_method']));
                $query->whereIn('payment_method', $paymentMethods);
            } else {
                $query->where('payment_method', $filters['payment_method']);
            }
        }
        
        // Handle service type filter (comma-separated support with relationship)
        if (!empty($filters['service_type'])) {
            if (strpos($filters['service_type'], ',') !== false) {
                $serviceTypes = array_map('trim', explode(',', $filters['service_type']));
                $query->whereHas('items', function($itemsQuery) use ($serviceTypes) {
                    $itemsQuery->whereIn('service_type', $serviceTypes);
                });
            } else {
                $query->whereHas('items', function($itemsQuery) use ($filters) {
                    $itemsQuery->where('service_type', $filters['service_type']);
                });
            }
        }
        
        // Handle global search across multiple fields
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($searchQuery) use ($searchTerm) {
                $searchQuery->where('bill_number', 'ILIKE', '%' . $searchTerm . '%')
                            ->orWhereHas('patient.user', function($patientQuery) use ($searchTerm) {
                                $patientQuery->where('name', 'ILIKE', '%' . $searchTerm . '%');
                            })
                            ->orWhereHas('doctor', function($doctorQuery) use ($searchTerm) {
                                $doctorQuery->where('name', 'ILIKE', '%' . $searchTerm . '%');
                            });
            });
        }
        
        return $query;
    }
}