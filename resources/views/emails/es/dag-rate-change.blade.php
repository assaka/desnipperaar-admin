@component('emails.es._layout', ['title' => 'Todavía un '.$oldPct.'% de descuento'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Todavía un {{ $oldPct }}% de descuento para usted, por ahora.</h1>

<p>Como suscriptor, el Día de Destrucción obtiene un descuento en la destrucción de su papel y soportes de datos. A partir del 1 de noviembre ese descuento pasa a ser del {{ $newPct }}%.</p>

<p>Hasta entonces sigue valiendo el {{ $oldPct }}% completo. Queda un Día de Destrucción antes del 1 de noviembre. Ese día le enviaremos su código por correo.</p>

<p>Si pide ese día, todavía disfruta de un {{ $oldPct }}% de descuento. Después planificamos juntos la recogida, aunque sea en noviembre.</p>

<p>¿Todavía tiene cajas con documentos antiguos, expedientes de personal o un disco duro que destruir? Prepárelos y esté atento a su bandeja de entrada.</p>

<p>Saludos cordiales,<br>El equipo de DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Recibe este mensaje porque se suscribió al Día de Destrucción.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Darse de baja</a>.
</p>
@endcomponent
