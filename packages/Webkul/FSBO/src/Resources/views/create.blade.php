
<x-admin::layouts>
    <x-slot:title>
        Novo Lead FSBO
    </x-slot>

    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">
                Novo Lead FSBO
            </h1>

            <button type="submit" form="fsbo-form" class="primary-button">
                Guardar lead
            </button>
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

        <x-admin::form
            id="fsbo-form"
            method="POST"
            :action="route('admin.fsbo.store')"
        >
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white">
                        Dados do proprietário
                    </h2>
                    <p class="text-sm text-gray-500">
                        Identificação e contacto do proprietário.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            Nome do proprietário
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="owner_name"
                            name="owner_name"
                            rules="required"
                            :value="old('owner_name')"
                            placeholder="Nome completo"
                        />

                        <x-admin::form.control-group.error control-name="owner_name" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label class="required">
                            Telefone
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="phone"
                            name="phone"
                            rules="required"
                            :value="old('phone')"
                            placeholder="Contacto telefónico"
                        />

                        <x-admin::form.control-group.error control-name="phone" />
                    </x-admin::form.control-group>
                </div>
            </div>

            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4">
                    <h2 class="text-base font-semibold text-gray-800 dark:text-white">
                        Dados do imóvel
                    </h2>
                    <p class="text-sm text-gray-500">
                        Informação do anúncio encontrado no Fizbot.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Tipo de imóvel
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="select"
                            id="property_type"
                            name="property_type"
                            :value="old('property_type')"
                        >
                            <option value="">Selecionar</option>
                            <option value="Apartamento">Apartamento</option>
                            <option value="Moradia">Moradia</option>
                            <option value="Terreno">Terreno</option>
                            <option value="Loja">Loja</option>
                            <option value="Escritório">Escritório</option>
                            <option value="Armazém">Armazém</option>
                            <option value="Outro">Outro</option>
                        </x-admin::form.control-group.control>

                        <x-admin::form.control-group.error control-name="property_type" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Localidade
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="locality"
                            name="locality"
                            :value="old('locality')"
                            placeholder="Ex.: Viseu"
                        />

                        <x-admin::form.control-group.error control-name="locality" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Preço pedido (€)
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="number"
                            id="asking_price"
                            name="asking_price"
                            step="0.01"
                            min="0"
                            :value="old('asking_price')"
                            placeholder="Ex.: 150000"
                        />

                        <x-admin::form.control-group.error control-name="asking_price" />
                    </x-admin::form.control-group>

                    <x-admin::form.control-group>
                        <x-admin::form.control-group.label>
                            Link do anúncio
                        </x-admin::form.control-group.label>

                        <x-admin::form.control-group.control
                            type="text"
                            id="listing_url"
                            name="listing_url"
                            :value="old('listing_url')"
                            placeholder="https://..."
                        />

                        <x-admin::form.control-group.error control-name="listing_url" />
                    </x-admin::form.control-group>
                </div>

                <x-admin::form.control-group>
                    <x-admin::form.control-group.label>
                        Notas
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="textarea"
                        id="notes"
                        name="notes"
                        :value="old('notes')"
                        placeholder="Observações sobre o proprietário ou imóvel"
                    />

                    <x-admin::form.control-group.error control-name="notes" />
                </x-admin::form.control-group>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="primary-button">
                    Guardar lead
                </button>
            </div>
        </x-admin::form>
    </div>
</x-admin::layouts>
