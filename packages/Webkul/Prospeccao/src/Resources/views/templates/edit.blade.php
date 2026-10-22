<x-admin::layouts>
    <x-slot:title>
        Editar modelo de comunicação
    </x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Editar modelo
            </p>

            <a href="{{ route('admin.prospeccao.templates.index') }}" class="secondary-button">
                Voltar
            </a>
        </div>

        @if (session('success'))
            <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.prospeccao.templates.update', $template->id) }}"
            class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf
            @method('PUT')

            @include('prospeccao::templates._form', ['template' => $template])

            <div class="mt-6 flex justify-end">
                <button type="submit" class="primary-button">
                    Guardar alterações
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>


