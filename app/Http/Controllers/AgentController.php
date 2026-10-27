<?php

namespace App\Http\Controllers;

use App\Services\CvAssistantAgentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function __construct(
        protected CvAssistantAgentService $agentService
    ) {}

    /**
     * Display the CV Assistant Agent UI.
     *
     * @return View
     */
    public function index(): View
    {
        return view('agent-qa');
    }

    /**
     * Process user question using Gemini Tool-Calling Agent Loop.
     *
     * @param Request $request
     * @return JsonResponse|View
     */
    public function ask(Request $request)
    {
        $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $question = trim($request->input('question'));

        try {
            $result = $this->agentService->ask($question);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'question'   => $question,
                    'answer'     => $result['answer'],
                    'traces'     => $result['traces'],
                    'iterations' => $result['iterations'],
                ]);
            }

            return view('agent-qa', [
                'question'   => $question,
                'answer'     => $result['answer'],
                'traces'     => $result['traces'],
                'iterations' => $result['iterations'],
            ]);
        } catch (Exception $e) {
            Log::error('AgentController Error: ' . $e->getMessage());
            $errorMsg = 'Terjadi kesalahan saat memproses permintaan agent: ' . $e->getMessage();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => $errorMsg], 500);
            }
            return back()->withErrors(['question' => $errorMsg]);
        }
    }
}
