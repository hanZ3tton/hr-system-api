<?php

namespace Tests\Feature; // Ubah ke Feature

use Illuminate\Foundation\Testing\RefreshDatabase; // Penting untuk factory dan database
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile; // Tambahkan ini
use Illuminate\Support\Facades\Storage; // Tambahkan ini
use Tests\TestCase; // Class induk Feature Test
use App\Models\User; // Asumsi model User ada di sini

class AttendancePhotoTest extends TestCase
{
    use RefreshDatabase; // Memastikan database bersih sebelum setiap test

    /**
     * Test case untuk memastikan check-in GAGAL jika tidak ada foto.
     */
    public function test_checkin_requires_photo()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // 2. Lakukan Request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/attendance/check-in', [
                // 'check_in_photo' tidak disertakan
                'check_in_location' => 'Office'
            ]);

        // 3. Assertion
        $response->assertStatus(422); // Mengharapkan validation error
        // Assert spesifik untuk error validasi foto
        $response->assertJsonValidationErrors(['check_in_photo']);
    }

    /**
     * Test case untuk memastikan check-in BERHASIL dan menyimpan foto.
     */
    public function test_checkin_uploads_photo_and_stores_path()
    {
        // 1. Setup Data
        Storage::fake('public'); // Menggunakan disk 'fake' untuk mencegah penulisan file ke disk nyata
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Membuat file palsu
        $file = UploadedFile::fake()->image('selfie.jpg', 600, 600)->size(100);

        // 2. Lakukan Request
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/attendance/check-in', [
                'check_in_photo' => $file,
                'check_in_location' => 'Office'
            ]);

        // 3. Assertion
        $response->assertStatus(201); // Mengharapkan sukses dibuat (Created)

        // **PENTING: Assertion untuk Storage**
        // Karena nama file Anda menggunakan 'now()', Anda harus memastikan
        // bahwa ada file di direktori spesifik tersebut.

        // Asumsi logic Anda menyimpan foto di folder 'attendances/{user_id}/'
        // dan menggunakan nama file dengan format tanggal.
        $folderPath = 'attendances/' . $user->id;

        // Cek bahwa ada file di folder tersebut.
        Storage::disk('public')->assertExists($folderPath);

        // Assert bahwa setidaknya ada satu file di folder tersebut
        $files = Storage::disk('public')->files($folderPath);
        $this->assertNotEmpty($files, 'Gagal menemukan file foto attendance di direktori yang diharapkan.');


        // Cek record di database
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in_location' => 'Office'
            // Anda bisa cek check_in_photo_path jika Anda tahu logikanya
        ]);
    }
}
