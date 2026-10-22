<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $conversation = ChatConversation::where('user_id', $request->user()->id)
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->first();

        return response()->json([
            'messages' => $conversation?->messages
                ->map(fn (ChatMessage $message) => $this->messageData($message))
                ->values() ?? [],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $messages = DB::transaction(function () use ($request, $validated) {
            $conversation = ChatConversation::firstOrCreate([
                'user_id' => $request->user()->id,
            ]);

            $userMessage = $conversation->messages()->create([
                'sender' => 'user',
                'content' => trim($validated['content']),
            ]);

            $assistantMessage = $conversation->messages()->create([
                'sender' => 'assistant',
                'content' => 'Gracias por compartirlo. No soy profesional de salud y no puedo diagnosticar ni ofrecer atención clínica. Si te ayuda, puedes contarme qué necesitas en este momento.',
            ]);

            return [$userMessage, $assistantMessage];
        });

        return response()->json([
            'messages' => array_map(
                fn (ChatMessage $message) => $this->messageData($message),
                $messages
            ),
        ], 201);
    }

    private function messageData(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender' => $message->sender,
            'content' => $message->content,
            'created_at' => $message->created_at->toISOString(),
        ];
    }
}
