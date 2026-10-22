@php
    $template = $template ?? null;
    $oldName = old('name', $template->name ?? null);
    $oldStage = old('stage', $template->stage ?? null);
    $oldChannel = old('channel', $template->channel ?? null);
    $oldSubject = old('subject', $template->subject ?? null);
    $oldBody = old('body', $template->body ?? null);
    $isActive = (bool) old('is_active', $template->is_active ?? true);
@endphp

<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <div class="flex flex-col gap-1 md:col-span-2">
        <label for="name" class="text-sm font-medium">Nome *</label>
        <input id="name" name="name" type="text" required maxlength="255"
            value="{{ $oldName }}"
            placeholder="Ex.: Primeiro contacto — proprietário"
            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
    </div>

    <div class="flex flex-col gap-1">
        <label for="stage" class="text-sm font-medium">Etapa *</label>
        <input id="stage" name="stage" type="text" required list="stage-options" maxlength="100"
            value="{{ $oldStage }}"
            placeholder="Ex.: 1º contacto"
            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
        <datalist id="stage-options">
            @foreach ($stages as $stage)
                <option value="{{ $stage }}"></option>
            @endforeach
        </datalist>
    </div>

    <div class="flex flex-col gap-1">
        <label for="channel" class="text-sm font-medium">Canal *</label>
        <select id="channel" name="channel" required
            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            @foreach ($channels as $value => $label)
                <option value="{{ $value }}" @selected($oldChannel === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="flex flex-col gap-1 md:col-span-2">
        <label for="subject" class="text-sm font-medium">Assunto (email)</label>
        <input id="subject" name="subject" type="text" maxlength="255"
            value="{{ $oldSubject }}"
            placeholder="Opcional — usado quando o canal inclui email"
            class="w-full rounded border border-gray-300 px-3 py-2 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
    </div>

    <div class="flex flex-col gap-1 md:col-span-2">
        <label for="body" class="text-sm font-medium">Corpo da mensagem *</label>
        <textarea id="body" name="body" rows="10" required
            placeholder="Escreva a mensagem e use as variáveis listadas abaixo"
            class="w-full rounded border border-gray-300 px-3 py-2 font-mono text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">{{ $oldBody }}</textarea>
    </div>

    <div class="flex items-center gap-2 md:col-span-2">
        <input id="is_active" name="is_active" type="checkbox" value="1" @checked($isActive)>
        <label for="is_active" class="text-sm font-medium">Modelo ativo</label>
    </div>
</div>

<div class="mt-4 rounded border border-dashed border-gray-300 p-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300">
    <p class="mb-2 font-medium text-gray-800 dark:text-white">Variáveis disponíveis</p>
    {{-- v-pre: o layout Admin usa Vue e interpreta {{ }} como interpolação --}}
    <ul class="flex flex-wrap gap-2" v-pre>
        @foreach ($variables as $key => $description)
            <li>
                <code class="rounded bg-gray-100 px-2 py-1 text-gray-800 dark:bg-gray-800 dark:text-gray-100"
                    title="{{ $description }}">@php echo '{'.'{'.$key.'}'.'}'; @endphp</code>
            </li>
        @endforeach
    </ul>
</div>
