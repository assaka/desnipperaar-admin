<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // AddPlainTextAlternative en LogSentMessage staan hier met opzet NIET.
        //
        // Laravel zoekt zelf de luisteraars in app/Listeners op aan de hand van
        // het type dat handle() verwacht. Wie ze hier dan ook nog aanmeldt,
        // krijgt ze twee keer: MessageSent had twee luisteraars en dus draaide
        // LogSentMessage twee keer per mail. De tweede keer liep stuk op de
        // unieke sleutel van order_messages, wat per verstuurde mail een
        // foutmelding in de log gooide. Het bericht zelf kwam wel goed in de
        // geschiedenis, want de eerste ronde had hem al opgeslagen.
    }
}
