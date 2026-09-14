<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        // Show all shared templates (company_id = null) for all users
        $templates = MessageTemplate::whereNull('company_id')
            ->orderBy('type')
            ->get();

        return view('message-templates.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', MessageTemplate::class);

        return view('message-templates.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', MessageTemplate::class);

        $validated = $request->validate([
            'type' => 'required|in:tenant_welcome,landlord_welcome,buyer_welcome,lease_payment,buyer_invoice,landlord_payment_notification,tenant_payment_notification',
            'name' => 'required|string|max:255',
            'sms_template' => 'nullable|string',
            'email_subject' => 'nullable|string|max:255',
            'email_template' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Create shared template (company_id = null)
        MessageTemplate::create([
            ...$validated,
            'company_id' => null,
        ]);

        return redirect()->route('message-templates.index')->with('success', 'Message template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id): View
    {
        $template = MessageTemplate::findOrFail($id);
        $this->authorize('update', $template);

        return view('message-templates.edit', compact('template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $template = MessageTemplate::findOrFail($id);
        $this->authorize('update', $template);

        $validated = $request->validate([
            'sms_template' => 'nullable|string',
            'email_subject' => 'nullable|string|max:255',
            'email_template' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $template->update($validated);

        return redirect()->route('message-templates.index')->with('success', 'Message template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $template = MessageTemplate::findOrFail($id);
        $this->authorize('delete', $template);

        $template->delete();

        return redirect()->route('message-templates.index')->with('success', 'Message template deleted successfully.');
    }
}
