<x-admin::layouts>
    <x-slot:title>
        Detalhes do imóvel FSBO
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <div class="flex flex-col gap-1">
                <p class="text-xl font-bold text-gray-800 dark:text-white">
                    {{ $property->property_type ?: 'Imóvel' }}
                    @if ($property->locality)
                        — {{ $property->locality }}
                    @endif
                </p>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Detalhes do imóvel e do contacto associado
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.fsbo.index') }}" class="secondary-button">
                    Voltar à listagem
                </a>

                <a href="{{ route('admin.fsbo.edit', $property->id) }}" class="primary-button">
                    Editar imóvel
                </a>
            </div>
        </div>

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
                            ? number_format((float) $property->asking_price, 0, ',', '.') . ' €'
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
                        @php
                            $phone = collect($property->person?->contact_numbers ?? [])
                                ->pluck('value')
                                ->filter()
                                ->first();
                        @endphp

                        {{ $phone ?: 'Não disponível' }}
                    </dd>
                </div>
            </dl>
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
