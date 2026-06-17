@component('emails.fr._layout', ['title' => 'Bienvenue au JourDestruction'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Vous êtes inscrit.</h1>

<p>Merci de votre inscription au JourDestruction. Un jour au hasard chaque semaine, nous offrons {{ $pct }}% de réduction. Vous serez le premier informé dès que ce jour arrive.</p>

<p>Surveillez votre boîte de réception. À bientôt.</p>

<p>L'équipe DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Vous recevez ceci parce que vous vous êtes inscrit au JourDestruction.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Se désinscrire</a>.
</p>
@endcomponent
