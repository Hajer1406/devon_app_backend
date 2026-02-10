<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactUsMail;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
            'subject' => ['required','string','max:255'],
            'message' => ['required','string','max:5000'],
        ]);

        $row = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'ip' => $request->ip(),
            'user_agent' => substr((string)$request->userAgent(), 0, 512),
        ]);

        $to = env('CONTACT_TO', config('mail.from.address'));
        Mail::to($to)->send(new ContactUsMail(
            $row->name,
            $row->email,
            $row->subject,
            $row->message
        ));

        return response()->json([
            'message' => 'Message sent.',
            'id' => $row->id,
        ], 201);
    }
}
