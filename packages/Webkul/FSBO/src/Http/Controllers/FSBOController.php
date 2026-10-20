<?php

namespace Webkul\FSBO\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\FSBO\Services\FSBOLeadService;

class FSBOController extends Controller
{
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
}
