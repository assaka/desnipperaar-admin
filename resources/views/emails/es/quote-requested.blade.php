@component('emails.es._layout', ['title' => 'Solicitud de presupuesto '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">¡Gracias! Su solicitud de presupuesto se ha recibido.</h1>

<p>Hola {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Su referencia es <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Le contactaremos en un día laborable con un presupuesto a medida.</p>

<p>Todavía no tiene que pagar ni confirmar nada. Un presupuesto es sin compromiso hasta que lo acepte.</p>

<p>¿Alguna pregunta? Solo responda a este correo.</p>

<p>Un cordial saludo,<br>El equipo de DeSnipperaar</p>
@endcomponent
