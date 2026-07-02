@component('emails._layout', ['title' => ($kind === 'quote_request' ? 'Nieuwe offerteaanvraag ' : 'Nieuwe order ').$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">
  {{ $kind === 'quote_request' ? 'Nieuwe offerteaanvraag' : 'Nieuwe order' }}
</h1>

<p>Er is zojuist een {{ $kind === 'quote_request' ? 'offerteaanvraag' : 'order' }} binnengekomen via de website. Reply op deze mail om direct de klant te beantwoorden.</p>

<table cellpadding="6" cellspacing="0" border="0" style="border-collapse:collapse;font-size:14px;">
  <tr><td style="color:#666;">Referentie</td><td><strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong></td></tr>
  <tr><td style="color:#666;">Naam</td><td><strong>{{ $order->customer_name ?: '-' }}</strong></td></tr>
  <tr><td style="color:#666;">E-mail</td><td>{{ $order->customer_email ?: '-' }}</td></tr>
  <tr><td style="color:#666;">Telefoon</td><td>{{ $order->customer_phone ?: '-' }}</td></tr>
  <tr><td style="color:#666;">Plaats</td><td>{{ $order->customer_city ?: '-' }}</td></tr>
  @if ($order->delivery_mode)
    <tr><td style="color:#666;">Wijze</td><td>{{ $order->delivery_mode }}</td></tr>
  @endif
  @if ($order->box_count)
    <tr><td style="color:#666;">Dozen</td><td>{{ $order->box_count }}</td></tr>
  @endif
  @if ($order->container_count)
    <tr><td style="color:#666;">Rolcontainers</td><td>{{ $order->container_count }}</td></tr>
  @endif
  @if ($order->notes)
    <tr><td style="color:#666;vertical-align:top;">Toelichting</td><td style="white-space:pre-wrap;">{{ $order->notes }}</td></tr>
  @endif
</table>

<p style="margin-top:20px;">
  <a href="{{ $orderUrl }}" style="background:#F5C518;color:#0A0A0A;padding:10px 18px;text-decoration:none;font-weight:700;display:inline-block;">Open in admin</a>
</p>
@endcomponent
