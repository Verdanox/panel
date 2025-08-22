<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->decimal('cpu_warning_threshold', 5, 2)->default(80.00)->after('cpu_overallocate');
            $table->decimal('memory_warning_threshold', 5, 2)->default(85.00)->after('memory_overallocate');
            $table->decimal('disk_warning_threshold', 5, 2)->default(90.00)->after('disk_overallocate');
            $table->timestamp('last_resource_check')->nullable()->after('updated_at');
            $table->boolean('has_resource_warnings')->default(false)->after('last_resource_check');
        });
    }

    public function down(): void
    {
        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn([
                'cpu_warning_threshold',
                'memory_warning_threshold', 
                'disk_warning_threshold',
                'last_resource_check',
                'has_resource_warnings'
            ]);
        });
    }
};
