<?php

return [
    /*
     * Percentual padrão usado quando a arena não possuir uma taxa própria.
     */
    'cancellation_fee_percent' => (float) env('CANCELLATION_FEE_PERCENT', 30),
];
