@props([
    'context',              // 'retirement' or 'deceased' — decides which benefits are offered
    'benefits',             // [value => label]
    'staffOptions',         // who a payment may be recorded against
    'payments',             // paginated BenefitPayment records
    'description' => '',
    'emptyPicker' => 'No staff available.',
])

<x-registry.card title="Record a Benefit Payment" :description="$description">
    @if (empty($staffOptions))
        <p class="py-6 text-center text-sm text-base-content/60">{{ $emptyPicker }}</p>
    @else
        {{-- multipart: this form carries the receipt. --}}
        <form method="POST" action="{{ route('benefit-payments.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="context" value="{{ $context }}" />

            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                <x-registry.picker name="basic_information_ulid" label="Staff" :options="$staffOptions" required />
                <x-registry.field name="benefit_type" label="Benefit" required :options="$benefits" />
                <x-registry.field name="amount" label="Amount (₦)" type="number" required placeholder="0.00" />
            </div>

            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                <x-registry.field name="payment_date" label="Date Paid" type="date" required />
                <x-registry.field name="reference_number" label="Payment Reference" placeholder="e.g. Voucher or cheque number" />
                <x-registry.field name="paid_to" label="Paid To"
                                  placeholder="{{ $context === 'deceased' ? 'Next of kin who received it' : 'Officer or account' }}" />
            </div>

            <div class="mb-3 w-full flex flex-col gap-4 lg:flex-row">
                <div class="w-full">
                    <label for="receipt" class="font-sans text-sm font-medium text-base-content">
                        Receipt
                        <span class="font-normal text-base-content/50">— PDF or image, up to 4MB</span>
                    </label>
                    <div class="relative mt-1">
                        <input id="receipt" name="receipt" type="file" accept=".pdf,.jpg,.jpeg,.png"
                               class="w-full cursor-pointer rounded-md border border-base-300 bg-white px-3 py-2 text-xs text-base-content/80 file:mr-3 file:rounded file:border-0 file:bg-[#2E7D32] file:px-3 file:py-1 file:text-xs file:text-white hover:border-primary/50" />
                    </div>
                    @error('receipt')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-3 w-full">
                <x-registry.field name="remarks" label="Remarks" rows="2" />
            </div>

            <div class="py-3 flex flex-wrap items-center justify-end gap-2">
                <button type="submit" class="btn btn-sm btn-cta px-4 shadow-md">Record Payment</button>
                <button type="reset" class="btn btn-sm btn-cta-outline px-4">Reset</button>
            </div>
        </form>
    @endif
</x-registry.card>

<x-registry.card title="Payments Recorded" :count="$payments->total()">
    <x-registry.table :headings="['Staff', 'Benefit', 'Amount', 'Date Paid', 'Reference', 'Paid To', 'Receipt']"
                      empty="No benefit payments recorded yet.">
        @foreach ($payments as $payment)
            <tr class="hover:bg-base-200/60">
                <td class="px-3 py-2">
                    <span class="block font-medium text-base-content">{{ trim($payment->staff?->surname . ', ' . $payment->staff?->firstname) }}</span>
                    <span class="block text-base-content/50">{{ $payment->staff?->staff_number }}</span>
                </td>
                <td class="px-3 py-2">{{ $payment->benefit_label }}</td>
                <td class="px-3 py-2">&#8358;{{ number_format((float) $payment->amount, 2) }}</td>
                <td class="px-3 py-2">{{ $payment->payment_date?->toDateString() }}</td>
                <td class="px-3 py-2">{{ $payment->reference_number ?: '—' }}</td>
                <td class="px-3 py-2">{{ $payment->paid_to ?: '—' }}</td>
                <td class="px-3 py-2">
                    @if ($payment->has_receipt)
                        <a href="{{ route('benefit-payments.receipt', $payment->ulid) }}"
                           class="inline-flex items-center gap-1 text-xs text-[#2E7D32] hover:underline">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-3.5 w-3.5">
                                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                            </svg>
                            {{ Str::limit($payment->receipt_name, 24) }}
                        </a>
                    @else
                        <span class="rounded-full bg-error/10 px-2 py-1 text-xs text-error">No receipt</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-registry.table>

    <x-registry.paginator :items="$payments" />
</x-registry.card>
