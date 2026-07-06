<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\N8nChat\Http\Controllers'], function()
{
    Route::get('/', 'N8nChatController@index');
});
