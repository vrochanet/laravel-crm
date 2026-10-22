<x-admin::layouts>
    <x-slot:title>
        Editar imóvel FSBO
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Editar imóvel FSBO
            </p>

            <a href="{{ route('admin.fsbo.show', $property->id) }}" class="secondary-button">
                Cancelar
            </a>
        </div>

        @if ($errors->any())
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.fsbo.update', $property->id) }}" class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                    Contacto proprietário
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label for="owner_name" class="text-sm font-medium">
                            Nome do proprietário *
                        </label>
                        <input id="owner_name" name="owner_name" type="text"
                            value="{{ old('owner_name', $property->person?->name) }}" required maxlength="255"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="phone" class="text-sm font-medium">
                            Telefone *
                        </label>
                        <input id="phone" name="phone" type="text"
                            value="{{ old('phone',collect($property->person?->contact_numbers ?? [])->pluck('value')->filter()->first()) }}"
                            required maxlength="50"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="email" class="text-sm font-medium">
                            Email
                        </label>
                        <input id="email" name="email" type="email"
                            value="{{ old('email',collect($property->person?->emails ?? [])->pluck('value')->filter()->first()) }}"
                            maxlength="255"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
                    Dados do imóvel
                </h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <label for="property_type" class="text-sm font-medium">
                            Tipo de imóvel
                        </label>
                        <input id="property_type" name="property_type" type="text"
                            value="{{ old('property_type', $property->property_type) }}" maxlength="100"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="locality" class="text-sm font-medium">
                            Localidade
                        </label>
                        <input id="locality" name="locality" type="text"
                            value="{{ old('locality', $property->locality) }}" maxlength="255"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="asking_price" class="text-sm font-medium">
                            Preço pedido (€)
                        </label>
                        <input id="asking_price" name="asking_price" type="number" min="0" step="0.01"
                            value="{{ old('asking_price', $property->asking_price) }}"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="listing_url" class="text-sm font-medium">
                            URL do anúncio
                        </label>
                        <input id="listing_url" name="listing_url" type="url"
                            value="{{ old('listing_url', $property->listing_url) }}"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>

                    <div class="flex flex-col gap-1 md:col-span-2">
                        <label for="notes" class="text-sm font-medium">
                            Notas
                        </label>
                        <textarea id="notes" name="notes" rows="5"
                            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ old('notes', $property->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="primary-button">
                    Guardar alterações
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>
