<?php

namespace Tests\Feature;

use App\Models\BasicInformation;
use App\Models\NextOfKin;
use App\Models\Notification;
use App\Models\RecordAmendment;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Next of kin and referees for a staff record already on file, and the
 * amendment flow that now leans on this page — HOD GAP decides a request,
 * and a next of kin or referee is corrected here rather than written
 * straight onto the record.
 */
class FamilyAndRefereesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role = 'Administrator', array $staffAttributes = []): User
    {
        $staff = BasicInformation::factory()->create($staffAttributes);

        return User::factory()->create([
            'basic_information_ulid' => $staff->ulid,
            'role' => $role,
        ]);
    }

    public function test_the_page_requires_a_staff_selection_before_showing_anything(): void
    {
        $viewer = $this->user();

        $this->actingAs($viewer)->get(route('family-referees'))
            ->assertOk()
            ->assertDontSee('Add Next of Kin');
    }

    public function test_next_of_kin_is_scoped_to_the_selected_staff(): void
    {
        $viewer = $this->user();
        $staff = BasicInformation::factory()->create();
        $somebodyElse = BasicInformation::factory()->create();

        NextOfKin::create([
            'basic_information_ulid' => $staff->ulid,
            'nok_name' => 'Theirs', 'nok_relationship' => 'Wife',
            'nok_address' => 'Calabar', 'nok_phone_number' => '08030000000',
        ]);
        NextOfKin::create([
            'basic_information_ulid' => $somebodyElse->ulid,
            'nok_name' => 'NotTheirs', 'nok_relationship' => 'Brother',
            'nok_address' => 'Lagos', 'nok_phone_number' => '08039999999',
        ]);

        $response = $this->actingAs($viewer)->get(route('family-referees', ['staff' => $staff->ulid]));

        $response->assertOk()->assertSee('Theirs')->assertDontSee('NotTheirs');
    }

    public function test_a_next_of_kin_is_added(): void
    {
        $viewer = $this->user();
        $staff = BasicInformation::factory()->create();

        $this->actingAs($viewer)->post(route('family-referees.next-of-kin.store'), [
            'basic_information_ulid' => $staff->ulid,
            'nok_name' => 'Added Name',
            'nok_relationship' => 'Sister',
            'nok_address' => 'Uyo',
            'nok_phone_number' => '08030000001',
        ])->assertRedirect(route('family-referees', ['staff' => $staff->ulid]));

        $this->assertDatabaseHas('next_of_kin', [
            'basic_information_ulid' => $staff->ulid,
            'nok_name' => 'Added Name',
        ]);
    }

    public function test_a_next_of_kin_is_updated(): void
    {
        $viewer = $this->user();
        $staff = BasicInformation::factory()->create();

        $kin = NextOfKin::create([
            'basic_information_ulid' => $staff->ulid,
            'nok_name' => 'Old Name', 'nok_relationship' => 'Wife',
            'nok_address' => 'Calabar', 'nok_phone_number' => '08030000000',
        ]);

        $this->actingAs($viewer)->post(route('family-referees.next-of-kin.update', $kin->id), [
            'nok_name' => 'New Name',
            'nok_relationship' => 'Wife',
            'nok_address' => 'Calabar',
            'nok_phone_number' => '08030000000',
        ])->assertRedirect(route('family-referees', ['staff' => $staff->ulid]));

        $this->assertSame('New Name', $kin->fresh()->nok_name);
    }

    public function test_a_next_of_kin_is_removed(): void
    {
        $viewer = $this->user();
        $staff = BasicInformation::factory()->create();

        $kin = NextOfKin::create([
            'basic_information_ulid' => $staff->ulid,
            'nok_name' => 'Gone', 'nok_relationship' => 'Wife',
            'nok_address' => 'Calabar', 'nok_phone_number' => '08030000000',
        ]);

        $this->actingAs($viewer)->post(route('family-referees.next-of-kin.remove', $kin->id))
            ->assertRedirect(route('family-referees', ['staff' => $staff->ulid]));

        $this->assertDatabaseMissing('next_of_kin', ['id' => $kin->id]);
    }

    public function test_saving_referees_replaces_the_whole_list_and_drops_blank_rows(): void
    {
        $viewer = $this->user();
        $staff = BasicInformation::factory()->create();

        Referee::create([
            'basic_information_ulid' => $staff->ulid,
            'referees_record' => [['name' => 'Old Referee', 'address' => 'Lagos', 'phone' => '080', 'email' => 'old@x.com']],
        ]);

        $this->actingAs($viewer)->post(route('family-referees.referees.store'), [
            'basic_information_ulid' => $staff->ulid,
            'referee-name' => ['New Referee', ''],
            'referee-address' => ['Abuja', ''],
            'referee-phone' => ['08030000002', ''],
            'referee-email' => ['new@x.com', ''],
        ])->assertRedirect(route('family-referees', ['staff' => $staff->ulid]));

        $sheet = Referee::where('basic_information_ulid', $staff->ulid)->sole();

        $this->assertCount(1, $sheet->referees_record);
        $this->assertSame('New Referee', $sheet->referees_record[0]['name']);
    }

    public function test_an_amendment_against_next_of_kin_is_not_written_onto_the_staff_record(): void
    {
        $staff = BasicInformation::factory()->create();
        $hodGap = $this->user('HOD GAP');

        $amendment = RecordAmendment::create([
            'basic_information_ulid' => $staff->ulid,
            'field' => 'next_of_kin',
            'requested_value' => 'Replace the phone number for Jane Doe',
            'status' => 'pending',
        ]);

        $this->actingAs($hodGap)->post(route('record-amendments.review', $amendment->ulid), [
            'decision' => 'approved',
        ])->assertRedirect(route('record-amendments'));

        $this->assertSame('approved', $amendment->fresh()->status);
        $this->assertDatabaseMissing('basic_informations', ['ulid' => $staff->ulid, 'next_of_kin' => 'Replace the phone number for Jane Doe']);
    }

    public function test_only_hod_gap_may_decide_an_amendment_request(): void
    {
        $staff = BasicInformation::factory()->create(['phone_number' => '08030000000']);
        $notHodGap = $this->user('Administrator');

        $amendment = RecordAmendment::create([
            'basic_information_ulid' => $staff->ulid,
            'field' => 'phone_number',
            'requested_value' => '08039999999',
            'status' => 'pending',
        ]);

        $this->actingAs($notHodGap)->post(route('record-amendments.review', $amendment->ulid), [
            'decision' => 'approved',
        ])->assertForbidden();

        $this->assertSame('pending', $amendment->fresh()->status);
        $this->assertSame('08030000000', $staff->fresh()->phone_number);
    }

    public function test_a_new_amendment_notifies_the_administrators_registry_officers_and_hod_gap(): void
    {
        $applicant = $this->user('User');
        $admin = $this->user('Administrator');
        $registryOfficer = $this->user('Registry Officer');
        $hodGap = $this->user('HOD GAP');

        RecordAmendment::create([
            'basic_information_ulid' => $applicant->basic_information_ulid,
            'field' => 'phone_number',
            'requested_value' => '08039999999',
            'status' => 'pending',
        ]);

        foreach ([$admin, $registryOfficer, $hodGap] as $recipient) {
            $this->assertSame(1, Notification::where('user_id', $recipient->id)->count());
        }

        $this->assertSame(0, Notification::where('user_id', $applicant->id)->count());
    }

    public function test_the_applicant_is_notified_once_their_request_is_approved(): void
    {
        $staff = BasicInformation::factory()->create(['phone_number' => '08030000000']);
        $applicant = User::factory()->create(['basic_information_ulid' => $staff->ulid, 'role' => 'User']);
        $hodGap = $this->user('HOD GAP');

        $amendment = RecordAmendment::create([
            'basic_information_ulid' => $staff->ulid,
            'field' => 'phone_number',
            'requested_value' => '08039999999',
            'status' => 'pending',
        ]);

        $this->actingAs($hodGap)->post(route('record-amendments.review', $amendment->ulid), [
            'decision' => 'approved',
        ]);

        $this->assertSame(1, Notification::where('user_id', $applicant->id)->count());
    }
}
