<x-mail::message>
# {{ $title }}

Hello **{{ $recipientName }}**,

**{{ $actorName }}** has uploaded a new **{{ $typeLabel }}** for review.

**Proposal Title**
{{ $proposalTitle }}

<x-mail::button :url="$url" color="success">
View Submission
</x-mail::button>

Please log in to SIM-LITABMAS to review and take action.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
