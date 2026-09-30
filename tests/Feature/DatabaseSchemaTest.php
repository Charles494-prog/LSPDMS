<?php

namespace Tests\Feature;

use App\Models\Purok;
use App\Models\Record;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_and_migrations_use_the_application_schema(): void
    {
        $this->assertSame('login_user', (new User)->getTable());
        $this->assertFalse((new User)->usesTimestamps());
        $this->assertSame('records', (new Record)->getTable());
        $this->assertFalse((new Record)->usesTimestamps());
        $this->assertSame('puroks', (new Purok)->getTable());
        $this->assertSame('id', (new Purok)->getKeyName());
        $this->assertTrue((new Purok)->getIncrementing());

        $this->assertTrue(Schema::hasColumns('login_user', [
            'gmail',
            'password',
            'tungkulin',
            'is_verified',
            'remember_token',
            'remember_token_expires_at',
            'first_name',
            'last_name',
            'gender',
            'profile_picture',
            'dark_mode',
            'dashboard_allow',
            'form_generator_allow',
            'Delistment_allow',
            'Inquire_Allow',
        ]));
        $this->assertTrue(Schema::hasColumns('puroks', [
            'id',
            'purok_number',
            'purok_name',
        ]));
        $purokColumns = Schema::getColumnListing('puroks');
        $this->assertSame(['id', 'purok_number', 'purok_name'], array_slice($purokColumns, 0, 3));
        $this->assertTrue(collect(Schema::getIndexes('puroks'))->contains(
            fn (array $index): bool => $index['unique']
                && ! $index['primary']
                && $index['columns'] === ['purok_number']
        ));
        $this->assertFalse(Schema::hasTable('members'));
        $this->assertTrue(Schema::hasColumns('records', [
            'id',
            'full_name',
            'registry',
            'gender',
            'purok',
            'grupo',
            'kapisanan',
            'date_of_baptism',
            'evangelist',
            'date_of_entry',
            'r2_10_eligibility',
            'status',
            'tarheta_status',
            'monthly_code',
            'date_of_birth',
            'filing_status',
            'r2_10_endorsement',
            'reason_of_delistment',
            'log_section',
        ]));
        $this->assertFalse(Schema::hasColumn('puroks', 'created_at'));
        $this->assertFalse(Schema::hasColumn('puroks', 'updated_at'));
        $this->assertSame('varchar', Schema::getColumnType('puroks', 'manggagawa'));
        $this->assertTrue(Schema::hasColumns('global_variables', [
            'id',
            'locale',
            'lcode',
            'district',
            'dcode',
            's_number',
            'pastor_destinado',
            'ksp_pd',
            'kalihim_ms',
            'kalihim_uwp',
            'baptism_requirement',
            'entry_years_requirement',
            'dateofbaptism_requirement_boolean',
            'dateofentryrequirement_boolean',
        ]));
        $this->assertSame('date', Schema::getColumnType('global_variables', 'baptism_requirement'));
        $this->assertSame('integer', Schema::getColumnType('global_variables', 'entry_years_requirement'));
        $this->assertContains(Schema::getColumnType('global_variables', 'dateofbaptism_requirement_boolean'), ['boolean', 'tinyint']);
        $this->assertContains(Schema::getColumnType('global_variables', 'dateofentryrequirement_boolean'), ['boolean', 'tinyint']);

        $user = new User;
        $purok = new Purok;

        $this->assertInstanceOf(HasMany::class, $user->secretaryPuroks());
        $this->assertInstanceOf(BelongsTo::class, $purok->secretaryUser());
        $this->assertSame('login_user', $purok->secretaryUser()->getRelated()->getTable());
    }
}
