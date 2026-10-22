<?php

namespace Webkul\FSBO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\FSBO\Models\CommunicationTemplate;
use Webkul\FSBO\Services\TemplateParserService;

class CommunicationTemplateController extends Controller
{
    public function index()
    {
        $templates = CommunicationTemplate::query()
            ->orderByDesc('is_active')
            ->orderBy('stage')
            ->orderBy('name')
            ->paginate(20);

        return view('fsbo::templates.index', compact('templates'));
    }

    public function create(TemplateParserService $parser)
    {
        return view('fsbo::templates.create', [
            'variables' => $parser->availableVariables(),
            'channels' => $this->channels(),
            'stages' => $this->stages(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        CommunicationTemplate::create($data);

        return redirect()
            ->route('admin.fsbo.templates.index')
            ->with('success', 'Modelo de comunicação criado com sucesso.');
    }

    public function edit(int $id, TemplateParserService $parser)
    {
        $template = CommunicationTemplate::findOrFail($id);

        return view('fsbo::templates.edit', [
            'template' => $template,
            'variables' => $parser->availableVariables(),
            'channels' => $this->channels(),
            'stages' => $this->stages(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);

        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        $template->update($data);

        return redirect()
            ->route('admin.fsbo.templates.index')
            ->with('success', 'Modelo de comunicação atualizado com sucesso.');
    }

    public function duplicate(int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);

        $copy = $template->replicate(['created_at', 'updated_at']);
        $copy->name = $template->name.' (cópia)';
        $copy->is_active = true;
        $copy->save();

        return redirect()
            ->route('admin.fsbo.templates.edit', $copy->id)
            ->with('success', 'Modelo duplicado. Pode editar a cópia.');
    }

    public function deactivate(int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        $template->update(['is_active' => false]);

        return redirect()
            ->route('admin.fsbo.templates.index')
            ->with('success', 'Modelo desativado.');
    }

    public function activate(int $id)
    {
        $template = CommunicationTemplate::findOrFail($id);
        $template->update(['is_active' => true]);

        return redirect()
            ->route('admin.fsbo.templates.index')
            ->with('success', 'Modelo reativado.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'stage' => ['required', 'string', 'max:100'],
            'channel' => ['required', Rule::in(array_keys($this->channels()))],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function channels(): array
    {
        return [
            CommunicationTemplate::CHANNEL_WHATSAPP => 'WhatsApp',
            CommunicationTemplate::CHANNEL_EMAIL => 'Email',
            CommunicationTemplate::CHANNEL_BOTH => 'Ambos',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function stages(): array
    {
        return [
            '1º contacto',
            '2º contacto',
            'Objeção',
            'Follow-up',
            'Fecho',
        ];
    }
}
