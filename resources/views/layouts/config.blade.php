@extends('layout')

@section('title')
    {{ $page_title }}
@endsection

@section('content')
<form>
    <div class="border-b border-base-300 pb-12">
      <h2 class="text-base font-semibold text-base-content">Notifications</h2>
      <p class="mt-1 text-sm text-base-content/70">We'll always let you know about important changes, but you pick what else you want to hear about.</p>

      <div class="mt-10 space-y-10">
        <fieldset>
          <legend class="text-sm font-semibold text-base-content">By email</legend>
          <div class="mt-6 space-y-6">
            <div class="flex gap-3">
              <div class="flex h-6 shrink-0 items-center">
                <div class="group grid size-4 grid-cols-1">
                  <input id="comments" aria-describedby="comments-description" name="comments" type="checkbox" checked class="col-start-1 row-start-1 appearance-none rounded-sm border border-base-300 bg-white checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:checked:bg-base-200 forced-colors:appearance-auto">
                  <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-primary-content group-has-disabled:stroke-base-content/25" viewBox="0 0 14 14" fill="none">
                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>
              </div>
              <div class="text-sm">
                <label for="comments" class="font-medium text-base-content">Comments</label>
                <p id="comments-description" class="text-base-content/60">Get notified when someones posts a comment on a posting.</p>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="flex h-6 shrink-0 items-center">
                <div class="group grid size-4 grid-cols-1">
                  <input id="candidates" aria-describedby="candidates-description" name="candidates" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded-sm border border-base-300 bg-white checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:checked:bg-base-200 forced-colors:appearance-auto">
                  <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-primary-content group-has-disabled:stroke-base-content/25" viewBox="0 0 14 14" fill="none">
                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>
              </div>
              <div class="text-sm">
                <label for="candidates" class="font-medium text-base-content">Candidates</label>
                <p id="candidates-description" class="text-base-content/60">Get notified when a candidate applies for a job.</p>
              </div>
            </div>
            <div class="flex gap-3">
              <div class="flex h-6 shrink-0 items-center">
                <div class="group grid size-4 grid-cols-1">
                  <input id="offers" aria-describedby="offers-description" name="offers" type="checkbox" class="col-start-1 row-start-1 appearance-none rounded-sm border border-base-300 bg-white checked:border-primary checked:bg-primary indeterminate:border-primary indeterminate:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:checked:bg-base-200 forced-colors:appearance-auto">
                  <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-primary-content group-has-disabled:stroke-base-content/25" viewBox="0 0 14 14" fill="none">
                    <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </div>
              </div>
              <div class="text-sm">
                <label for="offers" class="font-medium text-base-content">Offers</label>
                <p id="offers-description" class="text-base-content/60">Get notified when a candidate accepts or rejects an offer.</p>
              </div>
            </div>
          </div>
        </fieldset>

        <fieldset>
          <legend class="text-sm font-semibold text-base-content">Push notifications</legend>
          <p class="mt-1 text-sm text-base-content/70">These are delivered via SMS to your mobile phone.</p>
          <div class="mt-6 space-y-6">
            <div class="flex items-center gap-x-3">
              <input id="push-everything" name="push-notifications" type="radio" checked class="relative size-4 appearance-none rounded-full border border-base-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-primary checked:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:before:bg-base-300 forced-colors:appearance-auto forced-colors:before:hidden">
              <label for="push-everything" class="block text-sm font-medium text-base-content">Everything</label>
            </div>
            <div class="flex items-center gap-x-3">
              <input id="push-email" name="push-notifications" type="radio" class="relative size-4 appearance-none rounded-full border border-base-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-primary checked:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:before:bg-base-300 forced-colors:appearance-auto forced-colors:before:hidden">
              <label for="push-email" class="block text-sm font-medium text-base-content">Same as email</label>
            </div>
            <div class="flex items-center gap-x-3">
              <input id="push-nothing" name="push-notifications" type="radio" class="relative size-4 appearance-none rounded-full border border-base-300 bg-white before:absolute before:inset-1 before:rounded-full before:bg-white not-checked:before:hidden checked:border-primary checked:bg-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:border-base-300 disabled:bg-base-200 disabled:before:bg-base-300 forced-colors:appearance-auto forced-colors:before:hidden">
              <label for="push-nothing" class="block text-sm font-medium text-base-content">No push notifications</label>
            </div>
          </div>
        </fieldset>
      </div>
    </div>
  </div>

  <div class="mt-6 flex items-center justify-end gap-x-6">
    <button type="button" class="text-sm font-semibold text-base-content">Cancel</button>
    <button type="submit" class="btn btn-sm btn-cta">Save</button>
  </div>
</form>

@endsection