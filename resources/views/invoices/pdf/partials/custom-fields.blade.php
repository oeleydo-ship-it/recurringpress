{{-- Custom fields marked "show on PDF / client view". Expects $doc (invoice or quotation) and $entity. --}}
@php
    $customRows = \App\Support\CustomFields::display($entity, $doc->custom_values, $doc->company_id, true);
@endphp
@if(count($customRows))
    <table style="width: 100%; margin-top: 18px; border-collapse: collapse; font-size: 11px; page-break-inside: avoid;">
        @foreach(array_chunk($customRows, 2) as $pair)
            <tr>
                @foreach($pair as $row)
                    <td style="width: 50%; padding: 5px 10px 5px 0; vertical-align: top;">
                        <div style="font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #777;">{{ $row['label'] }}</div>
                        <div style="margin-top: 2px; color: #222; white-space: pre-line;">{{ $row['value'] }}</div>
                    </td>
                @endforeach
                @if(count($pair) === 1)
                    <td style="width: 50%;"></td>
                @endif
            </tr>
        @endforeach
    </table>
@endif
