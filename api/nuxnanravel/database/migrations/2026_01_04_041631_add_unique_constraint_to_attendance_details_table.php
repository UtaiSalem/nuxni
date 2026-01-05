<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add unique constraint to prevent duplicate attendance records
     */
    public function up(): void
    {
        // First, remove any existing duplicates (keep the earliest record)
        DB::statement('
            DELETE a1 FROM attendance_details a1
            INNER JOIN attendance_details a2 
            WHERE a1.id > a2.id 
            AND a1.course_attendance_id = a2.course_attendance_id 
            AND a1.course_member_id = a2.course_member_id
        ');

        // Add unique constraint
        Schema::table('attendance_details', function (Blueprint $table) {
            $table->unique(['course_attendance_id', 'course_member_id'], 'unique_attendance_per_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_details', function (Blueprint $table) {
            $table->dropUnique('unique_attendance_per_member');
        });
    }
};
