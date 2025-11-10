<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest; // Pastikan ini di-import
use App\Http\Requests\CheckOutRequest; // Pastikan ini di-import
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // Penting: Import Facade Storage
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    // =========================================================================
    // CHECK-IN
    // =========================================================================
    /**
     * Menangani proses Check-In user.
     * Memvalidasi data, mengupload foto, dan mencatat waktu check-in ke database.
     * Menggunakan DB Transaction dan Row Locking untuk keamanan data.
     *
     * @param \App\Http\Requests\CheckInRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Menggunakan DB Transaction untuk memastikan integritas data
        return DB::transaction(function () use ($user, $today, $request) {

            // Lock the attendance row (if exists) to avoid race condition
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if ($attendance && $attendance->check_in_at) {
                return response()->json(['message' => 'You have already checked in today.'], 400);
            }

            // Validasi apakah file foto ada
            // Note: Validasi ini redundant jika CheckInRequest sudah menangani 'required'
            if (!$request->hasFile('check_in_photo')) {
                // Tambahkan error handling jika mobile app tidak mengirim foto
                return response()->json(['message' => 'Check-in photo is required.'], 422);
            }

            // store photo: decide disk based on env (public or s3)
            $disk = config('filesystems.attendance_disk', 'public');
            $file = $request->file('check_in_photo');

            // filename: attendance/{user}/{date}/checkin_{timestamp}.{ext}
            $path = $file->storeAs(
                "attendances/{$user->id}/{$today}",
                'checkin_' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension(),
                $disk
            );

            $data = [
                'check_in_at' => now(),
                'check_in_ip' => $request->ip(),
                'check_in_location' => $request->input('check_in_location'),
                'check_in_photo_path' => $path,
                'check_in_photo_mime' => $file->getClientMimeType(),
                'status' => 'present',
                'date' => $today, // Tambahkan 'date' agar updateOrCreate bekerja
            ];

            // create or update
            $attendance = Attendance::updateOrCreate(
                ['user_id' => $user->id, 'date' => $today],
                $data
            );

            // refresh model untuk memuat accessors (check_in_photo_url)
            $attendance->refresh();

            // Menggunakan Accessor check_in_photo_url yang sudah didefinisikan di Model
            return response()->json([
                'message' => 'Check-in successful',
                'data' => [
                    'attendance' => $attendance,
                    'check_in_photo_url' => $attendance->check_in_photo_url, // Menggunakan Accessor
                ],
            ], 201);
        });
    }

    // =========================================================================
    // CHECK-OUT
    // =========================================================================
    /**
     * Menangani proses Check-Out user.
     * Memvalidasi data, mengupload foto, mencatat waktu check-out, dan menghitung durasi kerja.
     * Memerlukan user sudah check-in sebelumnya.
     *
     * @param \App\Http\Requests\CheckOutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $user = $request->user();
        $today = now()->toDateString();

        return DB::transaction(function () use ($user, $today, $request) {
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->lockForUpdate()
                ->first();

            if (!$attendance || !$attendance->check_in_at) {
                return response()->json(['message' => 'You have not checked in yet.'], 400);
            }
            if ($attendance->check_out_at) {
                return response()->json(['message' => 'You have already checked out today.'], 400);
            }

            // Validasi apakah file foto ada
            // Note: Validasi ini redundant jika CheckOutRequest sudah menangani 'required'
            if (!$request->hasFile('check_out_photo')) {
                // Tambahkan error handling jika mobile app tidak mengirim foto
                return response()->json(['message' => 'Check-out photo is required.'], 422);
            }

            $disk = config('filesystems.attendance_disk', 'public');
            $file = $request->file('check_out_photo');

            $path = $file->storeAs(
                "attendances/{$user->id}/{$today}",
                'checkout_' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension(),
                $disk
            );

            // Menghitung durasi kerja menggunakan Carbon
            $workedSeconds = $attendance->check_in_at ? $attendance->check_in_at->diffInSeconds(now()) : null;

            $attendance->update([
                'check_out_at' => now(),
                'check_out_ip' => $request->ip(),
                'check_out_location' => $request->input('check_out_location'),
                'check_out_photo_path' => $path,
                'check_out_photo_mime' => $file->getClientMimeType(),
                'worked_second' => $workedSeconds,
            ]);

            $attendance->refresh();

            // Menggunakan Accessor check_out_photo_url yang sudah didefinisikan di Model
            return response()->json([
                'message' => 'Check-out successful',
                'data' => [
                    'attendance' => $attendance,
                    'check_out_photo_url' => $attendance->check_out_photo_url, // Menggunakan Accessor
                ],
            ], 200); // Menggunakan status 200 (OK) untuk update
        });
    }

    // =========================================================================
    // HISTORY
    // =========================================================================
    /**
     * Mengambil riwayat absensi (attendance history) untuk user yang sedang login.
     * Data diurutkan berdasarkan tanggal terbaru.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        // Note: Pastikan tabel Attendance memiliki index pada kolom `user_id` untuk performance
        $records = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            // Data yang dikembalikan akan otomatis menyertakan Accessor URL
            'data' => $records,
        ]);
    }
}
