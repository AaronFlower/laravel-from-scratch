<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\Filesystem\Filesystem;
use App\Services\Twitter;

class UserController extends Controller
{
    //
    public function index(FileSystem $file) {
        dd(app('foo'));
        dd($file);
        echo "Hello";
        $bar = app()->make('Foo');
        dd($bar);
        return;
    }

    public function twitter(Twitter $twitter, Request $request) {
        dump($twitter);
        dump($request);
        return;
    }
}
