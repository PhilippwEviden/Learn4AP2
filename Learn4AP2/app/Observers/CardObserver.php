<?php

namespace App\Observers;

use App\Models\Card;

class CardObserver
{
    public function created(Card $card): void
    {
        $card->progress()->create();
    }
}
