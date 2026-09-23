<tr class="border-b hover:bg-yellow-50">
    @if ($showDate)
        <td class="py-2 w-28 whitespace-nowrap">{{ $r['date']->format('d-m-Y') }}</td>
    @endif
    <td class="py-2 w-28 whitespace-nowrap text-gray-600">{{ $r['window'] ?: 'flexibel' }}</td>
    <td class="font-mono w-32"><a href="{{ $r['url'] }}" class="underline">{{ $r['number'] }}</a></td>
    <td>{{ $r['customer'] }}</td>
    <td class="whitespace-nowrap">{{ $r['postcode'] }} {{ $r['city'] }}</td>
    <td class="text-gray-600">{{ $r['what'] }}</td>
    <td class="whitespace-nowrap">
        @if ($r['driver'])
            {{ $r['driver'] }}
        @else
            <span class="bg-yellow-200 text-yellow-900 px-1 text-xs font-bold uppercase">geen chauffeur</span>
        @endif
    </td>
</tr>
