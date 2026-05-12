<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kyc_profiles', function (Blueprint $table): void {
            $table->date('date_of_birth')->nullable()->after('risk_rating');
            $table->string('identity_type')->nullable()->after('date_of_birth');
            $table->string('identity_number')->nullable()->after('identity_type');
            $table->string('address')->nullable()->after('identity_number');
            $table->string('city_district')->nullable()->after('address');
            $table->string('occupation')->nullable()->after('city_district');
            $table->string('source_of_funds')->nullable()->after('occupation');
            $table->string('id_document_path')->nullable()->after('source_of_funds');
        });
    }

    public function down(): void
    {
        Schema::table('kyc_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'date_of_birth',
                'identity_type',
                'identity_number',
                'address',
                'city_district',
                'occupation',
                'source_of_funds',
                'id_document_path',
            ]);
        });
    }
};
