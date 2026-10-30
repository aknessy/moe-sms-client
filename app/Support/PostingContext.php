<?php

namespace App\Support;

use App\Models\BasicInformation;
use App\Models\Lga;
use App\Models\Posting;
use App\Models\State;
use Illuminate\Support\Collection;

/**
 * Where each officer stands right now, worked out from their postings.
 *
 * Read by the Posting & Deployment form, which needs it to decide what a
 * transfer or deployment may do, and by both nominal rolls, which print the
 * LGA an officer is posted to. One source so the two cannot disagree.
 */
class PostingContext
{
    /**
     * Where an officer with nothing on record is taken to stand: this command.
     * A posting only says where someone was moved to, so until one is recorded
     * they have no state of their own — and without a state they could never
     * be deployed, a deployment being confined to the state they are in.
     */
    public static function default(): array
    {
        $state = State::where('name', config('posting.default_state'))->first();

        $lga = $state === null ? null : Lga::where('state_id', $state->id)
            ->where('lga_name', config('posting.default_lga'))->first();

        return [
            'state_id' => $state?->id,
            'state' => $state?->name,
            'lga_id' => $lga?->id,
            'lga' => $lga?->lga_name,
            'station' => config('posting.default_station'),
        ];
    }

    /**
     * Where one officer stands. The same answer `all()` gives for them,
     * without reading the whole roll to get it — the self-service pages only
     * ever ask about the officer signed in.
     */
    public static function for(?BasicInformation $staff): array
    {
        if ($staff === null) {
            return self::default();
        }

        $theirs = Posting::query()
            ->where('basic_information_ulid', $staff->ulid)
            ->orderBy('effective_date')
            ->get();

        return self::resolve($theirs, State::pluck('name', 'id'),
                             Lga::pluck('lga_name', 'id'), self::default());
    }

    /**
     * One officer's postings, read into where they now stand.
     *
     * The state they are in is wherever their last transfer sent them; the
     * LGA, wherever their last posting of either kind did. A transfer names
     * the station it sends them to; a deployment does not, so the LGA it sent
     * them to is where they now are. Anything with no posting behind it falls
     * back to this command.
     */
    private static function resolve(Collection $theirs, Collection $states,
                                    Collection $lgas, array $default): array
    {
        $state = $theirs->where('posting_type', 'transfer')
            ->whereNotNull('to_state')->last()?->to_state;

        $lga = $theirs->whereNotNull('to_lga')->last()?->to_lga;

        $latest = $theirs->last();
        $station = $latest?->duty_station
            ?: ($latest?->to_lga ? ($lgas[$latest->to_lga] ?? null) : null);

        return [
            'state_id' => $state ?: $default['state_id'],
            'state' => $state ? ($states[$state] ?? null) : $default['state'],
            'lga_id' => $lga ?: $default['lga_id'],
            'lga' => $lga ? ($lgas[$lga] ?? null) : $default['lga'],
            'station' => $station ?: $default['station'],
        ];
    }

    /**
     * Every officer, keyed by ulid: the state their last transfer put them in,
     * the LGA their last posting sent them to, and the station they are
     * presently at.
     *
     * A transfer reads the state as the one it moves them out of; a deployment
     * reads the station as the one it moves them from, and is confined to the
     * LGAs of that state. Anything an officer has no posting for falls back to
     * this command.
     */
    public static function all(): Collection
    {
        $postings = Posting::query()
            ->orderBy('effective_date')
            ->get()
            ->groupBy('basic_information_ulid');

        $states = State::pluck('name', 'id');
        $lgas = Lga::pluck('lga_name', 'id');
        $default = self::default();

        return BasicInformation::query()->get()
            ->mapWithKeys(fn ($staff) => [
                $staff->ulid => self::resolve($postings[$staff->ulid] ?? collect(),
                                              $states, $lgas, $default),
            ]);
    }
}
