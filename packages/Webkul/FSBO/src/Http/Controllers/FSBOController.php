<?php

namespace Webkul\FSBO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\FSBO\Services\FSBOLeadService;

class FSBOController extends Controller
{
    public function index()
    {
        $properties = \Webkul\FSBO\Models\Property::with(['person', 'lead'])
            ->latest()
            ->paginate(15);

        return view('fsbo::index', compact('properties'));
    }

    public function show(int $id)
    {
        $property = \Webkul\FSBO\Models\Property::with(['person', 'lead'])
            ->findOrFail($id);

        return view('fsbo::show', compact('property'));
    }

    public function edit(int $id)
    {
        $property = \Webkul\FSBO\Models\Property::with(['person', 'lead'])
            ->findOrFail($id);

        return view('fsbo::edit', compact('property'));
    }

    public function create()
    {
        return view('fsbo::create');
    }

    public function store(Request $request, FSBOLeadService $service)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'property_type' => ['nullable', 'string', 'max:100'],
            'locality' => ['nullable', 'string', 'max:255'],
            'asking_price' => ['nullable', 'numeric'],
            'listing_url' => ['nullable', 'url'],
            'notes' => ['nullable', 'string'],
        ]);

        $service->create($data);

        return redirect()
            ->route('admin.fsbo.create')
            ->with('success', 'Lead FSBO criado com sucesso.');
    }

    public function update(Request $request, int $id)
{
    $data = $request->validate([
        'owner_name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:50'],
        'property_type' => ['nullable', 'string', 'max:100'],
        'locality' => ['nullable', 'string', 'max:255'],
        'asking_price' => ['nullable', 'numeric'],
        'listing_url' => ['nullable', 'url'],
        'notes' => ['nullable', 'string'],
    ]);

    $property = \Webkul\FSBO\Models\Property::with('person')
        ->findOrFail($id);

    $property->update([
        'property_type' => $data['property_type'] ?? null,
        'locality' => $data['locality'] ?? null,
        'asking_price' => $data['asking_price'] ?? null,
        'listing_url' => $data['listing_url'] ?? null,
        'notes' => $data['notes'] ?? null,
    ]);

    $person = $property->person;

    if ($person) {
        $person->name = $data['owner_name'];
        $person->contact_numbers = [
            [
                'value' => $data['phone'],
                'label' => 'mobile',
            ],
        ];

        $person->save();
    }

    return redirect()
        ->route('admin.fsbo.show', $property->id)
        ->with('success', 'Imóvel FSBO atualizado com sucesso.');
}
}
