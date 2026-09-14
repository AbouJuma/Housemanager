<?php

namespace App\Modules\Sales\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sales\Http\Requests\StoreBuyerRequest;
use App\Modules\Sales\Http\Requests\UpdateBuyerRequest;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BuyerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Buyer::class);

        $buyers = Buyer::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%');
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->orderBy('name')
            ->paginate(20);

        return view('sales::buyers.index', compact('buyers'));
    }

    public function create(): View
    {
        $this->authorize('create', Buyer::class);

        return view('sales::buyers.create');
    }

    public function store(StoreBuyerRequest $request)
    {
        $data = $request->validated();
        
        // Generate control number if not provided
        if (empty($data['control_number'])) {
            $data['control_number'] = $this->generateControlNumber($data['company_id'] ?? auth()->user()->company_id);
        }
        
        $buyer = Buyer::create($data);

        // Send welcome message to buyer
        $this->sendWelcomeMessage($buyer);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'buyer' => $buyer,
                'message' => 'Buyer created successfully.'
            ]);
        }

        return redirect()->route('buyers.index')->with('success', 'Buyer created successfully.');
    }

    protected function sendWelcomeMessage(Buyer $buyer): void
    {
        try {
            $company = $buyer->company;

            $data = [
                'buyer_name' => $buyer->name,
                'company_name' => $company->name,
                'control_number' => $buyer->control_number,
                'phone' => $buyer->phone,
                'bank_name' => 'N/A',
                'account_number' => 'N/A',
                'amount' => '0',
                'unit_number' => 'N/A',
                'property_name' => 'N/A',
            ];

            // Get message template (shared templates for all companies)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'buyer_welcome')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $buyer->company_id)
                    ->where('type', 'buyer_welcome')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active buyer_welcome template found', [
                    'company_id' => $buyer->company_id,
                ]);
                return;
            }

            // Send SMS
            if ($buyer->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending SMS to buyer', ['phone' => $buyer->phone, 'message' => $smsMessage]);
                $smsService = new \App\Modules\Tenants\Services\BeemsSmsService();
                $smsService->send($buyer->phone, $smsMessage);
            } else {
                \Log::warning('Buyer has no phone number', ['buyer_id' => $buyer->id]);
            }

            // Send Email
            if ($buyer->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                \Log::info('Sending email to buyer', ['email' => $buyer->email]);
                \Illuminate\Support\Facades\Mail::raw($emailBody, function ($message) use ($buyer, $emailSubject) {
                    $message->to($buyer->email)
                        ->subject($emailSubject);
                });
            } else {
                \Log::warning('Buyer has no email', ['buyer_id' => $buyer->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending buyer welcome message', [
                'buyer_id' => $buyer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function generateControlNumber(int $companyId): string
    {
        $maxAttempts = 100;
        $attempts = 0;
        
        do {
            // Generate random 10-digit number starting with 9
            $controlNumber = '9' . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);
            
            // Check if this control number already exists (including soft-deleted)
            $exists = Buyer::withTrashed()
                ->where('control_number', $controlNumber)
                ->exists();
            
            $attempts++;
        } while ($exists && $attempts < $maxAttempts);
        
        return $controlNumber;
    }

    public function show(Buyer $buyer): View
    {
        $this->authorize('view', $buyer);

        $buyer->load('sales.unit.building.property');

        return view('sales::buyers.show', compact('buyer'));
    }

    public function edit(Buyer $buyer): View
    {
        $this->authorize('update', $buyer);

        return view('sales::buyers.edit', compact('buyer'));
    }

    public function update(UpdateBuyerRequest $request, Buyer $buyer): RedirectResponse
    {
        $buyer->update($request->validated());

        return redirect()->route('buyers.show', $buyer)->with('success', 'Buyer updated successfully.');
    }

    public function destroy(Buyer $buyer): RedirectResponse
    {
        $this->authorize('delete', $buyer);

        $buyer->delete();

        return redirect()->route('buyers.index')->with('success', 'Buyer deleted successfully.');
    }

    public function search(Request $request)
    {
        $search = $request->get('search');
        $buyers = Buyer::query()
            ->where('status', 'active')
            ->when($search && $search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return response()->json($buyers);
    }
}
