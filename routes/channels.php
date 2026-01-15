<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports.
|
*/

// Channel publik untuk antrian display
// Tidak perlu auth karena display bisa dilihat siapa saja
Broadcast::channel('antrian', function () {
    return true;
});
