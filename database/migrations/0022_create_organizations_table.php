<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Introduces the dedicated organizations table supporting multiple organizations per user,
     * voluntary representative deactivation, and staff-approved name revisions.
     */
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            
            // Representative patient account owner (1 User can represent Many Organizations)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->string('name', 150);
            $table->string('pending_name', 150)->nullable(); // Name edit submitted for staff review
            $table->string('representative_name', 150);
            $table->string('representative_phone', 11);
            
            // Partnership Lifecycle Status
            $table->enum('status', [
                'pending',              // Awaiting initial clinic approval
                'approved',             // Active recognized organization partner
                'rejected',             // Declined partnership application
                'deactivated',          // Deactivated by staff or voluntarily by rep (packages & rosters disabled)
                'pending_reactivation'  // Reactivation requested by rep, awaiting staff approval
            ])->default('pending');

            $table->text('status_reason')->nullable(); // Justification for rejection, deactivation, or staff notes
            $table->timestamps();
            $table->softDeletes();
        });

        // Add organization_id reference to organization_members if not present
        if (!Schema::hasColumn('organization_members', 'organization_id')) {
            Schema::table('organization_members', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('organizations')
                    ->onDelete('cascade');
            });
        }

        // Seamless Backfill: Migrate any legacy single-user organization records into the new table
        $legacyUsers = DB::table('users')
            ->whereNotNull('organization_name')
            ->where('organization_name', '!=', '')
            ->get();

        foreach ($legacyUsers as $u) {
            $status = ($u->is_recognized_org && $u->org_status === 'approved') ? 'approved' : ($u->org_status ?? 'pending');
            $orgId = DB::table('organizations')->insertGetId([
                'user_id' => $u->id,
                'name' => strtoupper(trim($u->organization_name)),
                'representative_name' => $u->org_representative_name ?: $u->name,
                'representative_phone' => $u->org_representative_phone ?: $u->phone,
                'status' => in_array($status, ['pending', 'approved', 'rejected', 'deactivated']) ? $status : 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Associate existing roster members with this new organization
            DB::table('organization_members')
                ->where('user_id', $u->id)
                ->whereNull('organization_id')
                ->update(['organization_id' => $orgId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('organization_members', 'organization_id')) {
            Schema::table('organization_members', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            });
        }

        Schema::dropIfExists('organizations');
    }
};