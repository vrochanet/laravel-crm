<x-admin::layouts>
    <x-slot:title>
        Preparar mensagem
    </x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    Revisar mensagem antes do envio
                </p>
                <p class="text-sm text-gray-500">
                    Modelo: {{ $template->name }} · Canal: {{ $channel }}
                    · {{ $property->property_type ?: 'Imóvel' }}
                    @if ($property->locality)
                        — {{ $property->locality }}
                    @endif
                </p>
            </div>

            <a href="{{ route('admin.fsbo.show', $property->id) }}" class="secondary-button">
                Cancelar
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form id="fsbo-communicate-form" method="POST"
            action="{{ route('admin.fsbo.communicate.send', $property->id) }}"
            target="_blank"
            class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf

            <input type="hidden" name="template_id" value="{{ $template->id }}">
            <input type="hidden" name="channel" value="{{ $channel }}">

            @if ($channel === 'email')
                <div class="mb-4 flex flex-col gap-1">
                    <label for="subject" class="text-sm font-medium">Assunto</label>
                    <input id="subject" name="subject" type="text" required maxlength="255"
                        value="{{ old('subject', $prepared['subject']) }}"
                        class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            @endif

            <div class="mb-4 flex flex-col gap-1">
                <label for="body" class="text-sm font-medium">Mensagem</label>
                <textarea id="body" name="body" rows="12" required
                    class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('body', $prepared['body']) }}</textarea>
                <p class="text-xs text-gray-500">
                    Pode editar o texto. Ao confirmar, esta cópia fica registada no histórico (edições futuras do modelo não a alteram).
                </p>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.fsbo.show', $property->id) }}" class="secondary-button">
                    Voltar
                </a>
                <button type="submit" class="primary-button">
                    @if ($channel === 'whatsapp')
                        Confirmar e abrir WhatsApp
                    @else
                        Confirmar e abrir email
                    @endif
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('fsbo-communicate-form')?.addEventListener('submit', function () {
            // Mantém esta aba no CRM (ficha do imóvel); o WhatsApp/email abre na nova aba.
            setTimeout(function () {
                window.location.href = @json(route('admin.fsbo.show', $property->id));
            }, 400);
        });
    </script>
</x-admin::layouts>
