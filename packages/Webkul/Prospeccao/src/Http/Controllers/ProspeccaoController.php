<?php

namespace Webkul\Prospeccao\Http\Controllers;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use Webkul\Prospeccao\Models\CommunicationTemplate;
use Webkul\Prospeccao\Models\Property;
use Webkul\Prospeccao\Services\ContactLogService;
use Webkul\Prospeccao\Services\EmailService;
use Webkul\Prospeccao\Services\ProspeccaoLeadService;
use Webkul\Prospeccao\Services\WhatsAppService;
use Webkul\Prospeccao\Models\Conversation;
use Webkul\Prospeccao\Models\Message;
use Webkul\Prospeccao\Services\CommunicationDecisionService;
use Webkul\Prospeccao\Services\ConversationService;
use Webkul\Prospeccao\Services\CommunicationConfigService;

class ProspeccaoController extends Controller
{
    public function index()
    {
        $userIds = bouncer()->getAuthorizedUserIds();

        $query = Property::with(['person', 'lead']);

        if ($userIds !== null) {
            $query->whereHas('lead', function ($leadQuery) use ($userIds) {
                $leadQuery->whereIn('user_id', $userIds);
            });
        }

        $properties = $query
            ->latest()
            ->paginate(15);

        return view('prospeccao::index', compact('properties'));
    }

    public function show(int $id)
    {
        $property = $this->findAuthorizedProperty($id, [
            'person',
            'conversations.messages',
        ]);

        $templates = CommunicationTemplate::query()
            ->active()
            ->orderBy('stage')
            ->orderBy('name')
            ->get();

        return view('prospeccao::show', compact('property', 'templates'));
    }

