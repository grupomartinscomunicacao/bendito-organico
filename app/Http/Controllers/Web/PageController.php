<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Notifications\ContactMessageNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PageController extends Controller
{
    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function sendContact(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:20'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            // Honeypot: a real visitor never sees this field, a bot fills it in.
            'website' => ['prohibited'],
        ], [
            'message.min' => 'Conte um pouco mais para conseguirmos ajudar.',
            'website.prohibited' => 'Não foi possível enviar sua mensagem.',
        ]);

        try {
            Notification::route('mail', config('bendito.contact.email'))
                ->notify(new ContactMessageNotification($data));
        } catch (\Throwable $exception) {
            Log::error('Could not deliver a contact message.', ['message' => $exception->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Não conseguimos enviar sua mensagem agora. Tente pelo WhatsApp, por favor.');
        }

        return redirect()
            ->route('contact')
            ->with('success', 'Mensagem enviada! Respondemos em até um dia útil.');
    }
}
