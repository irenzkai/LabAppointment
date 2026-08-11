<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class PaymentProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Prepare public storage folders matching file upload controllers
        Storage::disk('public')->makeDirectory('providers/logos');
        Storage::disk('public')->makeDirectory('providers/qrs');

        // 2. Define asset source locations (under public/)
        $logoSource = public_path('images/gcash_logo.png');
        $qrSource = public_path('images/sample_qr.jfif');

        // 3. Define structured public disk target paths
        $logoTarget = 'providers/logos/gcash_logo.png';
        $qrTarget = 'providers/qrs/sample_qr.jfif';

        // 4. Safely duplicate file assets to simulated public storage disk if they exist
        if (File::exists($logoSource)) {
            Storage::disk('public')->put($logoTarget, File::get($logoSource));
        }

        if (File::exists($qrSource)) {
            Storage::disk('public')->put($qrTarget, File::get($qrSource));
        }

        // 5. Seed/Update the database record
        PaymentProvider::updateOrCreate(
            ['name' => 'GCASH'],
            [
                'logo' => File::exists($logoSource) ? $logoTarget : null,
                'qr_code' => $qrTarget, // Required field in the migrations schema
                'is_active' => true,
            ]
        );
    }
}