<?php

namespace Webkul\Prospeccao\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;
use Webkul\Prospeccao\Models\CommunicationTemplate;
use Webkul\Prospeccao\Models\Property;
use Webkul\Prospeccao\Services\ContactLogService;
use Webkul\Prospeccao\Services\EmailService;
use Webkul\Prospeccao\Services\ProspeccaoLeadService;
use Webkul\Prospeccao\Services\WhatsAppService;

class ProspeccaoController extends Controller
{
    public function index()
    {
        $properties = Property::with(['person', 'lead'])
            ->latest()
            ->paginate(15);

        return view('prospeccao::index', compact('properties'));
    }

    public function show(int $id)
    {
        $property = Property::with(['person', 'lead', 'contactLogs.user', 'contactLogs.template'])
            ->findOrFail($id);

        $templates = CommunicationTemplate::query()
            ->active()
            ->orderBy('stage')
            ->orderBy('name')
            ->get();

        return view('prospeccao::show', compact('property', 'templates'));
    }

    public function edit(int $id)
    {
        $property = Property::with(['person', 'lead'])
            ->findOrFail($id);

        return view('prospeccao::edit', compact('property'));
    }

    public function create()
    {
        return view('prospeccao::create');
    }

    public function store(Request $request, ProspeccaoLeadService $service)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:255'],
            'asking_price' => ['nullable', 'numeric'],
            'listing_url' => ['nullable', 'url'],
            'notes' => ['nullable', 'string'],
        ]);

        $service->create($data);

        return redirect()
            ->route('admin.prospeccao.create')
            ->with('success', 'Lead criada com sucesso.');
    }

    public function update(Request $request, int $id)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:50'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:255'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:255'],
            'asking_price' => ['nullable', 'numeric'],
            'listing_url' => ['nullable', 'url'],
            'notes' => ['nullable', 'string'],
        ]);

        $property = Property::with('person')
            ->findOrFail($id);

        $property->update([
            'property_type' => $data['property_type'] ?? null,
            'locality' => $data['locality'] ?? null,
            'asking_price' => $data['asking_price'] ?? null,
            'listing_url' => $data['listing_url'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $person = $property->person;

        if ($person) {
            $person->name = $data['owner_name'];

            $phone = trim($data['phone'] ?? '');
            $email = strtolower(trim($data['email'] ?? ''));

            $person->contact_numbers = $phone !== '' ? [
                [
                    'value' => $phone,
                    'label' => 'mobile',
                ],
            ] : [];

            $person->emails = $email !== '' ? [
                [
                    'value' => $email,
                    'label' => 'work',
                ],
            ] : [];

            $person->save();
        }

        return redirect()
            ->route('admin.prospeccao.show', $property->id)
            ->with('success', 'Imóvel atualizado com sucesso.');
    }

    public function prepareCommunication(Request $request, int $id, ContactLogService $contactLogService)
    {
        $property = Property::with('person')->findOrFail($id);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:prospeccao_communication_templates,id'],
            'channel' => ['required', Rule::in([
                CommunicationTemplate::CHANNEL_WHATSAPP,
                CommunicationTemplate::CHANNEL_EMAIL,
            ])],
        ]);

        $template = CommunicationTemplate::query()
            ->active()
            ->findOrFail($data['template_id']);

        if (
            ($data['channel'] === CommunicationTemplate::CHANNEL_WHATSAPP && ! $template->supportsWhatsApp())
            || ($data['channel'] === CommunicationTemplate::CHANNEL_EMAIL && ! $template->supportsEmail())
        ) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'Este modelo nÃ£o estÃ¡ disponÃ­vel para o canal selecionado.');
        }

        $prepared = $contactLogService->prepare($template, $property);

        return view('prospeccao::communicate', [
            'property' => $property,
            'template' => $template,
            'channel' => $data['channel'],
            'prepared' => $prepared,
        ]);
    }

    public function sendCommunication(
        Request $request,
        int $id,
        ContactLogService $contactLogService,
        WhatsAppService $whatsAppService,
        EmailService $emailService
    ) {
        $property = Property::with('person')->findOrFail($id);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:prospeccao_communication_templates,id'],
            'channel' => ['required', Rule::in([
                CommunicationTemplate::CHANNEL_WHATSAPP,
                CommunicationTemplate::CHANNEL_EMAIL,
            ])],
            'subject' => ['nullable', 'required_if:channel,email', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $template = CommunicationTemplate::findOrFail($data['template_id']);

        $contactLogService->log(
            property: $property,
            channel: $data['channel'],
            body: $data['body'],
            subject: $data['subject'] ?? null,
            template: $template
        );

        if ($data['channel'] === CommunicationTemplate::CHANNEL_WHATSAPP) {
            $phone = collect($property->person?->contact_numbers ?? [])
                ->pluck('value')
                ->filter()
                ->first();

            if (! $phone) {
                return redirect()
                    ->route('admin.prospeccao.show', $property->id)
                    ->with('error', 'O contacto nÃ£o tem telefone registado.');
            }

            return redirect()->away(
                $whatsAppService->buildUrl($phone, $data['body'])
            );
        }

        $email = collect($property->person?->emails ?? [])
            ->pluck('value')
            ->filter()
            ->first();

        if (! $email) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'O contacto nÃ£o tem email registado.');
        }

        return redirect()->away(
            $emailService->buildUrl($email, $data['subject'] ?? '', $data['body'])
        );
    }

    public function whatsapp(int $id, WhatsAppService $whatsAppService)
    {
        $property = Property::with('person')
            ->findOrFail($id);

        $phone = collect($property->person?->contact_numbers ?? [])
            ->pluck('value')
            ->filter()
            ->first();

        if (! $phone) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'O contacto nÃ£o tem telefone registado.');
        }

        $message = sprintf(
            'Bom dia, %s. O meu nome Ã© VÃ­tor Rocha, consultor imobiliÃ¡rio da RE/MAX. Encontrei o seu anÃºncio de %s%s e gostaria de saber se estaria disponÃ­vel para conversar sobre a venda do imÃ³vel. Obrigado.',
            $property->person?->name ?? '',
            $property->property_type ?? 'imÃ³vel',
            $property->locality ? ' em '.$property->locality : ''
        );

        return redirect()->away(
            $whatsAppService->buildUrl($phone, $message)
        );
    }

    public function email(int $id, EmailService $emailService)
    {
        $property = Property::with('person')
            ->findOrFail($id);

        $email = collect($property->person?->emails ?? [])
            ->pluck('value')
            ->filter()
            ->first();

        if (! $email) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'O contacto nÃ£o tem email registado.');
        }

        $subject = sprintf(
            'Contacto sobre o seu anÃºncio%s',
            $property->locality ? ' em '.$property->locality : ''
        );

        $message = sprintf(
            "Bom dia, %s.\n\nO meu nome Ã© VÃ­tor Rocha, consultor imobiliÃ¡rio da RE/MAX. Encontrei o seu anÃºncio de %s%s e gostaria de saber se estaria disponÃ­vel para conversar sobre a venda do imÃ³vel.\n\nObrigado,\nVÃ­tor Rocha\nRE/MAX",
            $property->person?->name ?? '',
            $property->property_type ?? 'imÃ³vel',
            $property->locality ? ' em '.$property->locality : ''
        );

        return redirect()->away(
            $emailService->buildUrl($email, $subject, $message)
        );
    }
}

