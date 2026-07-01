<?php

namespace App\Http\Controllers;

use App\Models\CapabilityApplication;
use App\Models\UserCapability;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CapabilityApplicationController extends Controller
{
    /**
     * Get all capability applications (admin only).
     */
    public function index(Request $request): Response
    {
        $query = CapabilityApplication::with(['user', 'reviewer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('capability_type')) {
            $query->where('capability_type', $request->capability_type);
        }

        $applications = $query->orderBy('created_at', 'desc')->paginate(20);
        return response($applications);
    }

    /**
     * Submit a new capability application.
     */
    public function store(Request $request): Response
    {
        $user = auth()->user();

        $validated = $request->validate([
            'capability_type' => 'required|in:farmer,buyer',
            'supporting_documents' => 'nullable|array',
        ]);

        // Check if user already has this capability granted
        $existing = UserCapability::where('user_id', $user->id)
            ->where('capability_type', $validated['capability_type'])
            ->where('status', 'active')
            ->first();

        if ($existing) {
            return response(['error' => 'You already have ' . $validated['capability_type'] . ' capability'], 422);
        }

        $application = CapabilityApplication::create([
            'user_id' => $user->id,
            'capability_type' => $validated['capability_type'],
            'supporting_documents' => $validated['supporting_documents'] ?? null,
            'status' => 'pending',
        ]);

        return response([
            'message' => 'Application submitted',
            'application' => $application,
        ], 201);
    }

    /**
     * Get user's own applications.
     */
    public function myApplications(): Response
    {
        $user = auth()->user();

        $applications = CapabilityApplication::where('user_id', $user->id)
            ->with('reviewer')
            ->orderBy('created_at', 'desc')
            ->get();

        return response($applications);
    }

    /**
     * Display a specific application.
     */
    public function show(CapabilityApplication $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['user', 'reviewer']);
        return response($application);
    }

    /**
     * Approve a capability application (admin only).
     */
    public function approve(CapabilityApplication $application): Response
    {
        $this->authorize('approve', $application);

        if ($application->status !== 'pending') {
            return response(['error' => 'Application is already ' . $application->status], 422);
        }

        // Update application status
        $application->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        // Grant capability
        UserCapability::create([
            'user_id' => $application->user_id,
            'capability_type' => $application->capability_type,
            'capability_application_id' => $application->id,
            'status' => 'active',
            'granted_by' => auth()->id(),
            'granted_at' => now(),
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'capability.approved',
            'auditable_type' => CapabilityApplication::class,
            'auditable_id' => $application->id,
            'new_values' => [
                'status' => 'approved',
                'capability_type' => $application->capability_type,
            ],
            'ip_address' => request()->ip(),
        ]);

        return response([
            'message' => 'Application approved',
            'application' => $application,
        ]);
    }

    /**
     * Reject a capability application (admin only).
     */
    public function reject(Request $request, CapabilityApplication $application): Response
    {
        $this->authorize('reject', $application);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if ($application->status !== 'pending') {
            return response(['error' => 'Application is already ' . $application->status], 422);
        }

        $application->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        // Audit log
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'capability.rejected',
            'auditable_type' => CapabilityApplication::class,
            'auditable_id' => $application->id,
            'new_values' => [
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
            ],
            'ip_address' => request()->ip(),
        ]);

        return response([
            'message' => 'Application rejected',
            'application' => $application,
        ]);
    }

    /**
     * Get applications pending review (admin).
     */
    public function pending(): Response
    {
        $applications = CapabilityApplication::where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response($applications);
    }
}
