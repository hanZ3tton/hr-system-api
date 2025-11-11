<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeaveRequestRequest;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeaveController extends Controller
{
    /**
     * Display all leave requests
     * - Admin/HR: all
     * - Employee: only own
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = LeaveRequest::with(['user:id,name,employee_number', 'processor:id,name'])
            ->orderBy('requested_at', 'desc');

        if ($user->role->name !== 'admin' && $user->role->name !== 'hrd') {
            $query->where('user_id', $user->id);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * Store new leave request
     */
    public function store(StoreLeaveRequestRequest $request)
    {
        $user = $request->user();
        $path = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->storeAs(
                "leave_attachments/{$user->id}",
                time() . '_' . $file->getClientOriginalName(),
                'public'
            );
        }

        $leave = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type' => $request->leave_type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $request->days,
            'reason' => $request->reason,
            'attachment_path' => $path,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'Leave request submitted successfully.',
            'data' => $leave,
        ], 201);
    }

    /**
     * Approve or Reject a leave request (Admin/HR only)
     */
    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $user = $request->user();

        if (!in_array($user->role->name, ['admin', 'hrd'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected,cancelled',
        ]);

        DB::transaction(function () use ($leaveRequest, $request, $user) {
            $leaveRequest->update([
                'status' => $request->status,
                'processed_by' => $user->id,
                'processed_at' => now(),
            ]);
        });

        return response()->json([
            'message' => "Leave request {$request->status} successfully.",
            'data' => $leaveRequest->refresh(),
        ]);
    }
}
