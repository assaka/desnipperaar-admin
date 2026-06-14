@component('emails.fr._layout', ['title' => 'Demande de devis '.$order->order_number])
<h1 style="font-size:22px;font-weight:900;margin:0 0 12px;">Merci ! Votre demande de devis a bien été reçue.</h1>

<p>Bonjour {{ explode(' ', $order->customer_name)[0] }},</p>

<p>Votre référence est <strong style="font-family:monospace;background:#F5C518;padding:2px 6px;">{{ $order->order_number }}</strong>.
Nous vous contacterons sous un jour ouvré avec un devis sur mesure.</p>

<p>Vous n'avez encore rien à payer ni à confirmer. Un devis est sans engagement tant que vous ne l'avez pas accepté.</p>

<p>Des questions ? Répondez simplement à cet e-mail.</p>

<p>Cordialement,<br>L'équipe DeSnipperaar</p>
@endcomponent
