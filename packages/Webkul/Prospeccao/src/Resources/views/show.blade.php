<x-admin::layouts>
    <x-slot:title>
        Detalhes do imóvel - Prospecção
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col gap-1">
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ $property->property_type ?: 'Imóvel' }}
                    @if ($property->locality)
                        - {{ $property->locality }}
                    @endif
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Detalhes do imóvel e do contacto associado
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.prospeccao.index') }}" class="secondary-button">
                    Voltar à listagem
                </a>

                <a href="{{ route('admin.prospeccao.edit', $property->id) }}" class="primary-button">
                    Editar imóvel
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                Dados do imóvel
            </h2>

            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Tipo de imóvel</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white">
                        {{ $property->property_type ?: 'Não indicado' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Localidade</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white">
                        {{ $property->locality ?: 'Não indicada' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Preço pedido</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white">
                        {{ $property->asking_price !== null
                            ? number_format((float) $property->asking_price, 0, ',', '.') . ' EUR'
                            : 'Não indicado' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Anúncio original</dt>
                    <dd class="mt-1 break-all text-gray-800 dark:text-white">
                        @if ($property->listing_url)
                            <a href="{{ $property->listing_url }}" target="_blank" rel="noopener noreferrer"
                                class="text-blue-600 hover:underline">
                                Consultar anúncio
                            </a>
                        @else
                            Não indicado
                        @endif
                    </dd>
                </div>

                <div class="md:col-span-2">
                    <dt class="text-sm text-gray-500">Notas</dt>
                    <dd class="mt-1 whitespace-pre-line text-gray-800 dark:text-white">
                        {{ $property->notes ?: 'Sem notas registadas.' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                Contacto proprietário
            </h2>

            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="text-sm text-gray-500">Nome</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white">
                        {{ $property->person?->name ?: 'Não disponível' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Telefone</dt>
                    <dd class="mt-1 text-gray-800 dark:text-white">
                        {{ collect($property->person?->contact_numbers ?? [])->pluck('value')->filter()->first() ?: 'Sem telefone registado' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-sm text-gray-500">Email</dt>
                    <dd class="mt-1 break-all text-gray-800 dark:text-white">
                        {{ collect($property->person?->emails ?? [])->pluck('value')->filter()->first() ?: 'Sem email registado' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">
                    Preparar comunicação
                </h2>

                <a href="{{ route('admin.prospeccao.templates.index') }}"
                    class="text-sm text-blue-600 hover:underline">
                    Gerir modelos
                </a>
            </div>

            @if ($templates->isEmpty())
                <p class="text-sm text-gray-500">
                    Ainda não existem modelos ativos.
                    <a href="{{ route('admin.prospeccao.templates.create') }}" class="text-blue-600 hover:underline">
                        Criar o primeiro modelo
                    </a>
                </p>
            @else
                <form method="POST" action="{{ route('admin.prospeccao.communicate.prepare', $property->id) }}"
                    class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @csrf

                    <div class="flex flex-col gap-1 md:col-span-2">
                        <label for="template_id" class="text-sm font-medium">Modelo</label>
                        <select id="template_id" name="template_id" required
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="">Selecionar modelo</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" data-channel="{{ $template->channel }}">
                                    {{ $template->stage }} - {{ $template->name }} ({{ $template->channel }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="channel" class="text-sm font-medium">Canal</label>
                        <select id="channel" name="channel" required
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="whatsapp">WhatsApp</option>
                            <option value="email">Email</option>
                        </select>
                    </div>

                    <div class="md:col-span-3">
                        <button type="submit" class="primary-button">
                            Gerar mensagem para revisão
                        </button>
                    </div>
                </form>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                Conversações
            </h2>

            @if ($property->conversations->isEmpty())
                <p class="text-sm text-gray-500">
                    Ainda não existem conversações para este imóvel.
                </p>
            @else
                <div class="flex flex-col gap-6">
                    @foreach (['whatsapp' => 'WhatsApp', 'email' => 'Email'] as $channel => $channelLabel)
                        @php
                            $conversation = $property->conversations->firstWhere('channel', $channel);
                        @endphp

                        @if ($conversation)
                            <section class="border-t border-gray-200 pt-4 first:border-0 first:pt-0 dark:border-gray-700">
                                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                                    <h3 class="font-semibold text-gray-800 dark:text-white">
                                        {{ $channelLabel }}
                                    </h3>

                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        {{ [
                                            'active' => 'Ativa',
                                            'waiting_reply' => 'A aguardar resposta',
                                            'paused' => 'Pausada',
                                            'closed' => 'Encerrada',
                                        ][$conversation->status] ?? $conversation->status }}
                                    </span>
                                </div>

                                @if ($conversation->messages->isEmpty())
                                    <p class="text-sm text-gray-500">
                                        Ainda não existem mensagens nesta conversação.
                                    </p>
                                @else
                                    <div class="flex flex-col gap-3">
                                        @foreach ($conversation->messages as $message)
                                            <div class="max-w-3xl rounded-lg border border-gray-200 p-4 dark:border-gray-700
                                                {{ $message->direction === 'outgoing' ? 'ml-auto bg-blue-50 dark:bg-gray-800' : 'mr-auto bg-gray-50 dark:bg-gray-800' }}">
                                                <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                    <span class="font-semibold">
                                                        {{ $message->direction === 'outgoing' ? 'Enviada' : 'Recebida' }}
                                                    </span>

                                                    <span>·</span>

                                                    <span>
                                                        {{ $message->created_at?->format('d/m/Y H:i') }}
                                                    </span>

                                                    <span>·</span>

                                                    <span>
                                                        {{ [
                                                            'draft' => 'Rascunho',
                                                            'pending_review' => 'Pendente de aprovação',
                                                            'approved' => 'Aprovada',
                                                            'sent' => 'Enviada',
                                                            'received' => 'Recebida',
                                                            'failed' => 'Falhou',
                                                            'cancelled' => 'Cancelada',
                                                        ][$message->status] ?? $message->status }}
                                                    </span>

                                                    @if ($message->source === 'ai')
                                                        <span>·</span>
                                                        <span>Preparada por IA</span>
                                                    @elseif ($message->source === 'template')
                                                        <span>·</span>
                                                        <span>Modelo</span>
                                                    @endif
                                                </div>

                                                @if ($message->subject)
                                                    <p class="mb-2 text-sm font-semibold text-gray-800 dark:text-white">
                                                        {{ $message->subject }}
                                                    </p>
                                                @endif

                                                <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">
                                                    {{ $message->body }}
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </section>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                Oportunidade Krayin
            </h2>

            <p class="text-gray-800 dark:text-white">
                {{ $property->lead?->title ?: 'Sem oportunidade associada' }}
            </p>
        </div>
    </div>
</x-admin::layouts>
