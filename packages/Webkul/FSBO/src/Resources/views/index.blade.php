<x-admin::layouts>
    <x-slot:title>
        Imóveis FSBO
    </x-slot:title>

    <div class="flex flex-col gap-4">

        {{-- Sticky header bar --}}
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="fsbo.properties" />

                <div class="text-xl font-bold dark:text-white">
                    Imóveis FSBO
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.fsbo.create') }}" class="primary-button">
                    Novo contacto FSBO
                </a>
            </div>
        </div>

        {{-- Table card --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Proprietário</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Telefone</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Tipo de imóvel</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Localidade</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Preço pedido</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Anúncio</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($properties as $property)
                        <tr class="border-b border-gray-100 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">

                            {{-- Proprietário --}}
                            <td class="px-4 py-2.5">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    <x-admin::avatar :name="$property->person?->name ?? '?'" />
                                    <span class="truncate text-sm dark:text-gray-300">
                                        {{ $property->person?->name ?? '—' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Telefone --}}
                            <td class="px-4 py-2.5 text-sm dark:text-gray-300">
                                {{ $property->person?->contact_numbers[0]['value'] ?? '—' }}
                            </td>

                            {{-- Tipo de imóvel --}}
                            <td class="px-4 py-2.5">
                                <a
                                    href="{{ route('admin.fsbo.show', $property->id) }}"
                                    class="text-sm text-brandColor hover:underline"
                                >
                                    {{ $property->property_type ?? 'Imóvel' }}
                                </a>
                            </td>

                            {{-- Localidade --}}
                            <td class="px-4 py-2.5 text-sm dark:text-gray-300">
                                {{ $property->locality ?? '—' }}
                            </td>

                            {{-- Preço pedido --}}
                            <td class="px-4 py-2.5 text-sm dark:text-gray-300">
                                {{ $property->asking_price !== null ? number_format((float) $property->asking_price, 0, ',', '.') . ' €' : '—' }}
                            </td>

                            {{-- Anúncio --}}
                            <td class="px-4 py-2.5 text-sm">
                                @if ($property->listing_url)
                                    <a
                                        href="{{ $property->listing_url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-brandColor hover:underline"
                                    >
                                        Ver anúncio
                                    </a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Ações --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-1">
                                    <a
                                        href="{{ route('admin.fsbo.show', $property->id) }}"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                        title="Ver"
                                    >
                                        <span class="icon-eye"></span>
                                    </a>

                                    <a
                                        href="{{ route('admin.fsbo.edit', $property->id) }}"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                        title="Editar"
                                    >
                                        <span class="icon-edit"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                Ainda não existem imóveis FSBO registados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($properties->hasPages())
            <div class="px-1">
                {{ $properties->links() }}
            </div>
        @endif

    </div>
</x-admin::layouts>
