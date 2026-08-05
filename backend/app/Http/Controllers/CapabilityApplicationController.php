<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectCapabilityApplicationRequest;
use App\Http\Requests\StoreCapabilityApplicationRequest;
use App\Models\CapabilityApplication;
use App\Models\UserCapability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CapabilityApplicationController extends Controller
{
    /**
     * Submit a capability application (farmer or buyer).
     *
     * POST /api/capability-applications
     * Body: { "capability_type": "farmer", "supporting_documents": [...] }
     */
    public function store(StoreCapabilityApplicationRequest $request): JsonResponse
    {

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if the user already has an active capability of this type.
        $existingCapability = $user->capabilities()
            ->where('capability_type', $request->input('capability_type'))
            ->where('status', 'active')
            ->exists();

        if ($existingCapability) {
            return response()->json([
                'message' => 'You already have an active ' . $request->input('capability_type') . ' capability.',
            ], 409);
        }

        // Check if the user already has a pending application for this type.
        $pendingApplication = $user->capabilityApplications()
            ->where('capability_type', $request->input('capability_type'))
            ->where('status', 'pending')
            ->exists();

        if ($pendingApplication) {
            return response()->json([
                'message' => 'You already have a pending application for ' . $request->input('capability_type') . ' capability.',
            ], 409);
        }

        $application = CapabilityApplication::create([
            'user_id'              => $user->id,
            'capability_type'      => $request->validated('capability_type'),
            'supporting_documents' => $request->validated('supporting_documents'),
        ]);

        return response()->json([
            'message'     => 'Capability application submitted successfully.',
            'application' => $application->load('user'),
        ], 201);
    }

    /**
     * List the authenticated user's own capability applications.
     *
     * GET /api/capability-applications/my
     */
    public function my(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $applications = $user->capabilityApplications()
            ->with('reviewer:id,first_name,second_name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'applications' => $applications,
        ]);
    }

    /**
     * Show a single capability application (own or admin).
     *
     * GET /api/capability-applications/{id}
     */
    public function show(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $application = CapabilityApplication::with(['user:id,first_name,second_name,phone', 'reviewer:id,first_name,second_name'])
            ->findOrFail($id);

        /////// Non-admin users can only view their own applications./////////
        if (! $user->is_admin && $application->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not authorized to view this application.',
            ], 403);
        }

        return response()->json([
            'application' => $application,
        ]);
    }

    /**
     * List all pending capability applications (admin only).
     *
     * GET /api/admin/capability-applications?status=pending&capability_type=farmer
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $query = CapabilityApplication::with('user:id,first_name,second_name,phone');

        if ($request->has('status')) {
            $request->validate(['status' => ['in:pending,approved,rejected']]);
            $query->where('status', $request->input('status'));
        }

        if ($request->has('capability_type')) {
            $request->validate(['capability_type' => ['in:farmer,buyer']]);
            $query->where('capability_type', $request->input('capability_type'));
        }

        $applications = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($applications);
    }

    /**
     * Approve a pending capability application (admin only).
     *
     * POST /api/admin/capability-applications/{id}/approve
     */
    public function approve(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $application = CapabilityApplication::findOrFail($id);

        if ($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been ' . $application->status . '.',
            ], 422);
        }

        DB::transaction(function () use ($application, $admin) {
            $application->update([
                'status'      => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            // Grant the capability (upsert: reactivate if previously revoked).
            UserCapability::updateOrCreate(
                [
                    'user_id'         => $application->user_id,
                    'capability_type' => $application->capability_type,
                ],
                [
                    'capability_application_id' => $application->id,
                    'status'                    => 'active',
                    'granted_by'                => $admin->id,
                    'granted_at'                => now(),
                    'revoked_at'                => null,
                ],
            );
        });

        return response()->json([
            'message'     => 'Application approved. ' . ucfirst($application->capability_type) . ' capability granted.',
            'application' => $application->fresh()->load(['user:id,first_name,second_name,phone', 'capabilityGrant']),
        ]);
    }

    /**
     * Reject a pending capability application (admin only).
     *
     * POST /api/admin/capability-applications/{id}/reject
     * Body: { "rejection_reason": "..." }
     */
    public function reject(RejectCapabilityApplicationRequest $request, int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $admin = Auth::user();

        if (! $admin->is_admin) {
            return response()->json([
                'message' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $application = CapabilityApplication::findOrFail($id);

        if ($application->status !== 'pending') {
            return response()->json([
                'message' => 'This application has already been ' . $application->status . '.',
            ], 422);
        }

        $application->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->validated('rejection_reason'),
            'reviewed_by'      => $admin->id,
            'reviewed_at'      => now(),
        ]);

        return response()->json([
            'message'     => 'Application rejected.',
            'application' => $application->fresh()->load('user:id,first_name,second_name,phone'),
        ]);
    }
}
