@component('emails.fr._layout', ['title' => 'Encore '.$oldPct.'% de réduction'])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Encore {{ $oldPct }}% de réduction pour vous, pour l’instant.</h1>

<p>En tant qu’abonné, vous bénéficiez d’une réduction sur la destruction de vos papiers et supports de données lors du Jour de Destruction. À partir du 1er novembre, cette réduction passe à {{ $newPct }}%.</p>

<p>D’ici là, les {{ $oldPct }}% s’appliquent toujours. Il reste un Jour de Destruction avant le 1er novembre. Ce jour-là, nous vous enverrons votre code par e-mail.</p>

<p>Commandez ce jour-là et vous profitez encore de {{ $oldPct }}% de réduction. Nous planifions ensuite l’enlèvement ensemble, même s’il a lieu en novembre.</p>

<p>Vous avez encore des cartons d’anciens dossiers, des dossiers du personnel ou un disque dur à faire détruire ? Préparez-les et surveillez votre boîte de réception.</p>

<p>Cordialement,<br>L’équipe DeSnipperaar</p>

<p style="font-size:11px;color:#999;margin-top:24px;border-top:1px solid #EEE;padding-top:12px;">
    Vous recevez ce message car vous vous êtes inscrit au Jour de Destruction.
    <a href="{{ $unsubscribeUrl }}" style="color:#999;">Se désinscrire</a>.
</p>
@endcomponent
