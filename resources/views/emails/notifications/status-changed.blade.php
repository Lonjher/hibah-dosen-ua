<x-mail::message>
# {{ $title }}

Hello **{{ $recipientName }}**,

@if ($isAdmin)
The status of a **{{ $typeLabel }}** has been updated.
@else
The status of your **{{ $typeLabel }}** has been updated.
@endif

**Proposal Title**
{{ $proposalTitle }}

**Status Change**
`{{ $oldStatus }}` → **`{{ $newStatus }}`**

<x-mail::button :url="$url" color="success">
View Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
