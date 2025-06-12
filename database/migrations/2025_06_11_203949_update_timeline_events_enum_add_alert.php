<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to handle the check constraint directly
        if (DB::getDriverName() === 'pgsql') {
            // Drop the existing check constraint
            DB::statement("ALTER TABLE timeline_events DROP CONSTRAINT IF EXISTS timeline_events_event_type_check");
            
            // Add the new constraint with 'alert' included
            DB::statement("ALTER TABLE timeline_events ADD CONSTRAINT timeline_events_event_type_check CHECK (event_type::text = ANY (ARRAY['appointment'::character varying, 'prescription'::character varying, 'vital_signs'::character varying, 'note'::character varying, 'file_upload'::character varying, 'alert'::character varying, 'manual'::character varying]::text[]))");
        } else {
            // For other databases, modify the column
            Schema::table('timeline_events', function (Blueprint $table) {
                $table->enum('event_type', ['appointment', 'prescription', 'vital_signs', 'note', 'file_upload', 'alert', 'manual'])
                      ->default('manual')
                      ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: PostgreSQL doesn't support removing enum values easily
        // This would require recreating the enum type and updating all references
        // For safety, we'll leave the 'alert' value in place
        
        // If you really need to remove it, you would need to:
        // 1. Create a new enum type without 'alert'
        // 2. Update the column to use the new type
        // 3. Drop the old enum type
        // This is complex and risky in production
    }
};
