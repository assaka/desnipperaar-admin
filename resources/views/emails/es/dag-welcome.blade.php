@component('emails.es._layout', ['title' => 'Bienvenido al DíaDestrucción'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Está suscrito.</h1>

<p>Gracias por suscribirse al DíaDestrucción. Un día al azar cada semana ofrecemos un {{ $pct }}% de descuento. Será el primero en enterarse en cuanto llegue ese día.</p>

<p>Esté atento a su bandeja de entrada. Hasta pronto.</p>

<p>Equipo DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Recibe esto porque se suscribió al DíaDestrucción.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Darse de baja</a>.
</p>
@endcomponent
