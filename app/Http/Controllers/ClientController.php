<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ClientController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:clients.view', only: ['index', 'show']),
            new Middleware('can:clients.create', only: ['create', 'store']),
            new Middleware('can:clients.edit', only: ['edit', 'update']),
            new Middleware('can:clients.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $clients = Client::query()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
            ))
            ->withCount('projects')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('clients.index', compact('clients', 'search'));
    }

    public function create(): View
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        Client::create($data);

        return redirect()->route('clients.index')->with('success', 'تمت إضافة العميل بنجاح.');
    }

    public function edit(Client $client): View
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->update($this->validateData($request));

        return redirect()->route('clients.index')->with('success', 'تم تحديث بيانات العميل.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->projects()->exists()) {
            return back()->with('error', 'لا يمكن حذف عميل مرتبط بمشاريع.');
        }

        $client->delete();

        return back()->with('success', 'تم حذف العميل.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'phone2' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'commercial_register' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
