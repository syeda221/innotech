<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Province;

return new class extends Migration
{
    public function up(): void
    {
        $defaultProvinces = [
            'Sindh',
            'Punjab',
            'Khyber Pakhtunkhwa',
            'Balochistan',
            'Islamabad Capital Territory',
            'Azad Jammu & Kashmir',
            'Gilgit-Baltistan',
        ];

        foreach ($defaultProvinces as $name) {
            Province::firstOrCreate(
                ['name' => $name],
                ['status' => 'active']
            );
        }
    }

    public function down(): void
    {
        // Don't delete on rollback
    }
};
