<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Patient;
use App\Repositories\Interfaces\BillRepositoryInterface;
use App\Repositories\Interfaces\BillItemRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Facades\LogActivity;
use Illuminate\Support\Facades\Log;


class BillService
{
    /**
     * @var BillRepositoryInterface
     */
    protected $billRepository;
    
    /**
     * @var BillItemRepositoryInterface
     */
    protected $billItemRepository;

    /**
     * BillService constructor.
     *
     * @param BillRepositoryInterface $billRepository
     * @param BillItemRepositoryInterface $billItemRepository
     */
    public function __construct(
        BillRepositoryInterface $billRepository,
        BillItemRepositoryInterface $billItemRepository
    ) {
        $this->billRepository = $billRepository;
        $this->billItemRepository = $billItemRepository;
    }

    /**
     * Get all bills with filtering and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllBills(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Change from getAll() to one of the methods that actually exists
        return $this->billRepository->getBills($filters, $perPage);
    }
    
    /**
     * Get a specific bill by ID
     *
     * @param int $id
     * @return Bill|null
     */
    public function getBillById(int $id, array $relations = []): ?Bill
    {
        // Define default relations that are almost always needed for a single bill view
    // Ensure 'doctor.doctor' is loaded to get the specialty from the Doctor model
    $defaultRelations = ['patient.user', 'doctor.doctor', 'items'];
    // Merge default relations with any additional specific relations requested
    $allRelations = array_unique(array_merge($defaultRelations, $relations));
    
    return $this->billRepository->findById($id, $allRelations);
}
    
    
    /**
     * Create a new bill with its items
     *
     * @param array $data
     * @return Bill
     */
    public function createBill(array $data): Bill
    {
        return DB::transaction(function () use ($data) {
            // Set default bill number if not provided
            if (!isset($data['bill_number'])) {
                $data['bill_number'] = $this->billRepository->generateBillNumber();
            }
            
            // Set created by user
            $data['created_by_user_id'] = Auth::id();
            
            // Set initial amount
            $data['amount'] = $data['amount'] ?? 0;
            
            // Create the bill
            $bill = $this->billRepository->create($data);
            
            // Create bill items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $totalAmount = 0;
                
                foreach ($data['items'] as $itemData) {
                    // Price is directly the total (no quantity needed)
                    $totalAmount += $itemData['price'];
                    
                    $this->billItemRepository->create([
                        'bill_id' => $bill->id,
                        'service_type' => $itemData['service_type'],
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'total' => $itemData['price']
                    ]);
                }
                
                // Update the bill amount with the sum of all items
                $this->billRepository->update($bill, ['amount' => $totalAmount]);
            }
            
            // Generate PDF if needed
            if (isset($data['generate_pdf']) && $data['generate_pdf']) {
                $pdfPath = $this->generatePdf($bill);
                $this->billRepository->update($bill, ['pdf_path' => $pdfPath]);
            }
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties(['bill_number' => $bill->bill_number])
                ->log('Bill created');
            
            return $this->getBillById($bill->id);
        });
    }
    
    /**
     * Update an existing bill and its items
     *
     * @param Bill $bill
     * @param array $data
     * @return Bill
     */
    public function updateBill(Bill $bill, array $data): Bill
    {
        return DB::transaction(function () use ($bill, $data) {
            // Update bill attributes
            $updateData = array_intersect_key($data, array_flip([
                'patient_id',
                'doctor_user_id',
                'bill_number',
                'issue_date',
                'payment_method',
                'description'
            ]));
            
            if (!empty($updateData)) {
                $this->billRepository->update($bill, $updateData);
            }
            
            // If items are provided, replace all existing items
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $this->billItemRepository->deleteByBillId($bill->id);
                
                $totalAmount = 0;
                
                // Create new items
                foreach ($data['items'] as $itemData) {
                    // Price is directly the total (no quantity needed)
                    $totalAmount += $itemData['price'];
                    
                    $this->billItemRepository->create([
                        'bill_id' => $bill->id,
                        'service_type' => $itemData['service_type'],
                        'description' => $itemData['description'] ?? null,
                        'price' => $itemData['price'],
                        'total' => $itemData['price']
                    ]);
                }
                
                // Update the bill amount
                $this->billRepository->update($bill, ['amount' => $totalAmount]);
            }
            
            // Regenerate PDF if requested
            if (isset($data['regenerate_pdf']) && $data['regenerate_pdf']) {
                // Delete old PDF if exists
                if ($bill->pdf_path) {
                    Storage::delete($bill->pdf_path);
                }
                
                $pdfPath = $this->generatePdf($bill);
                $this->billRepository->update($bill, ['pdf_path' => $pdfPath]);
            }
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties(['bill_number' => $bill->bill_number])
                ->log('Bill updated');
            
            return $this->getBillById($bill->id);
        });
    }
    
    /**
     * Delete a bill and all its items
     *
     * @param Bill $bill
     * @return bool
     */
    public function deleteBill(Bill $bill): bool
    {
        return DB::transaction(function () use ($bill) {
            // Delete the PDF file if exists
            if ($bill->pdf_path) {
                Storage::delete($bill->pdf_path);
            }
            
            // Delete all bill items
            $this->billItemRepository->deleteByBillId($bill->id);
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties(['bill_number' => $bill->bill_number])
                ->log('Bill deleted');
            
            // Delete the bill
            return $this->billRepository->delete($bill);
        });
    }
    
    /**
     * Add a new item to a bill
     *
     * @param Bill $bill
     * @param array $itemData
     * @return BillItem
     */
    public function addBillItem(Bill $bill, array $itemData): BillItem
    {
        return DB::transaction(function () use ($bill, $itemData) {
            // Price is directly the total (no quantity needed)
            $price = $itemData['price'];
            
            // Create the item
            $item = $this->billItemRepository->create([
                'bill_id' => $bill->id,
                'service_type' => $itemData['service_type'],
                'description' => $itemData['description'] ?? null,
                'price' => $price,
                'total' => $price
            ]);
            
            // Update the bill total amount
            $newTotal = $this->billItemRepository->calculateBillTotal($bill->id);
            $this->billRepository->update($bill, ['amount' => $newTotal]);
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties([
                    'bill_number' => $bill->bill_number,
                    'item_id' => $item->id,
                    'service_type' => $item->service_type
                ])
                ->log('Bill item added');
            
            return $item;
        });
    }
    
    /**
     * Update an existing bill item
     *
     * @param Bill $bill
     * @param BillItem $item
     * @param array $itemData
     * @return BillItem
     */
    public function updateBillItem(Bill $bill, BillItem $item, array $itemData): BillItem
    {
        // Ensure the item belongs to the bill
        if ($item->bill_id !== $bill->id) {
            throw new \InvalidArgumentException('The item does not belong to this bill.');
        }
        
        return DB::transaction(function () use ($bill, $item, $itemData) {
            $price = $itemData['price'] ?? $item->price;
            
            // Update data - price and total are the same (no quantity)
            $updateData = array_merge($itemData, [
                'price' => $price,
                'total' => $price
            ]);
            
            // Update the item
            $updatedItem = $this->billItemRepository->update($item, $updateData);
            
            // Update the bill total amount
            $newTotal = $this->billItemRepository->calculateBillTotal($bill->id);
            $this->billRepository->update($bill, ['amount' => $newTotal]);
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties([
                    'bill_number' => $bill->bill_number,
                    'item_id' => $item->id,
                    'service_type' => $updatedItem->service_type
                ])
                ->log('Bill item updated');
            
            return $updatedItem;
        });
    }
    
    /**
     * Remove an item from a bill
     *
     * @param Bill $bill
     * @param BillItem $item
     * @return bool
     */
    public function removeBillItem(Bill $bill, BillItem $item): bool
    {
        // Ensure the item belongs to the bill
        if ($item->bill_id !== $bill->id) {
            throw new \InvalidArgumentException('The item does not belong to this bill.');
        }
        
        return DB::transaction(function () use ($bill, $item) {
            // Delete the item
            $result = $this->billItemRepository->delete($item);
            
            // Update the bill total amount
            $newTotal = $this->billItemRepository->calculateBillTotal($bill->id);
            $this->billRepository->update($bill, ['amount' => $newTotal]);
            
            // Log activity
            activity()
                ->causedBy(Auth::user())
                ->performedOn($bill)
                ->withProperties([
                    'bill_number' => $bill->bill_number,
                    'item_id' => $item->id
                ])
                ->log('Bill item removed');
            
            return $result;
        });
    }
    
    /**
     * Get bills for a specific patient
     *
     * @param int $patientId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPatientBills(int $patientId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->billRepository->getByPatientId(
            $patientId,
            $filters,
            ['doctor', 'items'],
            $perPage
        );
    }
    
    /**
     * Get bills for the authenticated patient user
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAuthenticatedPatientBills(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $user = Auth::user();
        
        // Find patient record for this user
        $patient = Patient::where('user_id', $user->id)->first();
        
        if (!$patient) {
            // Log this issue for debugging
            Log::warning('No Patient record found for authenticated user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_name' => $user->name
            ]);
            
            // If no patient record exists, return empty paginator
            // This handles cases where:
            // - User doesn't have a patient record yet
            // - User is admin/staff accessing the endpoint
            // - Any other user type
            return new LengthAwarePaginator(
                collect([]),
                0,
                $perPage,
                1,
                ['path' => request()->url()]
            );
        }
        
        // Log successful patient lookup for debugging
        Log::info('Found Patient record for user', [
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'patient_user_id' => $patient->user_id
        ]);
        
        return $this->getPatientBills($patient->id, $filters, $perPage);
    }
    
    /**
     * Generate a PDF for a bill
     *
     * @param Bill $bill
     * @return string The path to the stored PDF
     */
    protected function generatePdf(Bill $bill): string
    {
        // Ensure relationships are loaded
        if (!$bill->relationLoaded('patient')) {
            $bill->load('patient');
        }
        
        if (!$bill->relationLoaded('doctor')) {
            $bill->load('doctor');
        }
        
        if (!$bill->relationLoaded('items')) {
            $bill->load('items');
        }
        
        // In a real implementation, you would use PDF generation logic
        // For now, this is a placeholder
        $filename = 'bills/bill-' . $bill->bill_number . '.pdf';
        
        // Simulate PDF creation
        Storage::put($filename, 'Placeholder for bill PDF');
        
        return $filename;
    }
}