<x-admin::layouts>
    <x-slot:title>
        Modelos de comunicação Prospeção
    </x-slot:title>

    <div class="flex flex-col gap-4">

        {{-- Sticky header bar --}}
        <div class="scroll-reactive-sticky sticky top-[60px] z-[1000] flex items-center justify-between rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <x-admin::breadcrumbs name="prospeccao.templates" />

                <div class="text-xl font-bold dark:text-white">
                    Modelos de comunicação
                </div>
            </div>

            <div class="flex items-center gap-x-2.5">
                <a href="{{ route('admin.prospeccao.templates.create') }}" class="primary-button">
                    Novo modelo
                </a>
            </div>
        </div>

        {{-- Table card --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full table-auto">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-800">
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Nome</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Etapa</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Canal</th>
                        <th class="px-4 py-2.5 text-left text-sm font-medium text-gray-600 dark:text-gray-300">Estado</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($templates as $template)
                        <tr class="border-b border-gray-100 transition-all hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-950">

                            {{-- Nome --}}
                            <td class="px-4 py-2.5">
                                <a
                                    href="{{ route('admin.prospeccao.templates.edit', $template->id) }}"
                                    class="text-sm text-brandColor hover:underline"
                                >
                                    {{ $template->name }}
                                </a>
                            </td>

                            {{-- Etapa --}}
                            <td class="px-4 py-2.5 text-sm dark:text-gray-300">
                                {{ $template->stage }}
                            </td>

                            {{-- Canal --}}
                            <td class="px-4 py-2.5 text-sm capitalize dark:text-gray-300">
                                {{ $template->channel }}
                            </td>

                            {{-- Estado --}}
                            <td class="px-4 py-2.5">
                                @if ($template->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        Inativo
                                    </span>
                                @endif
                            </td>

                            {{-- Ações --}}
                            <td class="px-4 py-2.5">
                                <div class="flex items-center justify-end gap-1">
                                    {{-- Editar --}}
                                    <a
                                        href="{{ route('admin.prospeccao.templates.edit', $template->id) }}"
                                        class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                        title="Editar"
                                    >
                                        <span class="icon-edit"></span>
                                    </a>

                                    {{-- Duplicar --}}
                                    <form method="POST" action="{{ route('admin.prospeccao.templates.duplicate', $template->id) }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                            title="Duplicar"
                                        >
                                            <span class="icon-more"></span>
                                        </button>
                                    </form>

                                    {{-- Ativar / Desativar --}}
                                    @if ($template->is_active)
                                        <form method="POST" action="{{ route('admin.prospeccao.templates.deactivate', $template->id) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                                title="Desativar"
                                            >
                                                <span class="icon-filter"></span>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.prospeccao.templates.activate', $template->id) }}">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="cursor-pointer rounded-md p-1.5 text-2xl text-gray-600 transition-all hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800"
                                                title="Ativar"
                                            >
                                                <span class="icon-tick"></span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                Ainda não existem modelos. Crie o primeiro para reutilizar nas fichas Prospeção.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($templates->hasPages())
            <div class="px-1">
                {{ $templates->links() }}
            </div>
        @endif

    </div>
</x-admin::layouts>


