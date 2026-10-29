<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Where an officer stands by default
    |--------------------------------------------------------------------------
    |
    | A posting says where an officer was moved to, so an officer who has
    | never been posted has no state, LGA or duty post of their own. Without
    | one they could never be deployed, since a deployment stays inside the
    | state they are already in.
    |
    | So an officer with nothing on record is taken to be at this command.
    | The state and LGA are matched by name against the reference tables.
    |
    */

    'default_state' => env('POSTING_DEFAULT_STATE', 'Cross River'),

    'default_lga' => env('POSTING_DEFAULT_LGA', 'Calabar Municipal'),

    'default_station' => env('POSTING_DEFAULT_STATION', 'State Headquarters, Calabar'),

];
