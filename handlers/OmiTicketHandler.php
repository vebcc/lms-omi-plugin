<?php

class OmiTicketHandler
{
    public function ticketViewBeforeDisplay(array $hook_data)
    {
        //waiting for lms v28 release :(
        // tu bedzie albo by device id albo by customer id w zalezosci czy do zgloszenia przypisany jest komputer
        // i wtedy zwrocic odpowiednie id i dodac dodatkowa zmienna i zwrocic tym  to, a najlepiej zrobic uniwersalny i odrazu moze caly url zwracac a w id tylko ten tego i onego

        return $hook_data;
    }
}