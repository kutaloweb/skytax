<?php

namespace Vanguard\Http\Controllers;

use App;
use Auth;
use Cookie;
use Vanguard\Chat;
use Vanguard\Http\Requests\Chat\CreateChatRequest;

class ChatController extends Controller
{
    /**
     * Displays the list of all chats.
     *
     * @return mixed
     */
    public function index()
    {
        $chats = Chat::where('user_id', Auth::user()->id)->get();

        return view('chat.index', compact("chats"));
    }

    /**
     * Displays the form for creating a new chat.
     *
     * @return mixed
     */
    public function create()
    {
        return view('chat.add');
    }

    /**
     * Displays the specified chat.
     *
     * @param $slug
     *
     * @return mixed
     */
    public function show($slug)
    {
        $user = Auth::user();
        $userName = $user->username ?: $user->first_name . ' ' . $user->last_name;
        $userEmail = $user->email;
        $chat = Chat::where('slug', $slug)->with('messages')->first();
        $chats = Chat::where('user_id', $user->id)->with('messages')->get();
        $url = explode(':', str_replace('http://', '', str_replace('https://', '', App::make('url')->to('/'))))[0];

        Cookie::queue('chat_id', $chat->id, 2628000);
        Cookie::queue('user_id', $user->id, 2628000);

        return view('chat.show', compact("userName", "userEmail", "chat", "chats", "url"));
    }

    /**
     * Stores a newly created chat.
     *
     * @param CreateChatRequest $request
     *
     * @return mixed
     */
    public function store(CreateChatRequest $request)
    {
        Chat::create([
            'title' => $request->get('title'),
            'slug' => $this->generateRandomString(),
            'user_id' => Auth::user()->id,
        ]);

        return redirect()->route('chat.list')
            ->withSuccess('New chat has been created.');
    }

    /**
     * Creates randomly generated url for chat.
     *
     * @param int $length
     *
     * @return mixed
     */
    private function generateRandomString($length = 20)
    {
        $string = str_random($length);
        $string = strtolower($string);

        if (Chat::where('slug', $string)->exists()) {
            return $this->generateSecureRandomString();
        };

        return $string;
    }
}