    public function edit(int $id)
    {
        $property = $this->findAuthorizedProperty($id, ['person']);

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

        $property = $this->findAuthorizedProperty($id, ['person']);

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
        $property = $this->findAuthorizedProperty($id, ['person']);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:prospeccao_communication_templates,id'],
            'channel' => [
                'required',
                Rule::in([
                    CommunicationTemplate::CHANNEL_WHATSAPP,
                    CommunicationTemplate::CHANNEL_EMAIL,
                ])
            ],
        ]);

        $template = CommunicationTemplate::query()
            ->active()
            ->findOrFail($data['template_id']);

        if (
            ($data['channel'] === CommunicationTemplate::CHANNEL_WHATSAPP && !$template->supportsWhatsApp())
            || ($data['channel'] === CommunicationTemplate::CHANNEL_EMAIL && !$template->supportsEmail())
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
        ConversationService $conversationService,
        CommunicationConfigService $communicationConfig
    ) {
        $property = $this->findAuthorizedProperty($id, ['person']);

        $data = $request->validate([
            'template_id' => ['required', 'integer', 'exists:prospeccao_communication_templates,id'],
            'channel' => [
                'required',
                Rule::in([
                    CommunicationTemplate::CHANNEL_WHATSAPP,
                    CommunicationTemplate::CHANNEL_EMAIL,
                ]),
            ],
            'subject' => ['nullable', 'required_if:channel,email', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $template = CommunicationTemplate::query()
            ->active()
            ->findOrFail($data['template_id']);

        if (
            ($data['channel'] === CommunicationTemplate::CHANNEL_WHATSAPP && !$template->supportsWhatsApp())
            || ($data['channel'] === CommunicationTemplate::CHANNEL_EMAIL && !$template->supportsEmail())
        ) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'Este modelo não está disponível para o canal selecionado.');
        }

        if ($data['channel'] === CommunicationTemplate::CHANNEL_WHATSAPP) {
            $contact = collect($property->person?->contact_numbers ?? [])
                ->pluck('value')
                ->filter()
                ->first();
        } else {
            $contact = collect($property->person?->emails ?? [])
                ->pluck('value')
                ->filter()
                ->first();
        }

        if (!$contact) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'O contacto não tem os dados necessários para este canal.');
        }

        $conversation = $conversationService->getOrCreate(
            $property,
            $data['channel']
        );

        $message = $conversationService->createOutgoingDraft(
            conversation: $conversation,
            body: $data['body'],
            source: Message::SOURCE_TEMPLATE,
            subject: $data['subject'] ?? null,
            template: $template
        );

        $settings = $communicationConfig->get($data['channel']);

        $approvalRequired = $conversation->approval_required_override
            ?? $settings['approval_required'];

        if (!$approvalRequired) {
            $conversationService->approve($message);
        }

        return redirect()
            ->route('admin.prospeccao.show', $property->id)
            ->with('success', $approvalRequired
                ? 'Rascunho guardado. Aguarda aprovação antes do envio.'
                : 'Mensagem guardada e aprovada. Pode avançar para o envio manual.');
    }

    public function approveMessage(
        int $messageId,
        ConversationService $conversationService
    ) {
        $message = Message::with('conversation.property.lead')
            ->findOrFail($messageId);

        $this->preventUnauthorizedAccess(
            $message->conversation?->property?->lead?->user_id
        );

        try {
            $conversationService->approve($message);
        } catch (\LogicException $exception) {
            return redirect()
                ->route('admin.prospeccao.show', $message->conversation->property_id)
                ->with('error', 'Esta mensagem não pode ser aprovada no estado atual.');
        }

        return redirect()
            ->route('admin.prospeccao.show', $message->conversation->property_id)
            ->with('success', 'Mensagem aprovada. Pode agora avaliar as condições de envio.');
    }

    public function confirmMessageSent(
        int $messageId,
        ConversationService $conversationService
    ) {
        $message = Message::with('conversation.property.lead')
            ->findOrFail($messageId);

        $this->preventUnauthorizedAccess(
            $message->conversation?->property?->lead?->user_id
        );

        try {
            $conversationService->confirmSent($message);
        } catch (\LogicException $exception) {
            return redirect()
                ->route('admin.prospeccao.show', $message->conversation->property_id)
                ->with('error', 'Só é possível confirmar o envio de mensagens aprovadas.');
        }

        return redirect()
            ->route('admin.prospeccao.show', $message->conversation->property_id)
            ->with('success', 'Envio registado no histórico.');
    }


    public function openMessage(
        int $messageId,
        CommunicationDecisionService $decisionService,
        WhatsAppService $whatsAppService,
        EmailService $emailService
    ) {
        $message = Message::with('conversation.property.person', 'conversation.property.lead')
            ->findOrFail($messageId);

        $this->preventUnauthorizedAccess(
            $message->conversation?->property?->lead?->user_id
        );

        $conversation = $message->conversation;
        $property = $conversation->property;

        if (
            $message->direction !== Message::DIRECTION_OUTGOING
            || $message->status !== Message::STATUS_APPROVED
        ) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'A mensagem tem de estar aprovada antes de abrir o canal de envio.');
        }

        $decision = $decisionService->evaluate($conversation);

        if (!$decision['allowed']) {
            $error = 'Envio não permitido. Motivo: ' . $decision['reason'];

            if ($decision['next_action_at']) {
                $error .= '. Próxima oportunidade: '
                    . $decision['next_action_at']->format('d/m/Y H:i');
            }

            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', $error);
        }

        if ($conversation->channel === Conversation::CHANNEL_WHATSAPP) {
            $phone = collect($property->person?->contact_numbers ?? [])
                ->pluck('value')
                ->filter()
                ->first();

            if (!$phone) {
                return redirect()
                    ->route('admin.prospeccao.show', $property->id)
                    ->with('error', 'O contacto não tem telefone registado.');
            }

            $message->update([
                'opened_at' => now(),
            ]);

            return redirect()->away(
                $whatsAppService->buildUrl($phone, $message->body)
            );
        }

        $email = collect($property->person?->emails ?? [])
            ->pluck('value')
            ->filter()
            ->first();

        if (!$email) {
            return redirect()
                ->route('admin.prospeccao.show', $property->id)
                ->with('error', 'O contacto não tem email registado.');
        }

        $message->update([
            'opened_at' => now(),
        ]);

        return redirect()->away(
            $emailService->buildUrl(
                $email,
                $message->subject ?? '',
                $message->body
            )
        );
    }

    protected function findAuthorizedProperty(int $id, array $relations = []): Property
    {
        $property = Property::with(array_merge($relations, ['lead']))
            ->findOrFail($id);

        $this->preventUnauthorizedAccess($property->lead?->user_id);

        return $property;
    }

}
