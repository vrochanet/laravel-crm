
<x-admin::layouts>
    <x-slot:title>
        Imóveis FSBO
    </x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Imóveis FSBO
            </p>

            <a
                href="{{ route('admin.fsbo.create') }}"
                class="primary-button"
            >
                Novo contacto FSBO
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-4 py-3 text-left">Proprietário</th>
                        <th class="px-4 py-3 text-left">Telefone</th>
                        <th class="px-4 py-3 text-left">Tipo de imóvel</th>
                        <th class="px-4 py-3 text-left">Localidade</th>
                        <th class="px-4 py-3 text-left">Preço pedido</th>
                        <th class="px-4 py-3 text-left">Anúncio</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($properties as $property)
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-4 py-3">
                                {{ $property->person?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $property->person?->contact_numbers[0]['value'] ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $property->property_type ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $property->locality ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $property->asking_price !== null
                                    ? number_format((float) $property->asking_price, 0, ',', '.') . ' €'
                                    : '—' }}
                            </td>

                            <td class="px-4 py-3">
                                @if ($property->listing_url)
                                    <a
                                        href="{{ $property->listing_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-blue-600 hover:underline"
                                    >
                                        Ver anúncio
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                Ainda não existem imóveis FSBO registados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $properties->links() }}
        </div>
    </div>
</x-admin::layouts>
