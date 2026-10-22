{{-- Offerte op briefpapier, voor klanten die ons als crediteur moeten aanmaken. --}}
<div class="flex flex-wrap gap-2 my-3">
    <a href="{{ route('orders.quote-pdf', $order) }}" target="_blank"
       class="bg-black text-yellow-400 px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Download PDF</a>
    <a href="{{ route('orders.quote-pdf', [$order, 'lang' => 'en']) }}" target="_blank"
       class="bg-black text-yellow-400 px-3 py-1 text-xs uppercase font-bold whitespace-nowrap">Download PDF (EN)</a>
</div>
