@props(['items'])

{{-- Only renders when the result set actually spans more than one page. --}}
@if ($items instanceof \Illuminate\Contracts\Pagination\Paginator || $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
    {{ $items->onEachSide(1)->links('vendor.pagination.registry') }}
@endif
