@component('emails.fr._layout', ['title' => 'Le Jour de Destruction change'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Une dernière fois {{ $oldPct }}% de réduction.</h1>

<p>À partir du 1er novembre, le Jour de Destruction offre {{ $newPct }}% de réduction. D’ici là, c’est encore {{ $oldPct }}%.</p>

<p>Il reste un Jour de Destruction avant le 1er novembre. Ce jour-là, vous recevrez comme toujours un e-mail avec votre code pour {{ $oldPct }}% de réduction. Commandez ce jour-là et vous gardez cette réduction. L’enlèvement peut avoir lieu plus tard.</p>

<p>Vous avez encore des papiers, des archives ou un vieux disque dur à faire détruire ? C’est le moment.</p>

<p>Après le 1er novembre, vous serez toujours le premier informé du Jour de Destruction.</p>

<p>L’équipe DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Vous recevez ce message car vous vous êtes inscrit au Jour de Destruction.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Se désinscrire</a>.
</p>
@endcomponent
