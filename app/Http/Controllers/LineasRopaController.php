<?php

namespace App\Http\Controllers;

use App\Models\LineasRopa;
use App\Http\Requests\LineasRopaRequest;

/**
 * Class LineasRopaController
 * @package App\Http\Controllers
 */
class LineasRopaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lineasRopas = LineasRopa::paginate();

        return view('lineas-ropa.index', compact('lineasRopas'))
            ->with('i', (request()->input('page', 1) - 1) * $lineasRopas->perPage());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lineasRopa = new LineasRopa();
        return view('lineas-ropa.create', compact('lineasRopa'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LineasRopaRequest $request)
    {
        LineasRopa::create($request->validated());

        return redirect()->route('lineas-ropa.index')
            ->with('success', 'LineasRopa created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $lineasRopa = LineasRopa::find($id);

        return view('lineas-ropa.show', compact('lineasRopa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $lineasRopa = LineasRopa::find($id);

        return view('lineas-ropa.edit', compact('lineasRopa'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LineasRopaRequest $request, LineasRopa $lineasRopa)
    {
        $lineasRopa->update($request->validated());

        return redirect()->route('lineas-ropa.index')
            ->with('success', 'LineasRopa updated successfully');
    }

    public function destroy($id)
    {
        LineasRopa::find($id)->delete();

        return redirect()->route('lineas-ropa.index')
            ->with('success', 'LineasRopa deleted successfully');
    }
}
