<x-admin::layouts>
    <x-slot:title>
        Novo modelo de comunicação
    </x-slot:title>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Novo modelo de comunicação
            </p>

            <a href="{{ route('admin.fsbo.templates.index') }}" class="secondary-button">
                Voltar
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

        <form method="POST" action="{{ route('admin.fsbo.templates.store') }}"
            class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
            @csrf

            @include('fsbo::templates._form')

            <div class="mt-6 flex justify-end">
                <button type="submit" class="primary-button">
                    Guardar modelo
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
