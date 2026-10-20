@component('emails.es._layout', ['title' => 'El Día de Destrucción cambia'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Una última vez un {{ $oldPct }}% de descuento.</h1>

<p>A partir del 1 de noviembre, el Día de Destrucción ofrece un {{ $newPct }}% de descuento. Hasta entonces sigue siendo un {{ $oldPct }}%.</p>

<p>Queda un Día de Destrucción antes del 1 de noviembre. Ese día recibirá, como siempre, un correo con su código para un {{ $oldPct }}% de descuento. Si pide ese día, conserva ese descuento. La recogida puede ser más tarde.</p>

<p>¿Todavía tiene papel, archivos o un disco duro antiguo que destruir? Este es el momento.</p>

<p>Después del 1 de noviembre seguirá siendo el primero en saber cuándo es el Día de Destrucción.</p>

<p>El equipo de DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Recibe este mensaje porque se suscribió al Día de Destrucción.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Darse de baja</a>.
</p>
@endcomponent
