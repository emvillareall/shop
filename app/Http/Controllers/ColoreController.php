<?php

namespace App\Http\Controllers;

use App\Models\Colores;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ColoreController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q', ''));

        $colores = Colores::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nombre_color', 'like', '%' . $search . '%')
                        ->orWhere('codigo_color', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('nombre_color')
            ->paginate(20)
            ->withQueryString();

        return view('colores.index', compact('colores', 'search'))
            ->with('i', (request()->input('page', 1) - 1) * $colores->perPage());
    }

    public function create()
    {
        $colore = new Colores();

        return view('colores.create', compact('colore'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        Colores::create($validated);

        return redirect()->route('colores.index')
            ->with('success', 'Color creado correctamente.');
    }

    public function show(string $id)
    {
        $colore = Colores::findOrFail($id);
        return view('colores.show', compact('colore'));
    }

    public function edit(string $id)
    {
        $colore = Colores::findOrFail($id);

        return view('colores.edit', compact('colore'));
    }

    public function update(Request $request, Colores $colore)
    {
        $validated = $this->validatePayload($request, $colore->id);
        $colore->update($validated);

        return redirect()->route('colores.index')
            ->with('success', 'Color actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $colore = Colores::findOrFail($id);
        $colore->delete();

        return redirect()->route('colores.index')
            ->with('success', 'Color eliminado correctamente.');
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        $validated = $request->validate([
            'nombre_color' => ['required', 'string', 'max:80'],
            'codigo_color' => [
                'required',
                'string',
                'regex:/^#([A-Fa-f0-9]{6})$/',
                Rule::unique('colores', 'codigo_color')->ignore($id),
            ],
        ], [
            'codigo_color.regex' => 'El codigo hexadecimal debe tener formato #RRGGBB.',
        ]);

        $validated['nombre_color'] = trim((string) $validated['nombre_color']);
        $validated['codigo_color'] = strtoupper(trim((string) $validated['codigo_color']));

        return $validated;
    }
}

