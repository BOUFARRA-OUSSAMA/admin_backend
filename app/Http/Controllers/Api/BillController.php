<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bill\StoreBillRequest;
use App\Http\Requests\Bill\UpdateBillRequest;
use App\Http\Requests\Bill\StoreBillItemRequest;
use App\Http\Requests\Bill\UpdateBillItemRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\BillItemResource;
use App\Http\Resources\BillCollection;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Patient;
use App\Services\BillService;
use App\Services\DateFilterService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BillController extends Controller
{
    use ApiResponseTrait;

    /**
     * @var BillService
     */
    protected $billService;
    
    /**
     * @var DateFilterService
     */
    protected $dateFilterService;

    /**
     * BillController constructor.
     *
     * @param BillService $billService
     * @param DateFilterService $dateFilterService
     */
    public function __construct(BillService $billService, DateFilterService $dateFilterService)
    {
        $this->billService = $billService;
        $this->dateFilterService = $dateFilterService;
    }

    /**
     * Display a listing of the bills.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'doctor_id',
            'doctor_name', 
            'patient_id',
            'date_from',
            'date_to',
            'preset_period',
            'amount_min',
            'amount_max',
            'payment_method',
            'service_type', 
            'sort_by',
            'sort_direction'
        ]);
        
        // Process date filters including preset periods
        $filters = $this->dateFilterService->processDates($filters, $request->header('Timezone'));
        
        $perPage = $request->input('per_page', 15);
        $bills = $this->billService->getAllBills($filters, $perPage);
        
        return $this->paginated($bills, 'Bills retrieved successfully');
    }
    
    /**
     * Store a newly created bill.
     *
     * @param StoreBillRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBillRequest $request)
    {
        try {
            $bill = $this->billService->createBill($request->validated());
            return $this->success(new BillResource($bill), 'Bill created successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to create bill: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Display the specified bill.
     *
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Bill $bill)
    {
        try {
            $bill = $this->billService->getBillById($bill->id);
            return $this->success(new BillResource($bill));
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve bill: ' . $e->getMessage(), 404);
        }
    }
    
    /**
     * Update the specified bill.
     *
     * @param UpdateBillRequest $request
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBillRequest $request, Bill $bill)
    {
        try {
            $bill = $this->billService->updateBill($bill, $request->validated());
            return $this->success(new BillResource($bill), 'Bill updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update bill: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove the specified bill.
     *
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Bill $bill)
    {
        try {
            $this->billService->deleteBill($bill);
            return $this->success(null, 'Bill deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete bill: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all items for a specific bill.
     *
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function getItems(Bill $bill)
    {
        return $this->success(BillItemResource::collection($bill->items), 'Bill items retrieved successfully');
    }
    
    /**
     * Add an item to a bill.
     *
     * @param StoreBillItemRequest $request
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function addItem(StoreBillItemRequest $request, Bill $bill)
    {
        try {
            $item = $this->billService->addBillItem($bill, $request->validated());
            return $this->success(new BillItemResource($item), 'Item added to bill successfully', 201);
        } catch (\Exception $e) {
            return $this->error('Failed to add item to bill: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Update a specific item in a bill.
     *
     * @param UpdateBillItemRequest $request
     * @param Bill $bill
     * @param BillItem $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateItem(UpdateBillItemRequest $request, Bill $bill, BillItem $item)
    {
        try {
            $item = $this->billService->updateBillItem($bill, $item, $request->validated());
            return $this->success(new BillItemResource($item), 'Bill item updated successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->error('Failed to update bill item: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove a specific item from a bill.
     *
     * @param Bill $bill
     * @param BillItem $item
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeItem(Bill $bill, BillItem $item)
    {
        try {
            $this->billService->removeBillItem($bill, $item);
            return $this->success(null, 'Bill item removed successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->error('Failed to remove bill item: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get bills for a specific patient.
     *
     * @param Request $request
     * @param Patient $patient
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPatientBills(Request $request, Patient $patient)
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'sort_by',
            'sort_direction'
        ]);
        
        $perPage = $request->input('per_page', 15);
        $bills = $this->billService->getPatientBills($patient->id, $filters, $perPage);
        
        return $this->paginated($bills, 'Patient bills retrieved successfully');
    }
    
    /**
     * Get bills for the authenticated patient user.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyBills(Request $request)
    {
        try {
            $filters = $request->only([
                'date_from',
                'date_to',
                'sort_by',
                'sort_direction'
            ]);
            
            $perPage = $request->input('per_page', 15);
            $bills = $this->billService->getAuthenticatedPatientBills($filters, $perPage);
            
            return $this->paginated($bills, 'Your bills retrieved successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to retrieve your bills: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * View a specific bill (for patient access).
     *
     * @param Bill $bill
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewBill(Bill $bill)
    {
        try {
            // Verify that the authenticated user owns this bill
            $user = Auth::user();
            $patient = Patient::where('user_id', $user->id)->first();
            
               if (!$patient) {
            return $this->error('Patient profile not found for the authenticated user.', 403);
        }
            if ($bill->patient_id !== $patient->id) {
                return $this->error('You are not authorized to view this bill.', 403);
            }
            
            return $this->success(new BillResource($bill->load(['doctor.doctor', 'items', 'patient.user'])));
       } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // This catch block might be for Patient::where(...)->firstOrFail() if you use it
        return $this->error('Resource not found or patient profile missing.', 404);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Error in viewBill: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
        return $this->error('Failed to retrieve bill.', 500); // Simplified error message
    }
    }
    
    /**
     * Download PDF for a bill.
     *
     * @param Bill $bill
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadPdf(Bill $bill)
    {
        try {
            if (!$bill->pdf_path || !Storage::exists($bill->pdf_path)) {
                // Generate PDF if it doesn't exist
                $updatedBill = $this->billService->updateBill($bill, ['regenerate_pdf' => true]);
                $pdfPath = $updatedBill->pdf_path;
            } else {
                $pdfPath = $bill->pdf_path;
            }
            
            return Storage::download($pdfPath, "Bill-{$bill->bill_number}.pdf");
        } catch (\Exception $e) {
            return $this->error('Failed to download PDF: ' . $e->getMessage(), 500);
        }
    }
}